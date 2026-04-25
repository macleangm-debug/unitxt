"""Messaging extras — pre-flight validation (Wave A) and saved CSV mappings (Wave B).

Mounted under `/messaging` so the public endpoints stay backward-compatible:
  POST   /messaging/preflight                 — free sample-validation
  GET    /messaging/csv-mappings              — list saved presets
  POST   /messaging/csv-mappings              — create / upsert
  POST   /messaging/csv-mappings/{id}/used    — touch last_used_at
  DELETE /messaging/csv-mappings/{id}
"""
from typing import Dict, List, Optional
from fastapi import APIRouter, Depends, HTTPException
from pydantic import BaseModel

from server import (
    db, get_current_user,
    new_id, iso, now_utc, clean, get_setting,
    smart_validate_number,
)


router = APIRouter(prefix="/messaging", tags=["messaging_extras"])


# ---------- Pre-flight (Wave A) ----------
class PreflightIn(BaseModel):
    phones: List[str]
    sample_size: int = 20


@router.post("/preflight")
async def bulk_preflight(body: PreflightIn, user: dict = Depends(get_current_user)):
    """Smart-validates a small sample of a bulk list FOR FREE so the client can
    gauge quality before committing credits to a full send."""
    if not body.phones:
        raise HTTPException(400, "Provide at least one number.")

    free_cap = int(await get_setting("messaging.preflight_free_sample", 20) or 20)
    cap = max(1, min(int(body.sample_size or free_cap), free_cap))

    full = [p for p in body.phones if p and str(p).strip()]
    if len(full) <= cap:
        sample = full
    else:
        step = max(1, len(full) // cap)
        sample = [full[i] for i in range(0, len(full), step)][:cap]

    results = []
    buckets = {"valid": 0, "invalid_format": 0, "unknown_operator": 0,
                "dnd_or_flagged": 0, "bad_history": 0}
    country_counts: Dict[str, int] = {}
    operator_counts: Dict[str, int] = {}
    for p in sample:
        res = await smart_validate_number(p, user["id"])
        country_counts[res.get("country") or "?"] = country_counts.get(res.get("country") or "?", 0) + 1
        op = res.get("operator") or "unknown"
        operator_counts[op] = operator_counts.get(op, 0) + 1
        if res.get("valid"):
            buckets["valid"] += 1
        else:
            reason = (res.get("reason") or "").lower()
            if "format" in reason or "invalid" in reason:
                buckets["invalid_format"] += 1
            elif "operator" in reason or "prefix" in reason:
                buckets["unknown_operator"] += 1
            elif "dnd" in reason or "opt-out" in reason or "flagged" in reason:
                buckets["dnd_or_flagged"] += 1
            else:
                buckets["bad_history"] += 1
        results.append(res)

    n = len(sample) or 1
    valid_pct = round(buckets["valid"] * 100 / n, 1)
    predicted_valid = int(round(len(full) * valid_pct / 100))
    predicted_failed = len(full) - predicted_valid

    # Thresholds are configurable from the Settings Hub
    red_pct = float(await get_setting("messaging.preflight_red_threshold", 50) or 50)
    amber_pct = float(await get_setting("messaging.preflight_amber_threshold", 80) or 80)

    warning = None
    if valid_pct < red_pct:
        warning = (f"Heads-up: only ~{valid_pct}% of your sample look deliverable. "
                    f"That's roughly {predicted_failed:,} wasted credits on a list this size. "
                    f"Clean the list (Contacts → Auto-clean) before you send.")
    elif valid_pct < amber_pct:
        warning = (f"~{valid_pct}% of your sample look deliverable. Consider running "
                    f"Auto-clean on the list or reviewing the flagged rows below.")

    return {
        "checked": n,
        "total_in_list": len(full),
        "valid_pct": valid_pct,
        "buckets": buckets,
        "predicted_valid": predicted_valid,
        "predicted_failed": predicted_failed,
        "countries": country_counts,
        "operators": operator_counts,
        "warning": warning,
        "results": results,
        "free": True,
    }


# ---------- Saved CSV column mappings (Wave B) ----------
class CsvMappingIn(BaseModel):
    name: str
    phone_column: str
    column_renames: Dict[str, str] = {}
    note: Optional[str] = ""


@router.get("/csv-mappings")
async def list_csv_mappings(user: dict = Depends(get_current_user)):
    return await db.csv_mappings.find({"user_id": user["id"]}, {"_id": 0}).sort(
        "created_at", -1).to_list(200)


@router.post("/csv-mappings")
async def save_csv_mapping(body: CsvMappingIn, user: dict = Depends(get_current_user)):
    name = (body.name or "").strip()
    if not name:
        raise HTTPException(400, "Give your mapping a name (e.g. 'CRM Export').")
    if not body.phone_column:
        raise HTTPException(400, "Pick which column holds the phone number.")
    existing = await db.csv_mappings.find_one({"user_id": user["id"], "name": name})
    doc = {
        "id": existing["id"] if existing else new_id(),
        "user_id": user["id"],
        "name": name,
        "phone_column": body.phone_column,
        "column_renames": body.column_renames or {},
        "note": body.note or "",
        "created_at": existing["created_at"] if existing else iso(now_utc()),
        "updated_at": iso(now_utc()),
        "last_used_at": existing.get("last_used_at") if existing else None,
    }
    if existing:
        await db.csv_mappings.update_one({"id": existing["id"]}, {"$set": doc})
    else:
        await db.csv_mappings.insert_one(doc)
    return clean(doc)


@router.post("/csv-mappings/{mid}/used")
async def touch_csv_mapping(mid: str, user: dict = Depends(get_current_user)):
    r = await db.csv_mappings.find_one({"id": mid, "user_id": user["id"]})
    if not r:
        raise HTTPException(404)
    await db.csv_mappings.update_one({"id": mid}, {"$set": {"last_used_at": iso(now_utc())}})
    return {"ok": True}


@router.delete("/csv-mappings/{mid}")
async def delete_csv_mapping(mid: str, user: dict = Depends(get_current_user)):
    r = await db.csv_mappings.delete_one({"id": mid, "user_id": user["id"]})
    if r.deleted_count == 0:
        raise HTTPException(404)
    return {"ok": True}
