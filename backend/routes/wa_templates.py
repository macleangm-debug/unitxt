"""WhatsApp templates — client submissions and admin review."""
from typing import Optional
from fastapi import APIRouter, Depends, HTTPException
from pydantic import BaseModel
from server import (
    db, get_current_user, require_roles, add_notification,
    now_utc, iso, new_id, clean,
)


class WaTemplateIn(BaseModel):
    name: str
    body: str
    category: str = "utility"  # utility | marketing | authentication
    language: str = "en"


class WaTemplateReviewIn(BaseModel):
    status: str  # approved | rejected
    note: Optional[str] = None


user_router  = APIRouter(prefix="/wa-templates",       tags=["wa_templates"])
admin_router = APIRouter(prefix="/admin/wa-templates", tags=["wa_templates"])


@user_router.get("")
async def list_wa(user: dict = Depends(get_current_user)):
    return await db.wa_templates.find(
        {"user_id": user["id"]}, {"_id": 0}).sort("created_at", -1).to_list(200)


@user_router.post("")
async def add_wa(body: WaTemplateIn, user: dict = Depends(get_current_user)):
    doc = {"id": new_id(), "user_id": user["id"], **body.model_dump(),
           "status": "pending", "created_at": iso(now_utc())}
    await db.wa_templates.insert_one(doc)
    admins = await db.users.find({"role": "super_admin"}, {"id": 1}).to_list(20)
    for a in admins:
        await add_notification(a["id"], "WhatsApp template submitted",
                               f"{body.name} by {user['email']}", "info")
    return clean(doc)


@user_router.delete("/{tid}")
async def del_wa(tid: str, user: dict = Depends(get_current_user)):
    await db.wa_templates.delete_one({"id": tid, "user_id": user["id"]})
    return {"ok": True}


@admin_router.get("")
async def adm_wa(user: dict = Depends(require_roles("super_admin", "compliance"))):
    return await db.wa_templates.find({}, {"_id": 0}).sort("created_at", -1).to_list(500)


@admin_router.post("/{tid}/review")
async def adm_wa_review(tid: str, body: WaTemplateReviewIn,
                        user: dict = Depends(require_roles("super_admin", "compliance"))):
    if body.status not in ("approved", "rejected"):
        raise HTTPException(400, "Invalid status")
    rec = await db.wa_templates.find_one({"id": tid})
    if not rec:
        raise HTTPException(404)
    await db.wa_templates.update_one({"id": tid}, {"$set": {
        "status": body.status, "review_note": body.note,
        "reviewed_by": user["id"], "reviewed_at": iso(now_utc()),
    }})
    await add_notification(rec["user_id"], f"WhatsApp template {body.status}",
                           f"{rec['name']} {body.status}.",
                           "success" if body.status == "approved" else "warning")
    return {"ok": True}
