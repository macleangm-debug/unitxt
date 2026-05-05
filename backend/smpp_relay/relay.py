#!/usr/bin/env python3
"""unitxt SMPP Relay Daemon
==========================

This is a STANDALONE daemon — it must run on the host that has the IPSec VPN
tunnel to the SMSC (e.g. Tigo's `smpp01.tigo.co.tz` is only reachable from
your VPN-attached server `41.220.143.37`).

Responsibilities
----------------
1. Connect to the same MongoDB instance that unitxt uses.
2. For every active provider with `transport == "smpp"`, hold a long-lived
   SMPP TRX bind to the SMSC.
3. Drain `db.smpp_outbox` rows as fast as the SMSC accepts them, respecting
   each provider's `smpp_throughput_per_sec` and `smpp_window_size`.
4. When the SMSC returns the submit_sm_resp, update the corresponding row
   in `db.messages` (status = "sent", provider_msg_id = SMSC id).
5. When the SMSC pushes a deliver_sm DLR, parse it and update
   `db.messages.status` to `delivered` / `failed` with the DLR error code.
6. Heartbeat: write `db.providers.{id}.last_smpp_heartbeat` every 30 s so
   the unitxt admin UI can show the bind as healthy.

Deployment
----------
1. Copy the contents of `/app/backend/smpp_relay/` to your VPN host
   (`/opt/unitxt-smpp-relay/`).
2. Create `relay.env` (next to `relay.py`) with:

       MONGO_URL="mongodb+srv://..."          # same DB unitxt uses
       DB_NAME="unitxt"
       LOG_LEVEL=INFO
       HEARTBEAT_INTERVAL_SEC=30
       POLL_INTERVAL_MS=250

3. Install deps:

       pip install -r requirements.relay.txt

4. Run under systemd (see `unitxt-smpp-relay.service`).

Provider configuration is read from MongoDB — set `transport=smpp` plus the
SMPP fields on a provider via the unitxt admin UI.  The daemon picks new
providers up on the next 30-second cycle.
"""
import logging
import os
import signal
import sys
import threading
import time
from collections import deque
from datetime import datetime, timezone
from typing import Dict, Optional

try:
    import smpplib.client
    import smpplib.consts
    import smpplib.gsm
except ImportError:
    print("ERROR: pip install smpplib pymongo python-dotenv", file=sys.stderr)
    sys.exit(1)

from pymongo import MongoClient
from pymongo.errors import PyMongoError
from dotenv import load_dotenv

load_dotenv(os.path.join(os.path.dirname(__file__), "relay.env"))

LOG_LEVEL = os.environ.get("LOG_LEVEL", "INFO").upper()
HEARTBEAT_SEC = int(os.environ.get("HEARTBEAT_INTERVAL_SEC", "30"))
POLL_MS = int(os.environ.get("POLL_INTERVAL_MS", "250"))

logging.basicConfig(level=LOG_LEVEL,
                    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s")
log = logging.getLogger("unitxt-smpp-relay")


def utcnow_iso() -> str:
    return datetime.now(timezone.utc).isoformat()


# ---------------------------------------------------------------------------
# One ProviderBind per active SMPP provider — keeps a bind, sender thread,
# and DLR receiver thread alive for the lifetime of the provider doc.
# ---------------------------------------------------------------------------
class ProviderBind:
    def __init__(self, provider: dict, db):
        self.provider = provider
        self.db = db
        self.id = provider["id"]
        self.name = provider.get("name") or self.id
        self.client: Optional[smpplib.client.Client] = None
        self.connected = False
        self.stopping = False
        self._sender_thread: Optional[threading.Thread] = None
        self._listener_thread: Optional[threading.Thread] = None
        self._heartbeat_thread: Optional[threading.Thread] = None
        # outstanding submits keyed by SMPP sequence number, value is correlation_token
        self._pending: Dict[int, str] = {}
        self._send_window = deque()  # for throughput throttling

    # -------- lifecycle --------
    def start(self):
        log.info(f"[{self.name}] connecting to "
                 f"{self.provider.get('smpp_host')}:{self.provider.get('smpp_port')}")
        self.client = smpplib.client.Client(
            self.provider.get("smpp_host"),
            int(self.provider.get("smpp_port") or 2775),
            allow_unknown_opt_params=True,
        )
        # Hook up DLR / response handlers
        self.client.set_message_sent_handler(self._on_message_sent)
        self.client.set_message_received_handler(self._on_message_received)

        try:
            self.client.connect()
            bind_mode = (self.provider.get("smpp_bind_mode") or "trx").lower()
            common = dict(
                system_id=self.provider.get("smpp_system_id") or "",
                password=self.provider.get("smpp_password") or "",
                system_type=self.provider.get("smpp_system_type") or "",
            )
            if bind_mode == "tx":
                self.client.bind_transmitter(**common)
            elif bind_mode == "rx":
                self.client.bind_receiver(**common)
            else:
                self.client.bind_transceiver(**common)
            self.connected = True
            log.info(f"[{self.name}] bound ({bind_mode})")
        except Exception:
            log.exception(f"[{self.name}] bind failed")
            self.connected = False
            return

        # Threads: smpplib's listen() blocks; sender uses the same client and
        # synchronises through smpplib's internal thread-safe send_message.
        self._listener_thread = threading.Thread(
            target=self._run_listener, name=f"smpp-rx-{self.name}", daemon=True)
        self._sender_thread = threading.Thread(
            target=self._run_sender, name=f"smpp-tx-{self.name}", daemon=True)
        self._heartbeat_thread = threading.Thread(
            target=self._run_heartbeat, name=f"smpp-hb-{self.name}", daemon=True)
        self._listener_thread.start()
        self._sender_thread.start()
        self._heartbeat_thread.start()

    def stop(self):
        self.stopping = True
        try:
            if self.client and self.connected:
                self.client.unbind()
                self.client.disconnect()
        except Exception:
            pass
        self.connected = False

    # -------- worker threads --------
    def _run_listener(self):
        try:
            self.client.listen()
        except Exception:
            if not self.stopping:
                log.exception(f"[{self.name}] listener crashed")
                self.connected = False

    def _run_sender(self):
        max_tps = int(self.provider.get("smpp_throughput_per_sec") or 30)
        window = int(self.provider.get("smpp_window_size") or 10)
        while not self.stopping and self.connected:
            # window: cap outstanding submit_sm
            if len(self._pending) >= window:
                time.sleep(POLL_MS / 1000.0)
                continue
            # claim one outbox row atomically
            doc = self.db.smpp_outbox.find_one_and_update(
                {"provider_id": self.id, "status": "queued"},
                {"$set": {"status": "sending", "claimed_at": utcnow_iso()},
                 "$inc": {"attempts": 1}},
                sort=[("created_at", 1)],
            )
            if not doc:
                time.sleep(POLL_MS / 1000.0)
                continue
            self._throttle(max_tps)
            try:
                self._submit(doc)
            except Exception as e:
                log.exception(f"[{self.name}] submit_sm failed for {doc['id']}")
                self.db.smpp_outbox.update_one(
                    {"id": doc["id"]},
                    {"$set": {"status": "queued", "last_error": str(e)}})

    def _throttle(self, max_tps: int):
        now = time.time()
        # drop entries older than 1s
        while self._send_window and now - self._send_window[0] > 1.0:
            self._send_window.popleft()
        if len(self._send_window) >= max_tps:
            sleep_for = 1.0 - (now - self._send_window[0])
            if sleep_for > 0:
                time.sleep(sleep_for)
        self._send_window.append(time.time())

    def _submit(self, doc: dict):
        body = doc.get("body") or ""
        encoded, encoding_flag, msg_type_flag = smpplib.gsm.make_parts(body)
        for part in encoded:
            pdu = self.client.send_message(
                source_addr_ton=int(self.provider.get("smpp_source_ton", 5)),
                source_addr_npi=int(self.provider.get("smpp_source_npi", 0)),
                source_addr=doc.get("sender_id") or "",
                dest_addr_ton=int(self.provider.get("smpp_dest_ton", 1)),
                dest_addr_npi=int(self.provider.get("smpp_dest_npi", 1)),
                destination_addr=doc.get("to") or "",
                short_message=part,
                data_coding=encoding_flag,
                esm_class=msg_type_flag,
                registered_delivery=1,  # request DLR
            )
            self._pending[pdu.sequence] = doc.get("correlation_token") or doc["id"]

    def _on_message_sent(self, pdu):
        token = self._pending.pop(pdu.sequence, None)
        if not token:
            return
        smsc_id = getattr(pdu, "message_id", None) or ""
        # Update the messages doc
        self.db.messages.update_one(
            {"provider_msg_id": token},
            {"$set": {"status": "sent",
                      "provider_msg_id": smsc_id.decode() if isinstance(smsc_id, bytes) else smsc_id,
                      "sent_at": utcnow_iso()}})
        self.db.smpp_outbox.update_one(
            {"correlation_token": token},
            {"$set": {"status": "submitted", "submitted_at": utcnow_iso(),
                      "smsc_msg_id": smsc_id.decode() if isinstance(smsc_id, bytes) else smsc_id}})

    def _on_message_received(self, pdu):
        # deliver_sm — this is the DLR back-channel
        try:
            short = (pdu.short_message or b"").decode("latin-1", errors="ignore")
        except Exception:
            short = ""
        # SMPP DLR text format (v3.4): "id:... sub:001 dlvrd:001 ... stat:DELIVRD err:000 ..."
        smsc_id = ""
        stat = ""
        err = ""
        for token in short.split():
            if token.startswith("id:"):
                smsc_id = token.split(":", 1)[1]
            elif token.startswith("stat:"):
                stat = token.split(":", 1)[1]
            elif token.startswith("err:"):
                err = token.split(":", 1)[1]
        if not smsc_id:
            return
        new_status = "delivered" if stat in ("DELIVRD", "ACCEPTD") else "failed"
        self.db.messages.update_one(
            {"provider_msg_id": smsc_id},
            {"$set": {"status": new_status,
                      "delivered_at": utcnow_iso(),
                      "error": None if new_status == "delivered" else f"DLR_{stat}_{err}"}})
        self.db.smpp_outbox.update_one(
            {"smsc_msg_id": smsc_id},
            {"$set": {"status": new_status, "dlr_at": utcnow_iso()}})

    def _run_heartbeat(self):
        while not self.stopping:
            try:
                if self.connected and self.client:
                    self.client.send_pdu(smpplib.smpp.make_pdu("enquire_link",
                                                               client=self.client))
                self.db.providers.update_one(
                    {"id": self.id},
                    {"$set": {"last_smpp_heartbeat": utcnow_iso(),
                              "smpp_bind_status": "bound" if self.connected else "down"}})
            except Exception:
                log.exception(f"[{self.name}] heartbeat failure")
                self.connected = False
            time.sleep(HEARTBEAT_SEC)


# ---------------------------------------------------------------------------
# Top-level orchestrator — discovers providers, manages binds.
# ---------------------------------------------------------------------------
class Relay:
    def __init__(self):
        self.db = MongoClient(os.environ["MONGO_URL"])[os.environ["DB_NAME"]]
        self.binds: Dict[str, ProviderBind] = {}
        self._stop = threading.Event()
        signal.signal(signal.SIGINT, self._on_signal)
        signal.signal(signal.SIGTERM, self._on_signal)

    def _on_signal(self, *_):
        log.info("shutting down…")
        self._stop.set()

    def run(self):
        log.info("unitxt SMPP relay started")
        while not self._stop.is_set():
            try:
                self._reconcile()
            except PyMongoError:
                log.exception("mongo error during reconcile")
            self._stop.wait(30)
        for b in self.binds.values():
            b.stop()
        log.info("relay stopped")

    def _reconcile(self):
        target = {}
        for p in self.db.providers.find({"active": True, "transport": "smpp"}):
            if not (p.get("smpp_host") and p.get("smpp_system_id")):
                continue
            target[p["id"]] = p
        # Stop binds that are no longer active
        for pid in list(self.binds.keys()):
            if pid not in target:
                log.info(f"[{pid}] removed — stopping bind")
                self.binds[pid].stop()
                self.binds.pop(pid)
        # Start new binds
        for pid, p in target.items():
            if pid not in self.binds:
                bind = ProviderBind(p, self.db)
                bind.start()
                self.binds[pid] = bind


if __name__ == "__main__":
    Relay().run()
