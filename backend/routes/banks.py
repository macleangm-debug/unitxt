"""Bank accounts registry — admin CRUD + per-client scoped lookup.

Drives the manual bank-transfer top-up flow alongside `routes.topups`.
"""
from typing import Optional
from fastapi import APIRouter, Depends
from pydantic import BaseModel

from server import (
    db, get_current_user, require_roles,
    new_id, iso, now_utc, clean,
)


# ---------- Models ----------
class BankAccountIn(BaseModel):
    country: str  # ISO-2; "*" means global / all countries
    bank_name: str
    account_name: str
    account_number: str
    branch: Optional[str] = ""
    swift: Optional[str] = ""
    instructions: Optional[str] = ""  # plain-English notes shown to clients
    currency: Optional[str] = None
    active: bool = True


# ---------- Admin CRUD ----------
admin_r = APIRouter(prefix="/admin/banks", tags=["bank_accounts"])


@admin_r.get("")
async def list_bank_accounts(_: dict = Depends(require_roles("super_admin"))):
    return await db.bank_accounts.find({}, {"_id": 0}).sort("country", 1).to_list(200)


@admin_r.post("")
async def create_bank_account(body: BankAccountIn,
                                _: dict = Depends(require_roles("super_admin"))):
    doc = {"id": new_id(), **body.model_dump(),
            "country": body.country.upper(),
            "created_at": iso(now_utc())}
    await db.bank_accounts.insert_one(doc)
    return clean(doc)


@admin_r.put("/{bid}")
async def update_bank_account(bid: str, body: BankAccountIn,
                                _: dict = Depends(require_roles("super_admin"))):
    await db.bank_accounts.update_one({"id": bid},
        {"$set": {**body.model_dump(), "country": body.country.upper(),
                   "updated_at": iso(now_utc())}})
    return {"ok": True}


@admin_r.delete("/{bid}")
async def delete_bank_account(bid: str,
                                _: dict = Depends(require_roles("super_admin"))):
    await db.bank_accounts.delete_one({"id": bid})
    return {"ok": True}


# ---------- Public (authed) — clients see banks they can pay into ----------
client_r = APIRouter(prefix="/banks", tags=["banks"])


@client_r.get("")
async def my_banks(user: dict = Depends(get_current_user)):
    """Return active bank accounts for the user's country (plus global accounts)."""
    country = (user.get("country") or "").upper()
    q = {"active": True, "$or": [{"country": country}, {"country": "*"}]} if country \
        else {"active": True, "country": "*"}
    return await db.bank_accounts.find(q, {"_id": 0}).to_list(50)
