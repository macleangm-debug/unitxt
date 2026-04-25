"""Affiliate program — promo-code-driven commission engine.

Three configurable commission models (admin picks one in Settings Hub):
  • time_window   (default A) — earn % on every paid top-up made within N months of signup
  • first_n_topups (B) — earn % on the first N paid top-ups, no time cap
  • tier_bonus     (C) — earn % on first top-up + USD bonuses when referee hits spend tiers

Public surface (mounted under /api):
  GET  /affiliate/me                      — config + my stats summary
  GET  /affiliate/codes                   — list my promo codes
  POST /affiliate/codes                   — create a new code
  DELETE /affiliate/codes/{id}            — disable / delete a code
  GET  /affiliate/referrals               — my referred users + their lifetime spend
  GET  /affiliate/earnings                — my commission ledger
  GET  /affiliate/payouts                 — my payout requests
  POST /affiliate/payouts                 — request a payout
  GET  /admin/affiliate/payouts           — pending payouts (admin)
  POST /admin/affiliate/payouts/{id}/review — approve/reject (admin)
"""
from datetime import datetime, timedelta
from typing import List, Optional
from fastapi import APIRouter, Depends, HTTPException
from pydantic import BaseModel, Field

from server import (
    db, get_current_user, require_roles,
    new_id, iso, now_utc, clean,
    add_audit, add_notification, get_setting,
)

import secrets


# ---------- Models ----------
class CodeIn(BaseModel):
    code: str = Field(min_length=3, max_length=24)
    note: Optional[str] = ""
    active: bool = True


class PayoutRequestIn(BaseModel):
    method: str = Field(default="bank")  # bank | mobile_money | crypto
    payout_details: dict = {}            # account #, mpesa #, wallet addr, etc.
    note: Optional[str] = ""


class PayoutReviewIn(BaseModel):
    status: str   # approved | rejected
    note: Optional[str] = ""


# ---------- Helpers ----------
async def affiliate_config():
    """Pulls the 7 affiliate Settings Hub keys (with sensible defaults)."""
    return {
        "model": str(await get_setting("affiliate.model", "time_window")),
        "commission_pct": float(await get_setting("affiliate.commission_pct", 10)),
        "window_months": int(await get_setting("affiliate.window_months", 6)),
        "first_n": int(await get_setting("affiliate.first_n", 3)),
        "tier_first_pct": float(await get_setting("affiliate.tier_first_pct", 15)),
        "tier_bonus_1_threshold_usd": float(await get_setting("affiliate.tier_bonus_1_threshold_usd", 200)),
        "tier_bonus_1_amount_usd":    float(await get_setting("affiliate.tier_bonus_1_amount_usd", 20)),
        "tier_bonus_2_threshold_usd": float(await get_setting("affiliate.tier_bonus_2_threshold_usd", 1000)),
        "tier_bonus_2_amount_usd":    float(await get_setting("affiliate.tier_bonus_2_amount_usd", 50)),
        "tier_window_months":         int(await get_setting("affiliate.tier_window_months", 12)),
        "welcome_bonus_pct":          float(await get_setting("affiliate.welcome_bonus_pct", 5)),
        "welcome_bonus_max_credits":  int(await get_setting("affiliate.welcome_bonus_max_credits", 500)),
        "payout_threshold_usd":       float(await get_setting("affiliate.payout_threshold_usd", 50)),
        "active":                     bool(await get_setting("affiliate.active", True)),
    }


async def find_owner_by_code(code: str) -> Optional[dict]:
    """Resolve a promo / affiliate code to its owning affiliate user."""
    if not code:
        return None
    code_u = code.upper().strip()
    # 1) Affiliate-specific code (preferred)
    ac = await db.affiliate_codes.find_one({"code": code_u, "active": True})
    if ac:
        return await db.users.find_one({"id": ac["owner_user_id"]})
    # 2) Legacy: a user's referral_code
    return await db.users.find_one({"referral_code": code_u})


async def referee_paid_topup_count(referee_id: str) -> int:
    return await db.affiliate_earnings.count_documents({
        "referred_user_id": referee_id,
        "kind": "topup",
    })


async def referee_total_spend_usd(referee_id: str) -> float:
    """Sum of price_usd across all that user's paid top-ups (mock buy + bank approve)."""
    cur = db.platform_payments.aggregate([
        {"$match": {"user_id": referee_id}},
        {"$group": {"_id": None, "t": {"$sum": "$price_usd"}}},
    ])
    rows = await cur.to_list(1)
    return float(rows[0]["t"]) if rows else 0.0


async def record_topup_commission(referee: dict, amount_usd: float, ref: str):
    """Calculate + persist commission for a referee's paid top-up.

    Called by `credits/buy` (mock pay) and by `admin/topups/{id}/review`
    (bank-transfer approve). idempotent on `ref`.
    """
    cfg = await affiliate_config()
    if not cfg["active"]:
        return None

    affiliate_id = referee.get("referred_by")
    if not affiliate_id:
        return None
    affiliate = await db.users.find_one({"id": affiliate_id})
    if not affiliate:
        return None

    # idempotency
    if await db.affiliate_earnings.find_one({"source_ref": ref, "affiliate_id": affiliate_id}):
        return None

    earned_usd = 0.0
    bonus_payouts: List[dict] = []
    model = cfg["model"]
    signup_at = referee.get("created_at")
    if isinstance(signup_at, str):
        signup_dt = datetime.fromisoformat(signup_at.replace("Z", "+00:00"))
    else:
        signup_dt = now_utc()

    if model == "time_window":
        cutoff = signup_dt + timedelta(days=cfg["window_months"] * 30)
        if now_utc() < cutoff:
            earned_usd = round(amount_usd * cfg["commission_pct"] / 100.0, 4)

    elif model == "first_n":
        prior = await referee_paid_topup_count(referee["id"])
        if prior < cfg["first_n"]:
            earned_usd = round(amount_usd * cfg["commission_pct"] / 100.0, 4)

    elif model == "tier_bonus":
        # Always: % on the FIRST paid top-up only
        prior = await referee_paid_topup_count(referee["id"])
        if prior == 0:
            earned_usd = round(amount_usd * cfg["tier_first_pct"] / 100.0, 4)
        # Plus tier bonuses if total spend crosses threshold WITHIN tier_window_months
        cutoff = signup_dt + timedelta(days=cfg["tier_window_months"] * 30)
        if now_utc() < cutoff:
            total = await referee_total_spend_usd(referee["id"]) + amount_usd
            for tier in (1, 2):
                threshold = cfg[f"tier_bonus_{tier}_threshold_usd"]
                amount = cfg[f"tier_bonus_{tier}_amount_usd"]
                already = await db.affiliate_earnings.find_one({
                    "affiliate_id": affiliate_id,
                    "referred_user_id": referee["id"],
                    "kind": f"tier_bonus_{tier}",
                })
                if total >= threshold and not already:
                    bonus_payouts.append({
                        "amount_usd": amount,
                        "kind": f"tier_bonus_{tier}",
                        "note": f"Tier {tier} bonus: ${threshold:.0f}+ in {cfg['tier_window_months']}mo",
                    })

    # Persist primary commission row
    if earned_usd > 0:
        await db.affiliate_earnings.insert_one({
            "id": new_id(),
            "affiliate_id": affiliate_id,
            "referred_user_id": referee["id"],
            "referred_email": referee.get("email"),
            "kind": "topup",
            "model_used": model,
            "amount_usd": earned_usd,
            "topup_amount_usd": amount_usd,
            "source_ref": ref,
            "status": "earned",  # earned -> requested -> paid
            "created_at": iso(now_utc()),
        })
        await add_notification(affiliate_id, "Affiliate commission earned",
            f"+${earned_usd:.2f} from {referee.get('email')} top-up.", "success")

    # Persist tier bonuses
    for b in bonus_payouts:
        await db.affiliate_earnings.insert_one({
            "id": new_id(),
            "affiliate_id": affiliate_id,
            "referred_user_id": referee["id"],
            "referred_email": referee.get("email"),
            "kind": b["kind"],
            "model_used": model,
            "amount_usd": b["amount_usd"],
            "source_ref": f"{ref}:{b['kind']}",
            "status": "earned",
            "created_at": iso(now_utc()),
            "note": b["note"],
        })
        await add_notification(affiliate_id, "Affiliate tier bonus",
            f"+${b['amount_usd']:.2f} — {b['note']}", "success")

    return {"earned_usd": earned_usd, "bonuses": bonus_payouts}


# ---------- Self-service routes ----------
self_r = APIRouter(prefix="/affiliate", tags=["affiliate"])


@self_r.get("/me")
async def my_overview(user: dict = Depends(get_current_user)):
    cfg = await affiliate_config()
    referred = await db.users.count_documents({"referred_by": user["id"]})
    earned = await db.affiliate_earnings.aggregate([
        {"$match": {"affiliate_id": user["id"]}},
        {"$group": {"_id": "$status", "t": {"$sum": "$amount_usd"}}},
    ]).to_list(10)
    by_status = {r["_id"]: round(r["t"], 4) for r in earned}
    return {
        "config": cfg,
        "referrals": referred,
        "earned_usd": by_status.get("earned", 0),
        "requested_usd": by_status.get("requested", 0),
        "paid_usd": by_status.get("paid", 0),
        "available_usd": by_status.get("earned", 0),
        "default_code": user.get("referral_code"),
    }


@self_r.get("/codes")
async def list_my_codes(user: dict = Depends(get_current_user)):
    rows = await db.affiliate_codes.find(
        {"owner_user_id": user["id"]}, {"_id": 0}
    ).sort("created_at", -1).to_list(50)
    return rows


@self_r.post("/codes")
async def create_code(body: CodeIn, user: dict = Depends(get_current_user)):
    code = body.code.strip().upper()
    if len(code) > 24:
        raise HTTPException(400, "Code must be 24 characters or less.")
    if await db.affiliate_codes.find_one({"code": code}):
        raise HTTPException(400, f"Code '{code}' is already taken.")
    if await db.users.find_one({"referral_code": code}) or await db.promotions.find_one({"code": code}):
        raise HTTPException(400, f"Code '{code}' clashes with an existing code.")
    # Cap codes per affiliate
    cap = int(await get_setting("affiliate.max_codes_per_user", 5))
    have = await db.affiliate_codes.count_documents({"owner_user_id": user["id"]})
    if have >= cap:
        raise HTTPException(400, f"You can only have up to {cap} codes.")
    doc = {
        "id": new_id(), "code": code, "note": body.note, "active": body.active,
        "owner_user_id": user["id"],
        "created_at": iso(now_utc()),
        "uses": 0,
    }
    await db.affiliate_codes.insert_one(doc)
    return clean(doc)


@self_r.delete("/codes/{cid}")
async def delete_code(cid: str, user: dict = Depends(get_current_user)):
    r = await db.affiliate_codes.delete_one({"id": cid, "owner_user_id": user["id"]})
    if r.deleted_count == 0:
        raise HTTPException(404)
    return {"ok": True}


@self_r.get("/referrals")
async def my_referrals(user: dict = Depends(get_current_user)):
    users = await db.users.find(
        {"referred_by": user["id"]},
        {"_id": 0, "id": 1, "email": 1, "name": 1, "country": 1, "created_at": 1}
    ).sort("created_at", -1).to_list(500)
    out = []
    for u in users:
        spend = await referee_total_spend_usd(u["id"])
        topups = await referee_paid_topup_count(u["id"])
        earnings = await db.affiliate_earnings.aggregate([
            {"$match": {"affiliate_id": user["id"], "referred_user_id": u["id"]}},
            {"$group": {"_id": None, "t": {"$sum": "$amount_usd"}}},
        ]).to_list(1)
        out.append({
            **u,
            "spend_usd": round(spend, 2),
            "topup_count": topups,
            "earned_from_user_usd": round(earnings[0]["t"], 4) if earnings else 0.0,
        })
    return out


@self_r.get("/earnings")
async def my_earnings(user: dict = Depends(get_current_user)):
    return await db.affiliate_earnings.find(
        {"affiliate_id": user["id"]}, {"_id": 0}
    ).sort("created_at", -1).limit(500).to_list(500)


@self_r.get("/payouts")
async def my_payouts(user: dict = Depends(get_current_user)):
    return await db.affiliate_payouts.find(
        {"affiliate_id": user["id"]}, {"_id": 0}
    ).sort("created_at", -1).to_list(200)


@self_r.post("/payouts")
async def request_payout(body: PayoutRequestIn, user: dict = Depends(get_current_user)):
    cfg = await affiliate_config()
    available = await db.affiliate_earnings.aggregate([
        {"$match": {"affiliate_id": user["id"], "status": "earned"}},
        {"$group": {"_id": None, "t": {"$sum": "$amount_usd"}}},
    ]).to_list(1)
    avail = round(float(available[0]["t"]), 4) if available else 0.0
    if avail < cfg["payout_threshold_usd"]:
        raise HTTPException(400,
            f"You need at least ${cfg['payout_threshold_usd']:.0f} earned to request a payout. "
            f"You currently have ${avail:.2f}.")
    rows = await db.affiliate_earnings.find(
        {"affiliate_id": user["id"], "status": "earned"}, {"_id": 0, "id": 1}
    ).to_list(5000)
    earning_ids = [r["id"] for r in rows]
    doc = {
        "id": new_id(), "affiliate_id": user["id"],
        "amount_usd": avail, "method": body.method,
        "payout_details": body.payout_details or {},
        "note": body.note or "",
        "earning_ids": earning_ids,
        "status": "pending",
        "created_at": iso(now_utc()),
    }
    await db.affiliate_payouts.insert_one(doc)
    await db.affiliate_earnings.update_many(
        {"id": {"$in": earning_ids}},
        {"$set": {"status": "requested"}}
    )
    return clean(doc)


# ---------- Admin routes ----------
admin_r = APIRouter(prefix="/admin/affiliate", tags=["admin_affiliate"])


@admin_r.get("/overview")
async def admin_overview(_: dict = Depends(require_roles("super_admin"))):
    cfg = await affiliate_config()
    affiliates = await db.users.count_documents({"role": {"$in": ["reseller", "affiliate"]}})
    refs = await db.users.count_documents({"referred_by": {"$ne": None}})
    earnings = await db.affiliate_earnings.aggregate([
        {"$group": {"_id": "$status", "t": {"$sum": "$amount_usd"}, "n": {"$sum": 1}}},
    ]).to_list(10)
    by = {r["_id"]: {"usd": round(r["t"], 2), "n": r["n"]} for r in earnings}
    return {
        "config": cfg,
        "affiliates_count": affiliates,
        "referred_users_count": refs,
        "earnings": by,
    }


@admin_r.get("/payouts")
async def list_payouts(status: Optional[str] = None,
                          _: dict = Depends(require_roles("super_admin"))):
    q = {"status": status} if status else {}
    rows = await db.affiliate_payouts.find(q, {"_id": 0}).sort("created_at", -1).to_list(500)
    for r in rows:
        u = await db.users.find_one({"id": r["affiliate_id"]},
                                       {"_id": 0, "email": 1, "name": 1})
        r["affiliate"] = u or {}
    return rows


@admin_r.post("/payouts/{pid}/review")
async def review_payout(pid: str, body: PayoutReviewIn,
                          admin: dict = Depends(require_roles("super_admin"))):
    if body.status not in ("approved", "rejected"):
        raise HTTPException(400, "Invalid status")
    p = await db.affiliate_payouts.find_one({"id": pid})
    if not p:
        raise HTTPException(404)
    if p["status"] != "pending":
        raise HTTPException(400, "Already reviewed.")
    update = {"status": body.status, "reviewed_at": iso(now_utc()),
                "reviewed_by": admin["id"], "review_note": body.note or ""}
    await db.affiliate_payouts.update_one({"id": pid}, {"$set": update})
    new_status = "paid" if body.status == "approved" else "earned"
    await db.affiliate_earnings.update_many(
        {"id": {"$in": p.get("earning_ids", [])}},
        {"$set": {"status": new_status}}
    )
    msg = (f"Your payout of ${p['amount_usd']:.2f} has been approved and is on the way."
            if body.status == "approved"
            else f"Your payout request was rejected. {body.note or ''}")
    await add_notification(p["affiliate_id"],
        "Payout " + ("approved" if body.status == "approved" else "rejected"),
        msg, "success" if body.status == "approved" else "warning")
    await add_audit(admin["id"], f"affiliate.payout.{body.status}",
                     target=pid, meta={"amount_usd": p["amount_usd"]})
    return {"ok": True}
