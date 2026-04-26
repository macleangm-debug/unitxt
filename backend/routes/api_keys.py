"""User-level API keys (issued so partners can call our REST API)."""
import secrets
from fastapi import APIRouter, Depends
from pydantic import BaseModel
from server import db, get_current_user, new_id, iso, now_utc, clean


class ApiKeyIn(BaseModel):
    name: str


router = APIRouter(prefix="/api-keys", tags=["api_keys"])


@router.get("")
async def my_keys(user: dict = Depends(get_current_user)):
    return await db.api_keys.find(
        {"user_id": user["id"]}, {"_id": 0}).to_list(50)


@router.post("")
async def create_key(body: ApiKeyIn, user: dict = Depends(get_current_user)):
    doc = {
        "id": new_id(), "user_id": user["id"], "name": body.name,
        "key": "uxk_" + secrets.token_urlsafe(24),
        "active": True, "created_at": iso(now_utc()),
    }
    await db.api_keys.insert_one(doc)
    return clean(doc)


@router.delete("/{kid}")
async def del_key(kid: str, user: dict = Depends(get_current_user)):
    await db.api_keys.delete_one({"id": kid, "user_id": user["id"]})
    return {"ok": True}
