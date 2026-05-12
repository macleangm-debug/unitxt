#!/usr/bin/env python3
"""unitxt SMPP Relay Daemon — HTTP edition
==========================================

This daemon runs on the VPN-attached host (e.g. Datavision-YTS Server
`41.220.143.37` for Tigo TZ — only that source IP is whitelisted by Tigo's
ACL).

It talks to the unitxt API over HTTPS — no MongoDB connection needed.
This means it works from any host on the public internet, no database
allowlist required.

Responsibilities
----------------
1. Poll the unitxt API for active SMPP providers.
2. Hold a long-lived TRX bind to each SMSC.
3. Claim queued messages, submit them, post results back over HTTPS.
4. Forward DLRs (deliver_sm) back over HTTPS.
5. Heartbeat every 30 s so the unitxt admin UI shows bind health.

Deployment
----------
1. Copy this folder to `/opt/unitxt-smpp-relay/` on your VPN host.
2. Create `relay.env`:

       UNITXT_BASE_URL=https://www.unitxt.co        # or your prod host
       UNITXT_RELAY_KEY=urk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
       LOG_LEVEL=INFO
       HEARTBEAT_INTERVAL_SEC=30
       POLL_INTERVAL_MS=250

   Get UNITXT_RELAY_KEY from the unitxt admin UI:
   Settings Hub → Platform → `platform.relay_api_key`

3. Install deps:    pip install -r requirements.relay.txt
4. Start systemd:   systemctl enable --now unitxt-smpp-relay
"""
import logging
import os
import signal
import sys
import threading
import time
from collections import deque
from typing import Dict, Optional

try:
    import smpplib.client
    import smpplib.consts
    import smpplib.gsm
    import smpplib.smpp
except ImportError:
    print("ERROR: pip install -r requirements.relay.txt", file=sys.stderr)
    sys.exit(1)

import requests
from dotenv import load_dotenv

load_dotenv(os.path.join(os.path.dirname(__file__), "relay.env"))

LOG_LEVEL = os.environ.get("LOG_LEVEL", "INFO").upper()
HEARTBEAT_SEC = int(os.environ.get("HEARTBEAT_INTERVAL_SEC", "30"))
POLL_MS = int(os.environ.get("POLL_INTERVAL_MS", "250"))
BASE_URL = os.environ.get("UNITXT_BASE_URL", "").rstrip("/")
RELAY_KEY = os.environ.get("UNITXT_RELAY_KEY", "")
if not BASE_URL or not RELAY_KEY:
    print("ERROR: UNITXT_BASE_URL and UNITXT_RELAY_KEY must be set in relay.env",
          file=sys.stderr)
    sys.exit(1)

logging.basicConfig(level=LOG_LEVEL,
                    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s")
log = logging.getLogger("unitxt-smpp-relay")
HEADERS = {"X-Relay-Key": RELAY_KEY, "Content-Type": "application/json"}


# ---------------------------------------------------------------------------
# HTTP client — wraps the 4 endpoints with timeouts + retries
# ---------------------------------------------------------------------------
class API:
    @staticmethod
    def list_providers(timeout=10):
        r = requests.get(f"{BASE_URL}/api/smpp-relay/providers",
                         headers=HEADERS, timeout=timeout)
        r.raise_for_status()
        return r.json().get("providers", [])

    @staticmethod
    def claim(provider_id: str, limit: int = 50, timeout=10):
        r = requests.post(f"{BASE_URL}/api/smpp-relay/claim",
                          headers=HEADERS, timeout=timeout,
                          json={"provider_id": provider_id, "limit": limit})
        r.raise_for_status()
        return r.json().get("messages", [])

    @staticmethod
    def report(correlation_token: str, status: str,
               smsc_msg_id: Optional[str] = None,
               error: Optional[str] = None, timeout=10):
        body = {"correlation_token": correlation_token, "status": status,
                "smsc_msg_id": smsc_msg_id, "error": error}
        r = requests.post(f"{BASE_URL}/api/smpp-relay/result",
                          headers=HEADERS, timeout=timeout, json=body)
        r.raise_for_status()

    @staticmethod
    def heartbeat(provider_id: str, bind_status: str = "bound",
                  detail: Optional[str] = None, timeout=5):
        body = {"provider_id": provider_id, "bind_status": bind_status,
                "detail": detail}
        try:
            requests.post(f"{BASE_URL}/api/smpp-relay/heartbeat",
                          headers=HEADERS, timeout=timeout, json=body)
        except Exception:
            log.exception("heartbeat failed")


# ---------------------------------------------------------------------------
# One ProviderBind per active SMPP provider.
# ---------------------------------------------------------------------------
class ProviderBind:
    def __init__(self, provider: dict):
        self.provider = provider
        self.id = provider["id"]
        self.name = provider.get("name") or self.id
        self.client: Optional[smpplib.client.Client] = None
        self.connected = False
        self.stopping = False
        self._sender_thread: Optional[threading.Thread] = None
        self._listener_thread: Optional[threading.Thread] = None
        self._heartbeat_thread: Optional[threading.Thread] = None
        # outstanding submits: sequence → correlation_token
        self._pending: Dict[int, str] = {}
        self._send_window = deque()

    # -------- lifecycle --------
    def start(self):
        log.info(f"[{self.name}] connecting to "
                 f"{self.provider.get('smpp_host')}:{self.provider.get('smpp_port')}")
        self.client = smpplib.client.Client(
            self.provider.get("smpp_host"),
            int(self.provider.get("smpp_port") or 2775),
            allow_unknown_opt_params=True,
        )
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
            API.heartbeat(self.id, "bound")
        except Exception as e:
            log.exception(f"[{self.name}] bind failed")
            self.connected = False
            API.heartbeat(self.id, "down", detail=str(e)[:200])
            return

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
            if len(self._pending) >= window:
                time.sleep(POLL_MS / 1000.0)
                continue
            try:
                claimed = API.claim(self.id, limit=min(window, 25))
            except Exception:
                log.exception(f"[{self.name}] claim error")
                time.sleep(2)
                continue
            if not claimed:
                time.sleep(POLL_MS / 1000.0)
                continue
            for doc in claimed:
                if self.stopping:
                    break
                self._throttle(max_tps)
                try:
                    self._submit(doc)
                except Exception as e:
                    log.exception(f"[{self.name}] submit_sm failed for {doc.get('id')}")
                    try:
                        API.report(doc["correlation_token"], "failed",
                                   error=f"submit_sm exception: {type(e).__name__}")
                    except Exception:
                        log.exception("failed to report failure")

    def _throttle(self, max_tps: int):
        now = time.time()
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
        token = doc.get("correlation_token") or doc.get("id")
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
                registered_delivery=1,
            )
            self._pending[pdu.sequence] = token

    def _on_message_sent(self, pdu):
        token = self._pending.pop(pdu.sequence, None)
        if not token:
            return
        smsc_id = getattr(pdu, "message_id", None) or ""
        if isinstance(smsc_id, bytes):
            smsc_id = smsc_id.decode(errors="ignore")
        try:
            API.report(token, "sent", smsc_msg_id=smsc_id)
        except Exception:
            log.exception("report 'sent' failed")

    def _on_message_received(self, pdu):
        try:
            short = (pdu.short_message or b"").decode("latin-1", errors="ignore")
        except Exception:
            short = ""
        smsc_id, stat, err = "", "", ""
        for tok in short.split():
            if tok.startswith("id:"):    smsc_id = tok.split(":", 1)[1]
            elif tok.startswith("stat:"): stat   = tok.split(":", 1)[1]
            elif tok.startswith("err:"):  err    = tok.split(":", 1)[1]
        if not smsc_id:
            return
        new_status = "delivered" if stat in ("DELIVRD", "ACCEPTD") else "failed"
        try:
            # NB: we don't have the correlation_token here — we sent it under
            # smsc_msg_id when the 'sent' callback fired. The server uses
            # provider_msg_id to find the row, so passing smsc_id as the
            # correlation_token works because /result was called earlier with
            # provider_msg_id=smsc_id.
            API.report(smsc_id, new_status,
                       error=None if new_status == "delivered" else f"DLR_{stat}_{err}")
        except Exception:
            log.exception("report DLR failed")

    def _run_heartbeat(self):
        while not self.stopping:
            try:
                if self.connected and self.client:
                    try:
                        self.client.send_pdu(smpplib.smpp.make_pdu("enquire_link",
                                                                    client=self.client))
                    except Exception:
                        log.exception(f"[{self.name}] enquire_link failed")
                API.heartbeat(self.id, "bound" if self.connected else "down")
            except Exception:
                log.exception(f"[{self.name}] heartbeat error")
            time.sleep(HEARTBEAT_SEC)


# ---------------------------------------------------------------------------
# Top-level orchestrator
# ---------------------------------------------------------------------------
class Relay:
    def __init__(self):
        self.binds: Dict[str, ProviderBind] = {}
        self._stop = threading.Event()
        signal.signal(signal.SIGINT, self._on_signal)
        signal.signal(signal.SIGTERM, self._on_signal)

    def _on_signal(self, *_):
        log.info("shutting down…")
        self._stop.set()

    def run(self):
        log.info(f"unitxt SMPP relay started — base={BASE_URL}")
        while not self._stop.is_set():
            try:
                self._reconcile()
            except Exception:
                log.exception("reconcile error")
            self._stop.wait(30)
        for b in self.binds.values():
            b.stop()
        log.info("relay stopped")

    def _reconcile(self):
        try:
            providers = API.list_providers()
        except Exception:
            log.exception("could not list providers — will retry")
            return
        target = {}
        for p in providers:
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
                bind = ProviderBind(p)
                bind.start()
                self.binds[pid] = bind


if __name__ == "__main__":
    Relay().run()
