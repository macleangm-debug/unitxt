"""Opt-out / DND list management.

Public-ish:
  POST /optout/inbound          — provider posts STOP/UNSUB SMS here, phone
                                  is auto-added.  No auth (provider IPs only).
Authenticated:
  GET  /optout                  — current user's view of their opt-outs
                                  (admins see all)
  POST /optout                  — manually add a phone (admin only)
  DELETE /optout/{phone}        — remove a phone (admin only)
"""
from typing import Optional
from fastapi import APIRouter, Depends, HTTPException
from pydantic import BaseModel
from server import db, get_current_user, require_roles, now_utc, iso

router = APIRouter(prefix="/optout", tags=["compliance"])


# Phrases that, when received via inbound SMS, opt the sender out
STOP_KEYWORDS = ("STOP", "UNSUBSCRIBE", "UNSUB", "OPTOUT", "OPT-OUT", "CANCEL")


class OptOutIn(BaseModel):
    phone: str
    reason: Optional[str] = "manual"


class InboundIn(BaseModel):
    from_phone: str           # E.164 phone of the person texting in
    body: str                  # SMS text
    provider_id: Optional[str] = None


@router.get("")
async def list_opt_outs(limit: int = 200,
                       user: dict = Depends(get_current_user)):
    if user.get("role") not in ("super_admin", "compliance"):
        # non-admins see only their own additions
        rows = await db.opt_outs.find(
            {"added_by": user["id"]}, {"_id": 0}
        ).sort("added_at", -1).limit(limit).to_list(limit)
    else:
        rows = await db.opt_outs.find(
            {}, {"_id": 0}).sort("added_at", -1).limit(limit).to_list(limit)
    return {"items": rows, "count": await db.opt_outs.estimated_document_count()}


@router.post("")
async def add_opt_out(body: OptOutIn,
                      admin: dict = Depends(require_roles("super_admin", "compliance"))):
    phone = body.phone.strip()
    if not phone:
        raise HTTPException(400, "Phone is required")
    await db.opt_outs.update_one(
        {"phone": phone},
        {"$set": {"phone": phone, "reason": body.reason or "manual",
                  "added_at": iso(now_utc()), "added_by": admin["id"]}},
        upsert=True)
    return {"ok": True, "phone": phone}


@router.delete("/{phone:path}")
async def del_opt_out(phone: str,
                      admin: dict = Depends(require_roles("super_admin", "compliance"))):
    res = await db.opt_outs.delete_one({"phone": phone})
    return {"ok": True, "deleted": res.deleted_count}


@router.post("/inbound")
async def inbound_sms(body: InboundIn):
    """Auto-opt-out endpoint — providers POST inbound SMS here.

    Any message whose body STARTS with a STOP keyword adds the sender phone
    to the opt-out list.
    """
    txt = (body.body or "").strip().upper()
    first_word = txt.split()[0] if txt else ""
    if first_word in STOP_KEYWORDS:
        await db.opt_outs.update_one(
            {"phone": body.from_phone},
            {"$set": {"phone": body.from_phone, "reason": "STOP keyword",
                      "added_at": iso(now_utc()), "added_by": "system",
                      "provider_id": body.provider_id}},
            upsert=True)
        return {"ok": True, "opted_out": True}
    # log inbound for audit even if not STOP
    await db.inbound_messages.insert_one({
        "from_phone": body.from_phone, "body": body.body or "",
        "provider_id": body.provider_id, "received_at": iso(now_utc()),
    })
    return {"ok": True, "opted_out": False}
