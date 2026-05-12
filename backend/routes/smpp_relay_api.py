"""SMPP Relay HTTP API.

The standalone SMPP relay daemon (running on the VPN-attached host) calls
these endpoints over HTTPS instead of connecting to MongoDB directly.  This
keeps the surface tiny (4 endpoints) and lets the daemon work from anywhere
without any database IP whitelisting.

Auth: shared secret in the `X-Relay-Key` header. The key is stored in
`Settings Hub → Platform → platform.relay_api_key` (auto-generated on first
boot — admins can rotate it).
"""
import secrets
from typing import List, Optional
from fastapi import APIRouter, Depends, Header, HTTPException
from pydantic import BaseModel, Field
from server import db, get_setting, set_setting, now_utc, iso

router = APIRouter(prefix="/smpp-relay", tags=["smpp_relay"])


async def _require_relay_key(x_relay_key: Optional[str] = Header(None)):
    expected = await get_setting("platform.relay_api_key", None)
    if not expected:
        # Bootstrap on first call: generate + persist so the admin sees it
        # in the Settings Hub.  (Race-free enough — the relay won't call us
        # before the admin reads the key, but if it does the relay daemon
        # just receives 401 and retries with a fresh key once configured.)
        expected = "urk_" + secrets.token_urlsafe(32)
        await set_setting("platform.relay_api_key", expected)
    if not x_relay_key or x_relay_key != expected:
        raise HTTPException(401, "Invalid or missing X-Relay-Key header")


# ---------- request / response shapes ----------
class ClaimIn(BaseModel):
    provider_id: str
    limit: int = Field(default=50, ge=1, le=500)


class HeartbeatIn(BaseModel):
    provider_id: str
    bind_status: str = "bound"     # bound | down | binding
    detail: Optional[str] = None


class ResultIn(BaseModel):
    correlation_token: str         # the synthetic msg id we returned at queue time
    status: str                    # "sent" | "delivered" | "failed"
    smsc_msg_id: Optional[str] = None
    error: Optional[str] = None


# ---------- endpoints ----------
@router.get("/providers", dependencies=[Depends(_require_relay_key)])
async def list_smpp_providers():
    """Return all active SMPP providers (so the daemon knows which binds to hold)."""
    rows = await db.providers.find(
        {"active": True, "transport": "smpp"},
        {"_id": 0,
         "id": 1, "name": 1,
         "smpp_host": 1, "smpp_port": 1, "smpp_system_id": 1,
         "smpp_password": 1, "smpp_system_type": 1, "smpp_bind_mode": 1,
         "smpp_use_tls": 1, "smpp_source_ton": 1, "smpp_source_npi": 1,
         "smpp_dest_ton": 1, "smpp_dest_npi": 1,
         "smpp_throughput_per_sec": 1, "smpp_window_size": 1,
         }).to_list(50)
    return {"providers": rows}


@router.post("/claim", dependencies=[Depends(_require_relay_key)])
async def claim_messages(body: ClaimIn):
    """Atomically claim up to `limit` queued messages for this provider.

    Each claim flips a row from status=queued to status=sending so concurrent
    relays don't double-submit.  Returns the message payloads the daemon needs
    to call submit_sm with.
    """
    claimed = []
    for _ in range(body.limit):
        doc = await db.smpp_outbox.find_one_and_update(
            {"provider_id": body.provider_id, "status": "queued"},
            {"$set": {"status": "sending", "claimed_at": iso(now_utc())},
             "$inc": {"attempts": 1}},
            sort=[("created_at", 1)],
            projection={"_id": 0,
                        "id": 1, "correlation_token": 1, "to": 1,
                        "sender_id": 1, "body": 1, "channel": 1},
        )
        if not doc:
            break
        claimed.append(doc)
    return {"messages": claimed}


@router.post("/result", dependencies=[Depends(_require_relay_key)])
async def post_result(body: ResultIn):
    """Update message + outbox state after the daemon submits or receives DLR."""
    msg_update = {
        "status": body.status,
        "error":  body.error,
    }
    if body.status == "sent":
        msg_update["sent_at"] = iso(now_utc())
        if body.smsc_msg_id:
            msg_update["provider_msg_id"] = body.smsc_msg_id
    elif body.status == "delivered":
        msg_update["delivered_at"] = iso(now_utc())
    await db.messages.update_one(
        {"provider_msg_id": body.correlation_token},
        {"$set": msg_update})
    outbox_update = {
        "status": "submitted" if body.status == "sent" else body.status,
        "last_error": body.error,
    }
    if body.status == "sent" and body.smsc_msg_id:
        outbox_update["smsc_msg_id"] = body.smsc_msg_id
        outbox_update["submitted_at"] = iso(now_utc())
    if body.status == "delivered":
        outbox_update["dlr_at"] = iso(now_utc())
    await db.smpp_outbox.update_one(
        {"correlation_token": body.correlation_token},
        {"$set": outbox_update})
    return {"ok": True}


@router.post("/heartbeat", dependencies=[Depends(_require_relay_key)])
async def heartbeat(body: HeartbeatIn):
    await db.providers.update_one(
        {"id": body.provider_id},
        {"$set": {"last_smpp_heartbeat": iso(now_utc()),
                  "smpp_bind_status": body.bind_status,
                  "smpp_bind_detail": body.detail}})
    return {"ok": True}
