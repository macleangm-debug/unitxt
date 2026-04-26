"""User profile — streak overview + DLR webhook configuration."""
from typing import Optional
from fastapi import APIRouter, Depends
from pydantic import BaseModel
from server import db, get_current_user, get_setting

router = APIRouter(prefix="/profile", tags=["profile"])


class WebhookIn(BaseModel):
    dlr_webhook_url: Optional[str] = ""
    dlr_webhook_secret: Optional[str] = ""


@router.get("/streak")
async def my_streak(user: dict = Depends(get_current_user)):
    return {"streak": int(user.get("send_streak_days", 0) or 0),
            "last_send_date": user.get("last_send_date"),
            "bonuses": {
                "7":  await get_setting("streak.7_day_bonus",   100),
                "30": await get_setting("streak.30_day_bonus", 1000),
                "90": await get_setting("streak.90_day_bonus", 5000),
            }}


@router.put("/webhook")
async def set_webhook(body: WebhookIn, user: dict = Depends(get_current_user)):
    await db.users.update_one({"id": user["id"]}, {"$set": {
        "dlr_webhook_url":    body.dlr_webhook_url or None,
        "dlr_webhook_secret": body.dlr_webhook_secret or None,
    }})
    return {"ok": True}


@router.get("/webhook")
async def get_webhook(user: dict = Depends(get_current_user)):
    return {"dlr_webhook_url":    user.get("dlr_webhook_url") or "",
            "dlr_webhook_secret": user.get("dlr_webhook_secret") or ""}
