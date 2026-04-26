"""Mobile-prefix registry — operator detection by phone-number prefix."""
from typing import Any, Dict, List, Optional
from fastapi import APIRouter, Depends
from pydantic import BaseModel
from server import (
    db, get_current_user, require_roles,
    new_id, iso, now_utc, clean, phone_to_operator,
)


class PrefixIn(BaseModel):
    country: str
    operator: str
    prefix: str
    active: bool = True


router = APIRouter(prefix="/prefixes", tags=["prefixes"])


@router.get("")
async def list_prefixes(country: Optional[str] = None,
                          _: dict = Depends(get_current_user)):
    q = {"country": country} if country else {}
    return await db.mobile_prefixes.find(q, {"_id": 0}).sort("prefix", 1).to_list(5000)


@router.post("")
async def add_prefix(body: PrefixIn, _: dict = Depends(require_roles("super_admin"))):
    doc = {"id": new_id(), **body.model_dump(), "created_at": iso(now_utc())}
    await db.mobile_prefixes.insert_one(doc)
    return clean(doc)


@router.patch("/{pid}")
async def update_prefix(pid: str, body: Dict[str, Any],
                          _: dict = Depends(require_roles("super_admin"))):
    await db.mobile_prefixes.update_one({"id": pid}, {"$set": body})
    return {"ok": True}


@router.delete("/{pid}")
async def del_prefix(pid: str, _: dict = Depends(require_roles("super_admin"))):
    await db.mobile_prefixes.delete_one({"id": pid})
    return {"ok": True}


@router.post("/import")
async def import_prefixes(rows: List[PrefixIn],
                            _: dict = Depends(require_roles("super_admin"))):
    docs = [{"id": new_id(), **r.model_dump(), "created_at": iso(now_utc())} for r in rows]
    if docs:
        await db.mobile_prefixes.insert_many(docs)
    return {"ok": True, "count": len(docs)}


@router.get("/lookup")
async def lookup(phone: str, _: dict = Depends(get_current_user)):
    match = await phone_to_operator(phone)
    return {"match": match}
