"""Legacy referrals (client-to-client invite codes).

Replaced for distribution by the Affiliate program (see routes/affiliate.py)
but kept around so existing clients with `referral_code` keep working.
"""
import secrets
from fastapi import APIRouter, Depends
from server import db, get_current_user, get_setting

router = APIRouter(prefix="/referrals", tags=["referrals"])


@router.get("/me")
async def my_referral(user: dict = Depends(get_current_user)):
    code = user.get("referral_code")
    if not code:
        code = "U" + secrets.token_hex(3).upper()
        await db.users.update_one({"id": user["id"]},
                                  {"$set": {"referral_code": code}})
    n = await db.users.count_documents({"referred_by": user["id"]})
    earned = int(user.get("referral_earned_credits", 0) or 0)
    return {"code": code, "referred_count": n, "earned": earned,
            "percent_of_pack":   await get_setting("referral.percent_of_pack", 5),
            "max_per_referral":  await get_setting("referral.max_credits_per_referral", 500),
            "active":            await get_setting("referral.active", True)}
