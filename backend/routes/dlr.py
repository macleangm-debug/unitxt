"""Delivery-Receipt webhooks — providers POST status updates here.

In production add signature verification per provider before applying the
update.  Today the route trusts the provider_id path param.
"""
from typing import Optional
from fastapi import APIRouter
from pydantic import BaseModel
from server import db, now_utc, iso

router = APIRouter(prefix="/dlr", tags=["dlr"])


class DlrIn(BaseModel):
    provider_msg_id: str
    status: str  # delivered | failed | undelivered | expired
    error: Optional[str] = None


@router.post("/{provider_id}")
async def dlr_update(provider_id: str, body: DlrIn):
    res = await db.messages.update_one(
        {"provider_id": provider_id, "provider_msg_id": body.provider_msg_id},
        {"$set": {"status": body.status, "error": body.error,
                  "delivered_at": iso(now_utc())}})
    return {"updated": res.modified_count}
