"""Client-facing credit pack endpoints — list, rates, buy, recover."""
from typing import Optional
from fastapi import APIRouter, Depends, HTTPException
from pydantic import BaseModel, Field
from server import (
    db, get_current_user, get_setting, adjust_wallet, get_or_create_wallet,
    add_notification, now_utc, iso, new_id,
)

router = APIRouter(prefix="/credits", tags=["credits"])


class BuyPackIn(BaseModel):
    pack_id: str
    promo_code: Optional[str] = None


@router.get("/packs")
async def list_packs(user: dict = Depends(get_current_user)):
    """Return packs visible to this user (global + country-scoped) enriched
    with local-currency pricing.  Country-scoped first."""
    user_country = user.get("country")
    q = {"active": True, "$or": [{"country": None}, {"country": {"$exists": False}}]}
    if user_country:
        q["$or"].append({"country": user_country})
    items = await db.credit_packs.find(q, {"_id": 0}).sort([
        ("country", -1), ("credits", 1)]).to_list(80)
    for p in items:
        c = await db.countries.find_one({"code": user_country}, {"_id": 0}) if user_country else None
        fx = float(c.get("fx_rate_to_usd", 0) or 0) if c else 0
        cur = (c.get("currency") if c else None) or "USD"
        if fx > 0 and cur != "USD":
            local = float(p["price_usd"]) * fx
            step = float(c.get("fx_rounding", 1) or 1)
            if step > 0:
                local = round(local / step) * step
            p["local_price"] = round(local, 2)
            p["local_currency"] = cur
            p["fx_rate_used"] = fx
        else:
            p["local_price"] = round(float(p["price_usd"]), 2)
            p["local_currency"] = "USD"
            p["fx_rate_used"] = 1.0
    return items


@router.get("/rates")
async def my_rates(user: dict = Depends(get_current_user)):
    rates = await get_setting("credits.country_rate", {}) or {}
    return {
        "country_rate":             rates,
        "default_rate":             await get_setting("credits.default_rate", 2),
        "whatsapp_rate":            await get_setting("credits.whatsapp_rate", 3),
        "sender_id_cost":           await get_setting("credits.sender_id_cost", 500),
        "sender_id_renewal":        await get_setting("credits.sender_id_renewal", 500),
        "sender_id_expiry_days":    await get_setting("credits.sender_id_expiry_days", 365),
        "inactivity_warn_days":     await get_setting("credits.inactivity_warn_days", 30),
        "inactivity_suspend_days":  await get_setting("credits.inactivity_suspend_days", 60),
        "inactivity_recovery_cost": await get_setting("credits.inactivity_recovery_cost", 1000),
    }


@router.post("/buy")
async def buy_pack(body: BuyPackIn, user: dict = Depends(get_current_user)):
    """Mock payment — credits the wallet immediately, applies promo bonus,
    records the payment, and triggers affiliate commission attribution."""
    pack = await db.credit_packs.find_one({"id": body.pack_id, "active": True}, {"_id": 0})
    if not pack:
        raise HTTPException(404, "Pack not found")
    bonus = 0
    if body.promo_code:
        promo = await db.promotions.find_one(
            {"code": body.promo_code.upper(), "active": True})
        if promo and float(pack.get("price_usd", 0)) >= float(promo.get("min_topup", 0)):
            promo_country = promo.get("country")
            user_country = user.get("country")
            if promo_country and promo_country != user_country:
                raise HTTPException(400,
                    f"This promo code is only valid for customers in {promo_country}.")
            if promo["type"] == "bonus_credit":
                bonus = int(float(promo["value"]) * 100)
            elif promo["type"] == "percent_discount":
                bonus = int(pack["credits"] * float(promo["value"]) / 100.0)
    total_credits = int(pack["credits"]) + int(bonus)
    # Affiliate welcome bonus on first paid top-up
    if user.get("referred_by") and not user.get("welcome_bonus_used", True):
        wb_pct = float(user.get("welcome_bonus_pct", 0))
        wb_cap = int(user.get("welcome_bonus_max_credits", 0))
        wb = min(int(pack["credits"] * wb_pct / 100.0), wb_cap)
        if wb > 0:
            total_credits += wb
            await db.users.update_one({"id": user["id"]},
                                      {"$set": {"welcome_bonus_used": True}})
    await adjust_wallet(user["id"], total_credits, "pack_purchase",
                        note=f"Pack {pack['name']} · {pack['credits']} credits"
                             + (f" + {bonus} promo" if bonus else ""),
                        ref=pack["id"], by=user["id"])
    payment_id = new_id()
    await db.platform_payments.insert_one({
        "id": payment_id, "user_id": user["id"], "pack_id": pack["id"],
        "credits": pack["credits"], "bonus": bonus,
        "price_usd": float(pack["price_usd"]), "method": "mock",
        "promo_code": body.promo_code, "created_at": iso(now_utc()),
    })
    # Affiliate commission attribution (replaces legacy referral reward)
    try:
        from routes.affiliate import record_topup_commission
        await record_topup_commission(user, float(pack["price_usd"]), ref=payment_id)
    except Exception:
        pass
    await add_notification(user["id"], "Credits added",
                           f"+{total_credits} credits purchased. Happy sending!",
                           "success")
    return {"ok": True, "credits_added": total_credits, "bonus": bonus}


@router.post("/recover")
async def recover_account(user: dict = Depends(get_current_user)):
    if user.get("status") != "inactive":
        return {"ok": True, "message": "Account already active."}
    cost = int(await get_setting("credits.inactivity_recovery_cost", 1000) or 0)
    w = await get_or_create_wallet(user["id"])
    if w["balance"] < cost:
        raise HTTPException(400,
            f"Recovery needs {cost} credits. You have {int(w['balance'])}.")
    await adjust_wallet(user["id"], -cost, "account_recovery",
                        note="Inactivity recovery fee", by=user["id"])
    await db.users.update_one({"id": user["id"]},
                              {"$set": {"status": "active",
                                        "last_active_at": iso(now_utc())}})
    await add_notification(user["id"], "Account restored",
                           "Welcome back. You're active again.", "success")
    return {"ok": True}
