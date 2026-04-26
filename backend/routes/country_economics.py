"""Per-country economics + P&L dashboard.

Two routers:
  - /api/admin/country-economics — VAT, sell, wholesale per country
  - /api/admin/country-pnl       — aggregated P&L sourced from message-level
                                    snapshots written by `execute_campaign`.
"""
from fastapi import APIRouter, Depends, HTTPException
from pydantic import BaseModel, Field
from server import db, require_roles, now_utc, iso, add_audit


# ---------- Input model ----------
class CountryEconomicsIn(BaseModel):
    vat_rate_pct: float = Field(ge=0, le=100)
    sell_per_sms_local: float = Field(ge=0)        # gross, VAT-incl, what direct clients pay
    wholesale_per_sms_local: float = Field(ge=0)   # internal reference for affiliates


# Distinct prefixes so /pnl doesn't get eaten by country_r's /{code} catch-all
econ_router = APIRouter(prefix="/admin/country-economics", tags=["country_economics"])
pnl_router  = APIRouter(prefix="/admin/country-pnl",       tags=["country_pnl"])


@pnl_router.get("")
async def country_pnl(_: dict = Depends(require_roles("super_admin"))):
    """Aggregated per-country profit & loss: sent / revenue / cost / margin."""
    countries = await db.countries.find({}, {"_id": 0}).to_list(200)
    rows = []
    for c in countries:
        code = c["code"]
        sent = await db.messages.count_documents(
            {"country": code, "status": {"$in": ["sent", "delivered"]}})
        if sent == 0:
            continue
        agg = await db.messages.aggregate([
            {"$match": {"country": code, "status": {"$in": ["sent", "delivered"]}}},
            {"$group": {
                "_id": None,
                "rev_local":  {"$sum": "$revenue_local"},
                "cost_local": {"$sum": "$cost_incl_vat_local"},
            }},
        ]).to_list(1)
        rev_local  = float(agg[0]["rev_local"])  if agg else 0.0
        cost_local = float(agg[0]["cost_local"]) if agg else 0.0
        if rev_local == 0 and cost_local == 0:
            e = c.get("economics") or {}
            rev_local  = sent * float(e.get("sell_per_sms_local", 0) or 0)
            buy_pre    = float(e.get("avg_buy_pre_vat", 0) or 0)
            vat        = float(e.get("vat_rate_pct", 0) or 0)
            cost_local = sent * buy_pre * (1 + vat / 100.0)
        profit_local = rev_local - cost_local
        margin_pct   = (profit_local / rev_local * 100.0) if rev_local else 0.0
        fx = float(c.get("fx_rate_to_usd", 0) or 0)
        profit_usd = (profit_local / fx) if fx > 0 else 0.0
        rows.append({
            "code": code, "name": c.get("name"),
            "currency": c.get("currency"),
            "sent": sent,
            "revenue_local": round(rev_local, 2),
            "cost_local":    round(cost_local, 2),
            "profit_local":  round(profit_local, 2),
            "margin_pct":    round(margin_pct, 1),
            "profit_usd":    round(profit_usd, 2),
        })
    rows.sort(key=lambda x: x["profit_usd"], reverse=True)
    total_usd = round(sum(r["profit_usd"] for r in rows), 2)
    return {"rows": rows, "total_profit_usd": total_usd}


@econ_router.get("/{code}")
async def get_country_economics(code: str,
                                _: dict = Depends(require_roles("super_admin", "country_admin"))):
    code = code.upper()
    r = await db.countries.find_one({"code": code}, {"_id": 0})
    if not r:
        raise HTTPException(404)
    e = r.get("economics") or {}
    return {
        "code": code,
        "currency": r.get("currency"),
        "fx_rate_to_usd": r.get("fx_rate_to_usd"),
        "vat_rate_pct": e.get("vat_rate_pct", 0),
        "sell_per_sms_local": e.get("sell_per_sms_local", 0),
        "wholesale_per_sms_local": e.get("wholesale_per_sms_local", 0),
    }


@econ_router.put("/{code}")
async def set_country_economics(code: str, body: CountryEconomicsIn,
                                admin: dict = Depends(require_roles("super_admin"))):
    code = code.upper()
    r = await db.countries.find_one({"code": code})
    if not r:
        raise HTTPException(404)
    await db.countries.update_one({"code": code}, {"$set": {
        "economics": {
            "vat_rate_pct":            float(body.vat_rate_pct),
            "sell_per_sms_local":      float(body.sell_per_sms_local),
            "wholesale_per_sms_local": float(body.wholesale_per_sms_local),
        },
        "updated_at": iso(now_utc()),
    }})
    await add_audit(admin["id"], "country.economics", target=code, meta=body.model_dump())
    return {"ok": True}
