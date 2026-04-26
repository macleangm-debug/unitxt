"""User notifications — list, mark all read, mark one read."""
from fastapi import APIRouter, Depends
from server import db, get_current_user

router = APIRouter(prefix="/notifications", tags=["notifications"])


@router.get("")
async def my_notifs(user: dict = Depends(get_current_user)):
    items = await db.notifications.find(
        {"user_id": user["id"]}, {"_id": 0}
    ).sort("created_at", -1).limit(100).to_list(100)
    unread = sum(1 for i in items if not i["read"])
    return {"items": items, "unread": unread}


@router.post("/read-all")
async def mark_all_read(user: dict = Depends(get_current_user)):
    await db.notifications.update_many(
        {"user_id": user["id"], "read": False},
        {"$set": {"read": True}})
    return {"ok": True}


@router.post("/{nid}/read")
async def mark_one_read(nid: str, user: dict = Depends(get_current_user)):
    await db.notifications.update_one(
        {"id": nid, "user_id": user["id"]},
        {"$set": {"read": True}})
    return {"ok": True}
