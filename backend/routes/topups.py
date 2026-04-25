"""Bank-transfer top-up flow.

Clients submit top-up requests with a base64 payment proof.
Admins/finance review; approval credits the client's wallet.
"""
from typing import Optional
from fastapi import APIRouter, Depends, HTTPException
from pydantic import BaseModel

from server import (
    db, get_current_user, require_roles,
    new_id, iso, now_utc, clean,
    adjust_wallet, add_audit, add_notification,
    convert_usd_to_local, get_setting,
)


# ---------- Models ----------
class TopupRequestIn(BaseModel):
    pack_id: Optional[str] = None
    amount_usd: Optional[float] = None
    method: str = "bank_transfer"
    bank_id: Optional[str] = None
    reference: Optional[str] = ""
    note: Optional[str] = ""
    proof_image: Optional[str] = None  # base64 data URL of receipt
    promo_code: Optional[str] = None


class TopupReviewIn(BaseModel):
    status: str  # approved | rejected
    note: Optional[str] = ""


# ---------- Client side ----------
client_r = APIRouter(prefix="/wallet/topups", tags=["topups"])


@client_r.post("")
async def submit_topup(body: TopupRequestIn, user: dict = Depends(get_current_user)):
    credits = 0
    usd = 0.0
    pack_name = None
    if body.pack_id:
        pack = await db.credit_packs.find_one({"id": body.pack_id, "active": True})
        if not pack:
            raise HTTPException(404, "Pack not found or inactive.")
        usd = float(pack["price_usd"])
        credits = int(pack["credits"])
        pack_name = pack["name"]
    elif body.amount_usd and body.amount_usd > 0:
        usd = float(body.amount_usd)
        usd_per_credit = float(await get_setting("economy.usd_per_credit", 0.01) or 0.01)
        credits = int(round(usd / usd_per_credit))
    else:
        raise HTTPException(400, "Pick a pack or enter an amount.")

    local = await convert_usd_to_local(user.get("country"), usd)
    if body.proof_image:
        max_kb = int(await get_setting("topups.max_proof_kb", 4096) or 4096)
        size_kb = len(body.proof_image.encode()) / 1024
        if size_kb > max_kb:
            raise HTTPException(400,
                f"Proof image is too big (max ~{max_kb / 1024:.1f}MB). Compress it and retry.")

    doc = {
        "id": new_id(), "user_id": user["id"], "user_email": user["email"],
        "user_country": user.get("country"),
        "pack_id": body.pack_id, "pack_name": pack_name,
        "method": body.method, "bank_id": body.bank_id,
        "reference": body.reference or "", "note": body.note or "",
        "promo_code": body.promo_code or "",
        "amount_usd": round(usd, 4),
        "local_amount": local["rounded"],
        "local_currency": local["currency"],
        "fx_rate_used": local["fx_rate"],
        "credits_on_approval": credits,
        "proof_image": body.proof_image or "",
        "status": "pending",
        "created_at": iso(now_utc()),
    }
    await db.topup_requests.insert_one(doc)
    await add_notification(user["id"], "Top-up request submitted",
        f"We received your {local['currency']} {local['rounded']:.0f} top-up request. "
        f"You'll get {credits:,} credits once admin verifies your payment.",
        "info")
    d = dict(doc)
    d.pop("proof_image", None)
    return clean(d)


@client_r.get("")
async def my_topups(user: dict = Depends(get_current_user)):
    return await db.topup_requests.find(
        {"user_id": user["id"]},
        {"_id": 0, "proof_image": 0}
    ).sort("created_at", -1).limit(100).to_list(100)


@client_r.get("/{tid}/proof")
async def my_topup_proof(tid: str, user: dict = Depends(get_current_user)):
    r = await db.topup_requests.find_one({"id": tid, "user_id": user["id"]}, {"_id": 0})
    if not r:
        raise HTTPException(404)
    return {"id": r["id"], "proof_image": r.get("proof_image") or ""}


# ---------- Admin / finance side ----------
admin_r = APIRouter(prefix="/admin/topups", tags=["admin_topups"])


@admin_r.get("")
async def list_topups(status: Optional[str] = None,
                       _: dict = Depends(require_roles("super_admin", "finance"))):
    q = {"status": status} if status else {}
    return await db.topup_requests.find(q, {"_id": 0, "proof_image": 0}).sort(
        "created_at", -1).limit(500).to_list(500)


@admin_r.get("/{tid}")
async def topup_detail(tid: str,
                         _: dict = Depends(require_roles("super_admin", "finance"))):
    r = await db.topup_requests.find_one({"id": tid}, {"_id": 0})
    if not r:
        raise HTTPException(404)
    return r


@admin_r.post("/{tid}/review")
async def review_topup(tid: str, body: TopupReviewIn,
                         admin: dict = Depends(require_roles("super_admin", "finance"))):
    if body.status not in ("approved", "rejected"):
        raise HTTPException(400, "Invalid status")
    r = await db.topup_requests.find_one({"id": tid})
    if not r:
        raise HTTPException(404)
    if r["status"] != "pending":
        raise HTTPException(400, "Already reviewed")
    update = {"status": body.status, "reviewed_at": iso(now_utc()),
               "reviewed_by": admin["id"], "review_note": body.note or ""}
    await db.topup_requests.update_one({"id": tid}, {"$set": update})

    if body.status == "approved":
        credits = int(r.get("credits_on_approval", 0))
        bonus = 0
        if r.get("promo_code"):
            promo = await db.promotions.find_one({"code": r["promo_code"].upper(),
                                                    "active": True})
            if promo and float(r.get("amount_usd", 0)) >= float(promo.get("min_topup", 0)):
                if promo.get("country") in (None, r.get("user_country")):
                    if promo["type"] == "bonus_credit":
                        bonus = int(float(promo["value"]) * 100)
                    elif promo["type"] == "percent_discount":
                        bonus = int(credits * float(promo["value"]) / 100.0)
        total = credits + bonus
        if total > 0:
            await adjust_wallet(r["user_id"], total, "topup_bank",
                                 note=f"Bank transfer · {r['local_currency']} "
                                      f"{r['local_amount']:.0f} · ref {r.get('reference') or '—'}",
                                 ref=tid)
        await add_notification(r["user_id"], "Top-up approved",
            f"+{total:,} credits added to your wallet "
            f"({credits:,} credits" +
            (f" + {bonus:,} promo bonus" if bonus else "") + ").",
            "success")
    else:
        await add_notification(r["user_id"], "Top-up rejected",
            body.note or "Please contact support.",
            "warning")
    await add_audit(admin["id"], f"topup.{body.status}", target=tid, meta={"note": body.note})
    return {"ok": True}
