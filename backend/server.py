"""unitxt - Global Bulk SMS & WhatsApp Operating System
Single-file FastAPI app with JWT auth, RBAC, multi-role, multi-country, multi-provider.
"""
from dotenv import load_dotenv
from pathlib import Path

ROOT_DIR = Path(__file__).parent
load_dotenv(ROOT_DIR / ".env")

import os
import uuid
import bcrypt
import jwt
import secrets
import logging
import asyncio
import random
import time
from datetime import datetime, timezone, timedelta
from typing import List, Optional, Literal, Any, Dict
from fastapi import FastAPI, APIRouter, Depends, HTTPException, Request, Response, Query
from starlette.middleware.cors import CORSMiddleware
from motor.motor_asyncio import AsyncIOMotorClient
from pydantic import BaseModel, Field, EmailStr, ConfigDict


# ============================================================
# Setup
# ============================================================
JWT_SECRET = os.environ["JWT_SECRET"]
JWT_ALG = "HS256"
ACCESS_MIN = 60 * 12  # 12h for nicer demo experience
REFRESH_DAYS = 30

mongo_url = os.environ["MONGO_URL"]
mongo_client = AsyncIOMotorClient(mongo_url)
db = mongo_client[os.environ["DB_NAME"]]

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(name)s: %(message)s")
log = logging.getLogger("unitxt")

app = FastAPI(title="unitxt API", version="1.0.0")
api = APIRouter(prefix="/api")


def now_utc() -> datetime:
    return datetime.now(timezone.utc)


def iso(dt: datetime) -> str:
    return dt.astimezone(timezone.utc).isoformat()


def new_id() -> str:
    return str(uuid.uuid4())


def hash_password(p: str) -> str:
    return bcrypt.hashpw(p.encode(), bcrypt.gensalt()).decode()


def verify_password(p: str, h: str) -> bool:
    try:
        return bcrypt.checkpw(p.encode(), h.encode())
    except Exception:
        return False


def make_access(user_id: str, email: str, role: str) -> str:
    return jwt.encode(
        {"sub": user_id, "email": email, "role": role,
         "exp": now_utc() + timedelta(minutes=ACCESS_MIN), "type": "access"},
        JWT_SECRET, algorithm=JWT_ALG)


def make_refresh(user_id: str) -> str:
    return jwt.encode(
        {"sub": user_id, "exp": now_utc() + timedelta(days=REFRESH_DAYS), "type": "refresh"},
        JWT_SECRET, algorithm=JWT_ALG)


def set_auth_cookies(resp: Response, access: str, refresh: str):
    resp.set_cookie("access_token", access, httponly=True, secure=True, samesite="none",
                    max_age=ACCESS_MIN * 60, path="/")
    resp.set_cookie("refresh_token", refresh, httponly=True, secure=True, samesite="none",
                    max_age=REFRESH_DAYS * 86400, path="/")


def clear_auth_cookies(resp: Response):
    resp.delete_cookie("access_token", path="/")
    resp.delete_cookie("refresh_token", path="/")


async def get_token_payload(request: Request) -> dict:
    token = request.cookies.get("access_token")
    if not token:
        auth = request.headers.get("Authorization", "")
        if auth.startswith("Bearer "):
            token = auth[7:]
    if not token:
        raise HTTPException(401, "Not authenticated")
    try:
        payload = jwt.decode(token, JWT_SECRET, algorithms=[JWT_ALG])
        if payload.get("type") != "access":
            raise HTTPException(401, "Invalid token type")
        return payload
    except jwt.ExpiredSignatureError:
        raise HTTPException(401, "Token expired")
    except jwt.InvalidTokenError:
        raise HTTPException(401, "Invalid token")


async def get_current_user(request: Request) -> dict:
    payload = await get_token_payload(request)
    user = await db.users.find_one({"id": payload["sub"]}, {"_id": 0, "password_hash": 0})
    if not user:
        raise HTTPException(401, "User not found")
    return user


def require_roles(*allowed: str):
    async def checker(user: dict = Depends(get_current_user)):
        if user["role"] not in allowed:
            raise HTTPException(403, f"Requires one of: {', '.join(allowed)}")
        return user
    return checker


# ============================================================
# Pydantic Models
# ============================================================
ROLES = Literal["super_admin", "country_admin", "reseller", "client", "staff", "support", "finance", "compliance"]


class UserOut(BaseModel):
    model_config = ConfigDict(extra="ignore")
    id: str
    email: str
    name: str
    role: str
    business_name: Optional[str] = None
    phone: Optional[str] = None
    country: Optional[str] = None
    reseller_id: Optional[str] = None
    status: str = "active"
    created_at: str


class RegisterIn(BaseModel):
    email: EmailStr
    password: str = Field(min_length=6)
    name: str
    role: Optional[str] = "client"  # client | reseller (admin only via seed)
    business_name: Optional[str] = None
    phone: Optional[str] = None
    country: Optional[str] = "TZ"
    reseller_code: Optional[str] = None
    referral_code: Optional[str] = None  # another user's referral code


class LoginIn(BaseModel):
    email: EmailStr
    password: str


class ForgotIn(BaseModel):
    email: EmailStr


class ResetIn(BaseModel):
    token: str
    password: str = Field(min_length=6)


# Wallet
class TopUpIn(BaseModel):
    amount: float = Field(gt=0)
    method: str = "manual"  # manual | stripe (mock)
    note: Optional[str] = None


class TransferIn(BaseModel):
    target_user_id: str
    amount: float = Field(gt=0)
    note: Optional[str] = None


# SMS
class QuickSendIn(BaseModel):
    channel: str = "sms"  # sms | whatsapp
    sender_id: str
    recipients: List[str]
    message: str
    schedule_at: Optional[str] = None  # ISO


class BulkSendIn(BaseModel):
    channel: str = "sms"
    sender_id: str
    name: str
    recipients: List[Dict[str, Any]]  # [{phone, name?, ...vars}]
    template: str  # supports {var}
    schedule_at: Optional[str] = None


# Contact
class ContactIn(BaseModel):
    phone: str
    name: Optional[str] = ""
    group_id: Optional[str] = None
    group_ids: List[str] = []
    tags: List[str] = []
    extras: Dict[str, Any] = Field(default_factory=dict)  # arbitrary columns for personalization


class ContactGroupIn(BaseModel):
    name: str
    description: Optional[str] = ""


# Sender ID
class SenderIdRequestIn(BaseModel):
    sender_id: str
    country: str
    use_case: str
    sample_message: str
    documents: List[str] = []


class SenderIdReviewIn(BaseModel):
    status: str  # approved | rejected
    note: Optional[str] = None


# Template
class TemplateIn(BaseModel):
    name: str
    body: str
    category: Optional[str] = "transactional"


# Country / Provider / Pricing / Settings
class CountryIn(BaseModel):
    code: str
    name: str
    currency: str = "USD"
    dial_code: str = "+1"
    default_provider_id: Optional[str] = None
    sender_id_required: bool = True
    active: bool = True


class ProviderIn(BaseModel):
    name: str
    type: str = "aggregator"  # direct_telco | aggregator | api_partner
    countries: List[str] = []
    channels: List[str] = ["sms"]
    api_key: Optional[str] = ""
    api_secret: Optional[str] = ""
    base_url: Optional[str] = ""
    cost_per_sms: float = 0.01
    buy_price_local_pre_vat: float = 0.0   # pre-VAT cost in destination country's currency
    priority: int = 100
    active: bool = True
    supports_unicode: bool = True
    supports_dlr: bool = True


class PricingPlanIn(BaseModel):
    name: str
    country: str
    channel: str = "sms"
    base_price: float
    reseller_price: Optional[float] = None
    client_price: Optional[float] = None
    min_volume: int = 0
    active: bool = True


class InstitutionIn(BaseModel):
    name: str
    type: str = "bank"  # bank | mobile_money | fintech | enterprise
    country: str
    api_credentials: Dict[str, Any] = {}
    callback_url: Optional[str] = ""
    active: bool = True


class PromotionIn(BaseModel):
    name: str
    code: str
    type: str = "bonus_credit"  # bonus_credit | percent_discount | free_sms
    value: float
    min_topup: float = 0
    country: Optional[str] = None  # None = global. ISO-2 = country-scoped.
    active: bool = True
    valid_from: Optional[str] = None
    valid_to: Optional[str] = None


class SettingsIn(BaseModel):
    key: str
    value: Any
    category: str = "global"


class ApiKeyIn(BaseModel):
    name: str


class ResellerPricingIn(BaseModel):
    client_id: str
    country: str
    channel: str = "sms"
    price: float


# ============================================================
# Helpers
# ============================================================
def clean(doc: Optional[dict]) -> Optional[dict]:
    if not doc:
        return doc
    doc.pop("_id", None)
    doc.pop("password_hash", None)
    return doc


def cleanl(docs: List[dict]) -> List[dict]:
    return [clean(d) for d in docs]


def gsm_segments(text: str) -> int:
    if not text:
        return 0
    is_unicode = any(ord(c) > 127 for c in text)
    if is_unicode:
        return max(1, -(-len(text) // 67)) if len(text) > 70 else 1
    return max(1, -(-len(text) // 153)) if len(text) > 160 else 1


async def add_notification(user_id: str, title: str, body: str, kind: str = "info"):
    await db.notifications.insert_one({
        "id": new_id(), "user_id": user_id, "title": title, "body": body,
        "kind": kind, "read": False, "created_at": iso(now_utc())
    })


async def add_audit(actor_id: str, action: str, target: str = "", meta: Optional[dict] = None):
    await db.audit_logs.insert_one({
        "id": new_id(), "actor_id": actor_id, "action": action,
        "target": target, "meta": meta or {}, "created_at": iso(now_utc())
    })


async def get_or_create_wallet(user_id: str, currency: str = "USD") -> dict:
    w = await db.wallets.find_one({"user_id": user_id}, {"_id": 0})
    if not w:
        w = {"id": new_id(), "user_id": user_id, "balance": 0.0, "currency": currency,
             "created_at": iso(now_utc())}
        await db.wallets.insert_one(w)
        w.pop("_id", None)
    return w


async def adjust_wallet(user_id: str, amount: float, kind: str, note: str = "",
                        ref: Optional[str] = None, by: Optional[str] = None) -> dict:
    w = await get_or_create_wallet(user_id)
    new_bal = round(w["balance"] + amount, 4)
    if new_bal < 0 and kind != "manual_adjustment":
        raise HTTPException(400, "Insufficient balance")
    await db.wallets.update_one({"id": w["id"]}, {"$set": {"balance": new_bal}})
    tx = {
        "id": new_id(), "wallet_id": w["id"], "user_id": user_id,
        "amount": amount, "kind": kind, "note": note, "ref": ref,
        "balance_after": new_bal, "by": by or user_id, "created_at": iso(now_utc())
    }
    await db.wallet_transactions.insert_one(tx)
    tx.pop("_id", None)
    return tx


# ============================================================
# Provider Adapter (Pluggable)
# ============================================================
class ProviderAdapter:
    """Base adapter all SMS/WhatsApp providers must implement."""

    def __init__(self, provider: dict):
        self.provider = provider

    async def send(self, to: str, sender_id: str, message: str, channel: str) -> dict:
        raise NotImplementedError


class MockAdapter(ProviderAdapter):
    async def send(self, to: str, sender_id: str, message: str, channel: str) -> dict:
        await asyncio.sleep(0)  # cooperative
        # Simulate 95% delivery
        success = random.random() < 0.95
        return {
            "ok": success,
            "provider_msg_id": f"mock_{secrets.token_hex(6)}",
            "status": "delivered" if success else "failed",
            "error": None if success else "MOCK_NETWORK_ERROR",
        }


class TwilioAdapter(ProviderAdapter):
    async def send(self, to: str, sender_id: str, message: str, channel: str) -> dict:
        # Stub: real Twilio would call Twilio REST API. We surface a clear status.
        if not self.provider.get("api_key") or not self.provider.get("api_secret"):
            return {"ok": False, "provider_msg_id": None, "status": "failed",
                    "error": "TWILIO_CREDENTIALS_MISSING"}
        # Pretend success
        return {"ok": True, "provider_msg_id": f"tw_{secrets.token_hex(6)}",
                "status": "sent", "error": None}


class TigoTZAdapter(ProviderAdapter):
    """Tanzania Tigo direct-connect adapter. Configure via provider.api_key (username),
    api_secret (password), base_url (VPN endpoint), and a custom 'account_id' field.
    Replace the placeholder body once the user provides VPN/API documentation."""
    async def send(self, to: str, sender_id: str, message: str, channel: str) -> dict:
        creds_ok = bool(self.provider.get("api_key") and self.provider.get("api_secret")
                        and self.provider.get("base_url"))
        if not creds_ok:
            # Offline mode: simulate acceptance, log intent. Flips to real call once creds exist.
            log.info(f"[TigoTZ stub] to={to} sender={sender_id} len={len(message)}")
            return {"ok": True, "provider_msg_id": f"tigo_stub_{secrets.token_hex(6)}",
                    "status": "sent", "error": None}
        # Placeholder for real VPN call — will be replaced with user-provided Tigo API.
        return {"ok": True, "provider_msg_id": f"tigo_{secrets.token_hex(6)}",
                "status": "sent", "error": None}


def adapter_for(provider: dict) -> ProviderAdapter:
    name = (provider.get("name") or "").lower()
    if "twilio" in name:
        return TwilioAdapter(provider)
    if "tigo" in name:
        return TigoTZAdapter(provider)
    return MockAdapter(provider)


async def pick_provider(country: str, channel: str) -> Optional[dict]:
    """Routing engine: choose highest priority active provider for country+channel."""
    cur = db.providers.find({
        "active": True,
        "channels": channel,
        "$or": [{"countries": country}, {"countries": "*"}, {"countries": []}],
    }, {"_id": 0}).sort("priority", 1)
    providers = await cur.to_list(50)
    return providers[0] if providers else None


async def get_setting(key: str, default=None):
    s = await db.system_settings.find_one({"key": key}, {"_id": 0})
    return s["value"] if s else default


async def country_price(country: str, channel: str, role: str) -> float:
    """Admin USD cost per message segment (provider cost). Used for margin reports."""
    plan = await db.pricing_plans.find_one(
        {"country": country, "channel": channel, "active": True}, {"_id": 0})
    if not plan:
        return 0.05
    if role == "reseller" and plan.get("reseller_price"):
        return float(plan["reseller_price"])
    if role == "client" and plan.get("client_price"):
        return float(plan["client_price"])
    return float(plan["base_price"])


async def credits_per_msg(country: str, channel: str) -> int:
    """How many credits to charge per SMS/WA segment for the given country+channel.
    Driven entirely by Settings Hub keys credits.country_rate.<CC> and credits.whatsapp_rate."""
    if channel == "whatsapp":
        return int(await get_setting("credits.whatsapp_rate", 3))
    rates = await get_setting("credits.country_rate", {}) or {}
    if country in rates:
        try:
            return int(rates[country])
        except (TypeError, ValueError):
            pass
    return int(await get_setting("credits.default_rate", 2))


async def phone_to_operator(phone: str) -> Optional[dict]:
    """Return the operator+country that matches the longest prefix for this phone number."""
    if not phone:
        return None
    p = phone.strip().replace(" ", "").replace("-", "")
    if not p.startswith("+"):
        p = "+" + p
    candidates = await db.mobile_prefixes.find({"active": True}, {"_id": 0}).to_list(5000)
    match = None
    best = 0
    for c in candidates:
        pre = str(c.get("prefix", "")).replace(" ", "")
        if pre and p.startswith(pre) and len(pre) > best:
            match = c
            best = len(pre)
    return match


# ============================================================
# AUTH ROUTES
# ============================================================
auth_r = APIRouter(prefix="/auth", tags=["auth"])


@auth_r.post("/register")
async def register(body: RegisterIn, response: Response):
    email = body.email.lower().strip()
    if await db.users.find_one({"email": email}):
        raise HTTPException(400, "Email already registered")
    role = body.role if body.role in ("client", "reseller") else "client"
    reseller_id = None
    if body.reseller_code:
        r = await db.users.find_one({"reseller_code": body.reseller_code, "role": "reseller"})
        if r:
            reseller_id = r["id"]
    referred_by = None
    if body.referral_code:
        ref_owner = None
        code_u = body.referral_code.upper().strip()
        # Affiliate code first (preferred new path)
        ac = await db.affiliate_codes.find_one({"code": code_u, "active": True})
        if ac:
            ref_owner = await db.users.find_one({"id": ac["owner_user_id"]})
            await db.affiliate_codes.update_one({"id": ac["id"]}, {"$inc": {"uses": 1}})
        # Legacy: a user's auto-generated referral_code
        if not ref_owner:
            ref_owner = await db.users.find_one({"referral_code": code_u})
        if ref_owner and ref_owner["email"] != email:
            referred_by = ref_owner["id"]
    user = {
        "id": new_id(),
        "email": email,
        "password_hash": hash_password(body.password),
        "name": body.name,
        "role": role,
        "business_name": body.business_name,
        "phone": body.phone,
        "country": body.country or "TZ",
        "reseller_id": reseller_id,
        "referred_by": referred_by,
        "referral_code": "U" + secrets.token_hex(3).upper(),
        "referral_earned_credits": 0,
        "send_streak_days": 0,
        "status": "active",
        "kyc_verified": False,
        "created_at": iso(now_utc()),
    }
    if role == "reseller":
        user["reseller_code"] = "R" + secrets.token_hex(3).upper()
        user["commission_rate"] = 0.10
    await db.users.insert_one(user)
    await get_or_create_wallet(user["id"])
    # Mint a default primary affiliate code for affiliate-eligible roles
    if role in ("reseller", "affiliate"):
        existing = await db.affiliate_codes.find_one({"owner_user_id": user["id"]})
        if not existing:
            seed = (user.get("referral_code") or
                     ("U" + secrets.token_hex(3).upper()))
            await db.affiliate_codes.insert_one({
                "id": new_id(), "code": seed.upper(),
                "note": "Primary affiliate code (default)",
                "active": True, "owner_user_id": user["id"],
                "created_at": iso(now_utc()), "uses": 0,
            })
    # Welcome bonus for being referred via an affiliate code
    if referred_by:
        try:
            wb_pct = float(await get_setting("affiliate.welcome_bonus_pct", 5))
            wb_max = int(await get_setting("affiliate.welcome_bonus_max_credits", 500))
            # Bonus is delivered on the user's FIRST paid top-up (not on signup) so
            # we can scale it to pack size — store the rule on the user doc.
            await db.users.update_one(
                {"id": user["id"]},
                {"$set": {"welcome_bonus_pct": wb_pct,
                           "welcome_bonus_max_credits": wb_max,
                           "welcome_bonus_used": False}})
        except Exception:
            pass
    await add_notification(user["id"], "Welcome to unitxt",
                           "Your account is ready. Top up your wallet to start sending.", "success")
    if reseller_id:
        await add_notification(reseller_id, "New client signed up",
                               f"{body.name} ({email}) joined with your code.", "info")
    access = make_access(user["id"], email, role)
    refresh = make_refresh(user["id"])
    set_auth_cookies(response, access, refresh)
    out = {k: v for k, v in user.items() if k not in ("password_hash", "_id")}
    return {"user": out, "access_token": access}


@auth_r.post("/login")
async def login(body: LoginIn, request: Request, response: Response):
    email = body.email.lower().strip()
    ip = request.client.host if request.client else "?"
    ident = f"{ip}:{email}"
    # brute force window
    rec = await db.login_attempts.find_one({"identifier": ident})
    if rec and rec.get("locked_until"):
        try:
            lu = datetime.fromisoformat(rec["locked_until"])
            if lu > now_utc():
                raise HTTPException(429, "Too many attempts. Try later.")
        except Exception:
            pass
    user = await db.users.find_one({"email": email})
    if not user or not verify_password(body.password, user["password_hash"]):
        await db.login_attempts.update_one(
            {"identifier": ident},
            {"$inc": {"count": 1},
             "$set": {"last_at": iso(now_utc()),
                      **({"locked_until": iso(now_utc() + timedelta(minutes=15))}
                         if (rec and rec.get("count", 0) + 1 >= 5) else {})}},
            upsert=True)
        raise HTTPException(401, "Invalid credentials")
    await db.login_attempts.delete_one({"identifier": ident})
    access = make_access(user["id"], user["email"], user["role"])
    refresh = make_refresh(user["id"])
    set_auth_cookies(response, access, refresh)
    out = clean(user)
    await db.users.update_one({"id": user["id"]},
                               {"$set": {"last_active_at": iso(now_utc()),
                                          "inactivity_warned": False}})
    await add_audit(user["id"], "login", target=user["id"])
    return {"user": out, "access_token": access}


@auth_r.post("/logout")
async def logout(response: Response, user: dict = Depends(get_current_user)):
    clear_auth_cookies(response)
    await add_audit(user["id"], "logout")
    return {"ok": True}


@auth_r.get("/me")
async def me(user: dict = Depends(get_current_user)):
    wallet = await get_or_create_wallet(user["id"])
    return {"user": user, "wallet": wallet}


@auth_r.post("/refresh")
async def refresh_token(request: Request, response: Response):
    rt = request.cookies.get("refresh_token")
    if not rt:
        raise HTTPException(401, "Missing refresh token")
    try:
        p = jwt.decode(rt, JWT_SECRET, algorithms=[JWT_ALG])
        if p.get("type") != "refresh":
            raise HTTPException(401, "Invalid type")
    except jwt.PyJWTError:
        raise HTTPException(401, "Invalid refresh token")
    user = await db.users.find_one({"id": p["sub"]})
    if not user:
        raise HTTPException(401, "User missing")
    access = make_access(user["id"], user["email"], user["role"])
    response.set_cookie("access_token", access, httponly=True, secure=True, samesite="none",
                        max_age=ACCESS_MIN * 60, path="/")
    return {"ok": True}


@auth_r.post("/forgot-password")
async def forgot(body: ForgotIn):
    user = await db.users.find_one({"email": body.email.lower()})
    if user:
        token = secrets.token_urlsafe(32)
        await db.password_reset_tokens.insert_one({
            "id": new_id(), "user_id": user["id"], "token": token, "used": False,
            "created_at": iso(now_utc()),
            "expires_at": iso(now_utc() + timedelta(hours=1)),
        })
        log.info(f"PASSWORD RESET LINK: /reset?token={token}")
    return {"ok": True, "message": "If that email exists, a reset link was sent."}


@auth_r.post("/reset-password")
async def reset(body: ResetIn):
    rec = await db.password_reset_tokens.find_one({"token": body.token, "used": False})
    if not rec:
        raise HTTPException(400, "Invalid or used token")
    if datetime.fromisoformat(rec["expires_at"]) < now_utc():
        raise HTTPException(400, "Token expired")
    await db.users.update_one({"id": rec["user_id"]},
                              {"$set": {"password_hash": hash_password(body.password)}})
    await db.password_reset_tokens.update_one({"id": rec["id"]}, {"$set": {"used": True}})
    return {"ok": True}


# ============================================================
# WALLET ROUTES
# ============================================================
wallet_r = APIRouter(prefix="/wallet", tags=["wallet"])


@wallet_r.get("/me")
async def my_wallet(user: dict = Depends(get_current_user)):
    w = await get_or_create_wallet(user["id"])
    return w


@wallet_r.get("/transactions")
async def my_tx(limit: int = 50, user: dict = Depends(get_current_user)):
    items = await db.wallet_transactions.find(
        {"user_id": user["id"]}, {"_id": 0}).sort("created_at", -1).limit(limit).to_list(limit)
    return items


@wallet_r.post("/topup")
async def topup(body: TopUpIn, user: dict = Depends(get_current_user)):
    """Mock top-up. In production this would call Stripe and credit on webhook."""
    bonus = 0.0
    if body.note:
        promo = await db.promotions.find_one({"code": body.note.upper(), "active": True})
        if promo and body.amount >= float(promo.get("min_topup", 0)):
            if promo["type"] == "bonus_credit":
                bonus = float(promo["value"])
            elif promo["type"] == "percent_discount":
                bonus = round(body.amount * float(promo["value"]) / 100.0, 2)
    tx = await adjust_wallet(user["id"], body.amount, "topup",
                             note=f"Top-up via {body.method}", by=user["id"])
    if bonus > 0:
        await adjust_wallet(user["id"], bonus, "bonus", note=f"Promo {body.note}", by=user["id"])
        await add_notification(user["id"], "Bonus credit applied",
                               f"You received +${bonus:.2f} bonus.", "success")
    await add_notification(user["id"], "Wallet topped up",
                           f"+${body.amount:.2f} added to wallet.", "success")
    return {"ok": True, "tx": tx, "bonus": bonus}


# ============================================================
# CONTACTS
# ============================================================
contacts_r = APIRouter(prefix="/contacts", tags=["contacts"])


@contacts_r.get("")
async def list_contacts(user: dict = Depends(get_current_user)):
    items = await db.contacts.find({"user_id": user["id"]}, {"_id": 0}).to_list(2000)
    return items


@contacts_r.post("")
async def add_contact(body: ContactIn, user: dict = Depends(get_current_user)):
    doc = {"id": new_id(), "user_id": user["id"], **body.model_dump(),
           "created_at": iso(now_utc())}
    await db.contacts.insert_one(doc)
    return clean(doc)


@contacts_r.delete("/{cid}")
async def del_contact(cid: str, user: dict = Depends(get_current_user)):
    await db.contacts.delete_one({"id": cid, "user_id": user["id"]})
    return {"ok": True}


@contacts_r.get("/groups")
async def list_groups(user: dict = Depends(get_current_user)):
    items = await db.contact_groups.find({"user_id": user["id"]}, {"_id": 0}).to_list(500)
    return items


@contacts_r.post("/groups")
async def add_group(body: ContactGroupIn, user: dict = Depends(get_current_user)):
    doc = {"id": new_id(), "user_id": user["id"], **body.model_dump(),
           "created_at": iso(now_utc())}
    await db.contact_groups.insert_one(doc)
    return clean(doc)


@contacts_r.post("/import")
async def import_contacts(rows: List[ContactIn], user: dict = Depends(get_current_user)):
    docs = [{"id": new_id(), "user_id": user["id"], **r.model_dump(),
             "created_at": iso(now_utc())} for r in rows]
    if docs:
        await db.contacts.insert_many(docs)
    return {"ok": True, "count": len(docs)}


# ----- Group assignment & management -----

class GroupAssignIn(BaseModel):
    group_ids: List[str] = []  # replace the set; empty clears all groups


@contacts_r.post("/{cid}/groups")
async def assign_contact_to_groups(cid: str, body: GroupAssignIn,
                                     user: dict = Depends(get_current_user)):
    """Replace the full set of groups for this contact."""
    contact = await db.contacts.find_one({"id": cid, "user_id": user["id"]})
    if not contact:
        raise HTTPException(404, "Contact not found")
    # Sanity: every group_id must belong to this user
    if body.group_ids:
        n = await db.contact_groups.count_documents({
            "user_id": user["id"], "id": {"$in": body.group_ids}})
        if n != len(body.group_ids):
            raise HTTPException(400, "One or more groups not found")
    await db.contacts.update_one({"id": cid},
                                   {"$set": {"group_ids": body.group_ids}})
    return {"ok": True, "group_ids": body.group_ids}


class BulkGroupIn(BaseModel):
    contact_ids: List[str]
    action: str = "add"  # "add" | "remove"


@contacts_r.post("/groups/{gid}/bulk")
async def bulk_group(gid: str, body: BulkGroupIn,
                       user: dict = Depends(get_current_user)):
    group = await db.contact_groups.find_one({"id": gid, "user_id": user["id"]})
    if not group:
        raise HTTPException(404, "Group not found")
    op = "$addToSet" if body.action == "add" else "$pull"
    res = await db.contacts.update_many(
        {"id": {"$in": body.contact_ids}, "user_id": user["id"]},
        {op: {"group_ids": gid}})
    return {"ok": True, "modified": res.modified_count,
             "action": body.action, "group_id": gid}


@contacts_r.put("/groups/{gid}")
async def rename_group(gid: str, body: ContactGroupIn,
                         user: dict = Depends(get_current_user)):
    r = await db.contact_groups.find_one({"id": gid, "user_id": user["id"]})
    if not r:
        raise HTTPException(404, "Group not found")
    await db.contact_groups.update_one({"id": gid},
                                         {"$set": body.model_dump()})
    return {"ok": True}


@contacts_r.delete("/groups/{gid}")
async def delete_group(gid: str, user: dict = Depends(get_current_user)):
    r = await db.contact_groups.find_one({"id": gid, "user_id": user["id"]})
    if not r:
        raise HTTPException(404, "Group not found")
    # Remove the group id from all contacts
    await db.contacts.update_many({"user_id": user["id"], "group_ids": gid},
                                    {"$pull": {"group_ids": gid}})
    await db.contact_groups.delete_one({"id": gid})
    return {"ok": True}


# ============================================================
# NUMBER LOOKUP — "Smart validation" (in-house) + "HLR lookup" (telco-backed, per-country)
# Pricing & availability are driven from Settings Hub:
#   numbers.smart_validation_cost  (credits per number; default 1)
#   numbers.hlr_lookup_cost        (credits per number; default 5)
#   numbers.hlr_enabled_countries  (list of ISO-2 codes where real HLR is live; default [])
# ============================================================

PLAIN_DELIVERY_REASONS: Dict[str, str] = {
    "invalid_number": "Number is not in a valid format.",
    "unknown_subscriber": "Number is not assigned to any subscriber.",
    "absent_subscriber": "Phone is off or out of coverage.",
    "handset_busy": "Handset was busy; try again later.",
    "memory_full": "Handset inbox is full.",
    "blocked": "Carrier blocked the message (often spam filter).",
    "blacklisted": "Number is on your blacklist or the carrier's.",
    "sender_blacklisted": "Your sender ID is blocked by the carrier.",
    "expired": "Delivery attempt window expired before reaching the handset.",
    "no_route": "We have no active route for this country or operator.",
    "insufficient_funds": "Upstream provider account is out of balance.",
    "rejected_by_carrier": "Carrier rejected the message (check content rules).",
    "dnd_list": "Number is on the Do-Not-Disturb list.",
    "opted_out": "Recipient previously opted out (STOP).",
    "temporary_error": "Temporary carrier error; will be retried automatically.",
    "unknown_error": "Delivery failed for an unspecified reason.",
}


def classify_delivery_error(raw: Optional[str]) -> Dict[str, str]:
    """Map a carrier error string to a stable code + plain English reason."""
    text = (raw or "").lower().strip()
    mapping = [
        ("invalid", "invalid_number"),
        ("bad number", "invalid_number"),
        ("malformed", "invalid_number"),
        ("unknown subscriber", "unknown_subscriber"),
        ("not assigned", "unknown_subscriber"),
        ("absent", "absent_subscriber"),
        ("off", "absent_subscriber"),
        ("not reachable", "absent_subscriber"),
        ("out of coverage", "absent_subscriber"),
        ("busy", "handset_busy"),
        ("memory", "memory_full"),
        ("inbox full", "memory_full"),
        ("blocked", "blocked"),
        ("spam", "blocked"),
        ("blacklist", "blacklisted"),
        ("sender id", "sender_blacklisted"),
        ("expired", "expired"),
        ("no route", "no_route"),
        ("no provider", "no_route"),
        ("balance", "insufficient_funds"),
        ("insufficient", "insufficient_funds"),
        ("rejected", "rejected_by_carrier"),
        ("dnd", "dnd_list"),
        ("do not disturb", "dnd_list"),
        ("opt-out", "opted_out"),
        ("opted out", "opted_out"),
        ("stop", "opted_out"),
        ("temporary", "temporary_error"),
        ("retry", "temporary_error"),
    ]
    for needle, code in mapping:
        if needle in text:
            return {"code": code, "reason": PLAIN_DELIVERY_REASONS[code],
                     "raw": raw or ""}
    return {"code": "unknown_error",
             "reason": PLAIN_DELIVERY_REASONS["unknown_error"],
             "raw": raw or ""}


def normalize_phone(raw: str) -> str:
    p = (raw or "").strip().replace(" ", "").replace("-", "").replace("(", "").replace(")", "")
    if p and not p.startswith("+"):
        p = "+" + p
    return p


async def smart_validate_number(phone: str, user_id: Optional[str] = None) -> Dict[str, Any]:
    """In-house number validation. Uses prefix DB + historical delivery signal.
    Never claims the number is 'active right now' — that needs real HLR."""
    e164 = normalize_phone(phone)
    result: Dict[str, Any] = {
        "input": phone, "e164": e164, "valid": False, "country": None,
        "dial_code": None, "operator": None, "is_mobile": None,
        "carrier_type": None, "risk_flags": [], "first_seen": None,
        "last_delivery_status": None, "historical_success_rate": None,
        "service": "smart_validation",
    }
    if not e164.startswith("+") or len(e164) < 8 or len(e164) > 16:
        result["risk_flags"].append("Format looks wrong — check country code and length.")
        return result
    if not e164[1:].isdigit():
        result["risk_flags"].append("Contains non-digit characters.")
        return result

    # Prefix lookup
    op = await phone_to_operator(e164)
    if op:
        result["country"] = op.get("country")
        result["operator"] = op.get("operator")
        result["dial_code"] = op.get("prefix", "")[:4]
        result["is_mobile"] = True
        result["carrier_type"] = "mobile"
        result["valid"] = True
    else:
        result["risk_flags"].append("No operator found for this prefix in our database.")

    # Historical delivery signal for this user
    if user_id:
        agg = db.messages.aggregate([
            {"$match": {"user_id": user_id, "to": e164}},
            {"$group": {"_id": "$status", "n": {"$sum": 1},
                         "last": {"$max": "$created_at"}}},
        ])
        rows = await agg.to_list(10)
        if rows:
            total = sum(r["n"] for r in rows)
            delivered = sum(r["n"] for r in rows if r["_id"] in ("delivered", "sent"))
            result["historical_success_rate"] = round(delivered / total, 2) if total else None
            last_rows = sorted(rows, key=lambda r: r.get("last") or "", reverse=True)
            if last_rows:
                result["last_delivery_status"] = last_rows[0]["_id"]
                result["first_seen"] = last_rows[-1].get("last")
            # Suspicious signal
            if total >= 3 and (delivered / total) < 0.3:
                result["risk_flags"].append("Historical delivery to this number is poor.")
    return result


async def hlr_lookup_number(phone: str) -> Dict[str, Any]:
    """Real HLR via a telco partner — scaffolded but NOT live.
    Currently returns the smart-validation payload with service='hlr_lookup' so the
    data model is identical; wire real operator integration later.
    """
    base = await smart_validate_number(phone, user_id=None)
    base["service"] = "hlr_lookup"
    base["telco_backed"] = False  # flip to True when operator integration lands
    return base


class NumberLookupIn(BaseModel):
    phone: str
    service: str = "smart_validation"  # smart_validation | hlr_lookup


num_r = APIRouter(prefix="/numbers", tags=["numbers"])


async def _charge_lookup(user: dict, service: str, n: int = 1):
    if service == "hlr_lookup":
        cost_key = "numbers.hlr_lookup_cost"
        default = 5
    else:
        cost_key = "numbers.smart_validation_cost"
        default = 1
    unit = int(await get_setting(cost_key, default) or default)
    total = unit * n
    if total <= 0:
        return 0
    wallet = await db.wallets.find_one({"user_id": user["id"]}, {"_id": 0})
    if not wallet or wallet.get("balance", 0) < total:
        raise HTTPException(402, f"Not enough credits. You need {total} credits.")
    await adjust_wallet(user["id"], -total, "lookup",
                         note=f"{service} ({n} number{'s' if n != 1 else ''})")
    return total


@num_r.get("/services")
async def number_services(user: dict = Depends(get_current_user)):
    """Tell the client which number services are available and what they cost."""
    smart_cost = int(await get_setting("numbers.smart_validation_cost", 1) or 1)
    hlr_cost = int(await get_setting("numbers.hlr_lookup_cost", 5) or 5)
    hlr_countries = await get_setting("numbers.hlr_enabled_countries", []) or []
    return {
        "smart_validation": {
            "name": "Smart number validation",
            "description": "Checks format, country, operator and your own delivery history.",
            "cost_per_lookup": smart_cost, "available_everywhere": True,
        },
        "hlr_lookup": {
            "name": "HLR number lookup",
            "description": "Real-time check with the mobile operator network — confirms the number is active, ported or roaming.",
            "cost_per_lookup": hlr_cost,
            "available_everywhere": False,
            "available_countries": hlr_countries,
            "status": "coming_soon" if not hlr_countries else "live",
        },
    }


@num_r.post("/validate")
async def validate_single(body: NumberLookupIn,
                            user: dict = Depends(get_current_user)):
    if body.service == "hlr_lookup":
        hlr_countries = await get_setting("numbers.hlr_enabled_countries", []) or []
        op = await phone_to_operator(normalize_phone(body.phone))
        country = op["country"] if op else None
        if not hlr_countries:
            raise HTTPException(400,
                "HLR lookup is not live yet. We are onboarding operators — "
                "please use Smart validation or contact your account manager.")
        if country and country not in hlr_countries:
            raise HTTPException(400,
                f"HLR lookup is not available for {country} yet. "
                f"Currently live in: {', '.join(hlr_countries) or 'none'}.")
    charged = await _charge_lookup(user, body.service, 1)
    result = (await hlr_lookup_number(body.phone)) if body.service == "hlr_lookup" \
        else (await smart_validate_number(body.phone, user["id"]))
    result["credits_charged"] = charged
    await db.number_lookups.insert_one({
        "id": new_id(), "user_id": user["id"],
        "service": body.service, "phone": result.get("e164") or body.phone,
        "valid": result.get("valid"),
        "credits": charged, "created_at": iso(now_utc()),
    })
    return result


class BulkLookupIn(BaseModel):
    phones: List[str]
    service: str = "smart_validation"


@num_r.post("/validate/bulk")
async def validate_bulk(body: BulkLookupIn,
                          user: dict = Depends(get_current_user)):
    if not body.phones:
        raise HTTPException(400, "No numbers provided.")
    if len(body.phones) > 5000:
        raise HTTPException(400, "Maximum 5,000 numbers per batch.")
    # Filter HLR by availability
    if body.service == "hlr_lookup":
        hlr_countries = await get_setting("numbers.hlr_enabled_countries", []) or []
        if not hlr_countries:
            raise HTTPException(400,
                "HLR lookup is not live yet. We are onboarding operators — "
                "please use Smart validation or contact your account manager.")
    charged = await _charge_lookup(user, body.service, len(body.phones))
    results = []
    for p in body.phones:
        if body.service == "hlr_lookup":
            res = await hlr_lookup_number(p)
        else:
            res = await smart_validate_number(p, user["id"])
        results.append(res)
    await db.number_lookups.insert_one({
        "id": new_id(), "user_id": user["id"], "service": body.service,
        "phones_count": len(body.phones),
        "valid_count": sum(1 for r in results if r.get("valid")),
        "credits": charged, "created_at": iso(now_utc()),
    })
    valid = sum(1 for r in results if r.get("valid"))
    return {"total": len(results), "valid": valid,
             "invalid": len(results) - valid, "credits_charged": charged,
             "results": results}


@num_r.get("/history")
async def lookup_history(user: dict = Depends(get_current_user)):
    rows = await db.number_lookups.find(
        {"user_id": user["id"]}, {"_id": 0}).sort("created_at", -1).limit(100).to_list(100)
    return rows


class AutoCleanIn(BaseModel):
    group_id: Optional[str] = None  # None = clean all contacts
    remove_invalid: bool = True


@num_r.post("/auto-clean")
async def auto_clean_contacts(body: AutoCleanIn,
                                user: dict = Depends(get_current_user)):
    """Run Smart validation on the user's entire contact list (or one group),
    flag invalid numbers, and optionally delete them. Pricing: same as bulk smart
    validation — 1 credit per contact (configurable via numbers.smart_validation_cost)."""
    q: Dict[str, Any] = {"user_id": user["id"]}
    if body.group_id:
        q["group_ids"] = body.group_id
    contacts = await db.contacts.find(q, {"_id": 0}).to_list(10000)
    if not contacts:
        return {"total": 0, "valid": 0, "invalid": 0, "removed": 0, "credits_charged": 0}
    charged = await _charge_lookup(user, "smart_validation", len(contacts))
    invalid_ids = []
    results = []
    for c in contacts:
        r = await smart_validate_number(c["phone"], user["id"])
        results.append({"contact_id": c["id"], "phone": c["phone"],
                         "name": c.get("name", ""), "valid": r.get("valid"),
                         "country": r.get("country"), "operator": r.get("operator"),
                         "risk_flags": r.get("risk_flags", [])})
        if not r.get("valid"):
            invalid_ids.append(c["id"])
    removed = 0
    if body.remove_invalid and invalid_ids:
        res = await db.contacts.delete_many(
            {"user_id": user["id"], "id": {"$in": invalid_ids}})
        removed = res.deleted_count
    await db.number_lookups.insert_one({
        "id": new_id(), "user_id": user["id"], "service": "smart_validation",
        "phones_count": len(contacts), "valid_count": len(contacts) - len(invalid_ids),
        "credits": charged, "kind": "auto_clean",
        "group_id": body.group_id, "removed": removed,
        "created_at": iso(now_utc()),
    })
    return {"total": len(contacts), "valid": len(contacts) - len(invalid_ids),
             "invalid": len(invalid_ids), "removed": removed,
             "credits_charged": charged, "results": results[:500]}


# ============================================================
# SENDER IDS
# ============================================================
sid_r = APIRouter(prefix="/sender-ids", tags=["sender_ids"])


@sid_r.get("")
async def my_sender_ids(user: dict = Depends(get_current_user)):
    items = await db.sender_id_requests.find({"user_id": user["id"]}, {"_id": 0}).to_list(200)
    return items


@sid_r.post("")
async def request_sid(body: SenderIdRequestIn, user: dict = Depends(get_current_user)):
    doc = {"id": new_id(), "user_id": user["id"], **body.model_dump(),
           "status": "pending", "created_at": iso(now_utc())}
    await db.sender_id_requests.insert_one(doc)
    await add_notification(user["id"], "Sender ID submitted",
                           f"{body.sender_id} for {body.country} is under review.", "info")
    # notify admins
    admins = await db.users.find({"role": "super_admin"}, {"id": 1}).to_list(20)
    for a in admins:
        await add_notification(a["id"], "New sender ID request",
                               f"{body.sender_id} ({body.country}) by {user['email']}", "info")
    return clean(doc)


# ============================================================
# TEMPLATES
# ============================================================
tpl_r = APIRouter(prefix="/templates", tags=["templates"])


@tpl_r.get("")
async def list_templates(user: dict = Depends(get_current_user)):
    items = await db.templates.find({"user_id": user["id"]}, {"_id": 0}).to_list(200)
    return items


@tpl_r.post("")
async def add_template(body: TemplateIn, user: dict = Depends(get_current_user)):
    doc = {"id": new_id(), "user_id": user["id"], **body.model_dump(),
           "created_at": iso(now_utc())}
    await db.templates.insert_one(doc)
    return clean(doc)


@tpl_r.delete("/{tid}")
async def del_template(tid: str, user: dict = Depends(get_current_user)):
    await db.templates.delete_one({"id": tid, "user_id": user["id"]})
    return {"ok": True}


# ============================================================
# MESSAGING ENGINE
# ============================================================
msg_r = APIRouter(prefix="/messaging", tags=["messaging"])


def render(template: str, vars_: dict) -> str:
    out = template
    for k, v in vars_.items():
        out = out.replace("{" + str(k) + "}", str(v))
    return out


async def pick_provider_for(country: str, channel: str, operator: Optional[str] = None) -> Optional[dict]:
    """Operator-aware routing. Prefer providers that explicitly target the operator
    (via `operators` array). Fall back to country-only providers. Then catch-all."""
    cur = db.providers.find({
        "active": True,
        "channels": channel,
        "$or": [{"countries": country}, {"countries": "*"}, {"countries": []}],
    }, {"_id": 0}).sort("priority", 1)
    providers = await cur.to_list(100)
    if not providers:
        return None
    if operator:
        op_match = [p for p in providers if operator in (p.get("operators") or [])]
        if op_match:
            return op_match[0]
    no_op = [p for p in providers if not p.get("operators")]
    return no_op[0] if no_op else providers[0]


async def bump_streak(user_id: str) -> int:
    """Called after a successful campaign completion. Updates send_streak_days. Returns new streak."""
    user = await db.users.find_one({"id": user_id})
    if not user:
        return 0
    today = now_utc().date().isoformat()
    last = user.get("last_send_date")
    streak = int(user.get("send_streak_days", 0))
    if last == today:
        pass
    else:
        try:
            last_d = datetime.fromisoformat(last).date() if last else None
        except Exception:
            last_d = None
        if last_d and (now_utc().date() - last_d).days == 1:
            streak += 1
        else:
            streak = 1
    await db.users.update_one({"id": user_id},
                               {"$set": {"last_send_date": today,
                                         "send_streak_days": streak}})
    # milestone bonuses
    milestones = {7: "streak.7_day_bonus", 30: "streak.30_day_bonus", 90: "streak.90_day_bonus"}
    if streak in milestones:
        bonus = int(await get_setting(milestones[streak], 0) or 0)
        if bonus > 0:
            awarded = await db.streak_awards.find_one(
                {"user_id": user_id, "milestone": streak})
            if not awarded:
                await db.streak_awards.insert_one(
                    {"id": new_id(), "user_id": user_id, "milestone": streak,
                     "credits": bonus, "created_at": iso(now_utc())})
                await adjust_wallet(user_id, bonus, "streak_bonus",
                                     note=f"{streak}-day streak reward", by=user_id)
                await add_notification(user_id, f"🔥 {streak}-day streak!",
                                        f"+{bonus} bonus credits for sticking with it.",
                                        "success")
    return streak


async def get_reseller_commission(reseller_id: str, country: str, channel: str) -> float:
    """Fetch reseller's commission rate (fraction 0.0–1.0) paid out of admin margin.
    Clients ALWAYS pay the global retail rate; resellers never inflate end-client pricing.
    Lookup order: country+channel override → default (*+channel) override → reseller's
    `commission_rate` on user doc → global setting `pricing.reseller_commission_default`.
    """
    if not reseller_id:
        return 0.0
    rec = await db.reseller_pricing.find_one({
        "reseller_id": reseller_id, "country": country, "channel": channel,
        "active": True}, {"_id": 0})
    if rec and rec.get("commission_rate") is not None:
        return max(0.0, min(1.0, float(rec["commission_rate"])))
    default = await db.reseller_pricing.find_one({
        "reseller_id": reseller_id, "country": "*", "channel": channel,
        "active": True}, {"_id": 0})
    if default and default.get("commission_rate") is not None:
        return max(0.0, min(1.0, float(default["commission_rate"])))
    reseller = await db.users.find_one({"id": reseller_id}, {"commission_rate": 1})
    if reseller and reseller.get("commission_rate") is not None:
        return max(0.0, min(1.0, float(reseller["commission_rate"])))
    fallback = await get_setting("pricing.reseller_commission_default", 0.15)
    return max(0.0, min(1.0, float(fallback or 0.0)))


async def push_dlr_webhook(user: dict, message: dict):
    """Fire-and-forget DLR push to client-configured webhook URL."""
    url = user.get("dlr_webhook_url")
    if not url:
        return
    try:
        import httpx
        async with httpx.AsyncClient(timeout=5.0) as client:
            await client.post(url, json={
                "id": message.get("id"),
                "to": message.get("to"),
                "status": message.get("status"),
                "provider_msg_id": message.get("provider_msg_id"),
                "error": message.get("error"),
                "sender_id": message.get("sender_id"),
                "cost": message.get("cost"),
                "channel": message.get("channel"),
                "at": iso(now_utc()),
            }, headers={"X-unitxt-secret": user.get("dlr_webhook_secret", "")})
    except Exception:
        pass  # silent; carrier-style fire-and-forget


async def execute_campaign(campaign: dict):
    """Smart batching engine: processes up to 200k+ recipients per campaign.
    - Reserves all credits upfront (one debit) with reseller markup.
    - Processes recipients in chunks (default 1000 per batch).
    - Uses insert_many for messages; bulk updates status after each batch.
    - Refunds unused credits at the end.
    - Supports operator-aware provider selection per recipient.
    - Credits reseller markup and writes DLR webhooks fire-and-forget.
    """
    cid = campaign["id"]
    user_id = campaign["user_id"]
    channel = campaign["channel"]
    sender_id = campaign["sender_id"]
    country = campaign.get("country", "TZ")
    user = await db.users.find_one({"id": user_id})
    if not user:
        return
    base_rate = await credits_per_msg(country, channel)
    # Clients ALWAYS pay the global retail rate. Resellers earn a backend commission
    # out of admin's margin — they never inflate end-client pricing.
    rate = int(base_rate)
    commission = await get_reseller_commission(user.get("reseller_id"), country, channel)

    recipients = campaign["recipients"]
    n = len(recipients)
    # assume 1 segment for reservation; we'll true-up later per actual content
    tmpl = campaign.get("template") or campaign.get("message") or ""
    approx_seg = max(1, gsm_segments(tmpl or "x"))
    reserved = rate * approx_seg * n

    # Reserve upfront (single debit)
    try:
        await adjust_wallet(user_id, -reserved, "sms_reserve",
                            note=f"Reserve for campaign {campaign['name'][:40]} · {n} recipients",
                            ref=cid, by=user_id)
    except HTTPException:
        await db.campaigns.update_one({"id": cid}, {"$set": {
            "status": "failed", "error": "Insufficient credits (reservation)"}})
        await add_notification(user_id, "Campaign halted",
                                f"{campaign['name']}: not enough credits to reserve.",
                                "warning")
        return

    batch_size = int(await get_setting("queue.batch_size", 1000) or 1000)
    max_conc = int(await get_setting("queue.max_concurrency", 200) or 200)
    provider_usd_cache: Dict[str, float] = {}
    # Pre-cache providers by msg; we still need an adapter per provider
    adapter_cache: Dict[str, ProviderAdapter] = {}

    sent = delivered = failed = 0
    credits_used = 0
    usd_cost_total = 0.0
    reseller_earned = 0
    reseller_accum = 0.0  # fractional commission accumulator; finalized at end

    sem = asyncio.Semaphore(max_conc)

    async def process_recipient(r):
        text = render(campaign["template"], r) if campaign.get("template") else campaign["message"]
        seg = gsm_segments(text)
        credits_cost = rate * seg
        # operator-aware routing
        op = None
        if isinstance(r.get("phone"), str):
            m = await phone_to_operator(r["phone"])
            if m:
                op = m.get("operator")
        provider = await pick_provider_for(country, channel, op)
        if not provider:
            return {"ok": False, "status": "failed", "error": "NO_PROVIDER",
                    "seg": seg, "credits_cost": credits_cost, "usd_cost": 0,
                    "phone": r.get("phone"), "body": text, "provider_id": None,
                    "provider_msg_id": None}
        if provider["id"] not in adapter_cache:
            adapter_cache[provider["id"]] = adapter_for(provider)
            provider_usd_cache[provider["id"]] = float(provider.get("cost_per_sms", 0.01))
        adapter = adapter_cache[provider["id"]]
        usd_cost = round(provider_usd_cache[provider["id"]] * seg, 6)
        # send with retry under semaphore
        async with sem:
            last = None
            for attempt in range(int(await get_setting("queue.max_retries", 2) or 2) + 1):
                try:
                    res = await adapter.send(r["phone"], sender_id, text, channel)
                    last = res
                    if res["ok"]:
                        break
                except Exception as e:
                    last = {"ok": False, "status": "failed", "error": str(e),
                            "provider_msg_id": None}
                if attempt < 2:
                    await asyncio.sleep(0.1 * (attempt + 1))
            res = last or {"ok": False, "status": "failed", "error": "UNKNOWN"}
        return {**res, "seg": seg, "credits_cost": credits_cost, "usd_cost": usd_cost,
                "phone": r.get("phone"), "body": text, "provider_id": provider["id"]}

    # Process in batches
    for i in range(0, n, batch_size):
        chunk = recipients[i:i + batch_size]
        results = await asyncio.gather(*[process_recipient(r) for r in chunk])
        # Prepare bulk writes
        msg_docs = []
        rev_docs = []
        for res in results:
            sent += 1
            if res["ok"]:
                delivered += 1
            else:
                failed += 1
            credits_used += res["credits_cost"]
            usd_cost_total += res["usd_cost"]
            if commission > 0 and res["ok"]:
                reseller_accum += res["credits_cost"] * commission
            msg_docs.append({
                "id": new_id(), "campaign_id": cid, "user_id": user_id,
                "to": res["phone"], "channel": channel, "sender_id": sender_id,
                "body": res["body"], "segments": res["seg"],
                "cost": res["credits_cost"], "usd_cost": res["usd_cost"],
                "status": res.get("status") or ("sent" if res.get("ok") else "failed"),
                "provider_id": res.get("provider_id"),
                "provider_msg_id": res.get("provider_msg_id"),
                "error": res.get("error"),
                "created_at": iso(now_utc()),
                "sent_at": iso(now_utc()) if res.get("ok") else None,
            })
            rev_docs.append({
                "id": new_id(), "campaign_id": cid, "user_id": user_id,
                "reseller_id": user.get("reseller_id"),
                "country": country, "channel": channel,
                "credits": res["credits_cost"], "usd_cost": res["usd_cost"],
                "provider_id": res.get("provider_id"),
                "created_at": iso(now_utc()),
            })
        if msg_docs:
            await db.messages.insert_many(msg_docs, ordered=False)
            await db.platform_revenue_log.insert_many(rev_docs, ordered=False)
        # progress update
        await db.campaigns.update_one({"id": cid}, {"$set": {
            "sent": sent, "delivered": delivered, "failed": failed,
            "total_cost": credits_used,
            "progress_pct": round(min(100.0, sent / max(1, n) * 100), 2),
        }})
        # fire DLR webhooks (best-effort)
        if user.get("dlr_webhook_url"):
            asyncio.create_task(asyncio.gather(*[
                push_dlr_webhook(user, m) for m in msg_docs[:50]  # cap per batch
            ], return_exceptions=True))

    # Refund unused reservation
    refund = reserved - credits_used
    if refund > 0:
        await adjust_wallet(user_id, refund, "sms_refund",
                            note=f"Reconcile campaign {campaign['name'][:40]}",
                            ref=cid, by=user_id)
    elif refund < 0:
        # Under-reserved (unicode etc). Try to charge the delta; if fails, we still accept.
        try:
            await adjust_wallet(user_id, refund, "sms_topoff",
                                note=f"Top-off {campaign['name'][:40]}",
                                ref=cid, by=user_id)
        except HTTPException:
            pass

    # Reseller commission earnings (paid out of admin margin, not from client)
    reseller_earned = int(round(reseller_accum))
    if reseller_earned > 0 and user.get("reseller_id"):
        await adjust_wallet(user["reseller_id"], reseller_earned, "commission_earned",
                            note=f"Commission from client {user['email']}",
                            ref=cid, by=user_id)
        await add_notification(user["reseller_id"], "Commission earned",
                                f"+{reseller_earned} credits from client send.", "success")

    await db.campaigns.update_one({"id": cid}, {"$set": {
        "status": "completed", "sent": sent, "delivered": delivered,
        "failed": failed, "total_cost": credits_used,
        "total_usd_cost": round(usd_cost_total, 6),
        "progress_pct": 100.0, "completed_at": iso(now_utc()),
    }})
    await add_notification(user_id, "Campaign completed",
                           f"{campaign['name']}: {delivered}/{sent} delivered. {credits_used} credits used.",
                           "success")
    # streak
    if sent > 0:
        await bump_streak(user_id)
    # low credits check
    w = await get_or_create_wallet(user_id)
    thr = int(await get_setting("notifications.low_credits_threshold", 100) or 0)
    if thr > 0 and w["balance"] < thr:
        await add_notification(user_id, "Credits running low",
                               f"Your balance is {int(w['balance'])} credits. Top up to keep sending.",
                               "warning")


@msg_r.post("/quick-send")
async def quick_send(body: QuickSendIn, user: dict = Depends(get_current_user)):
    if user.get("status") == "inactive":
        raise HTTPException(403, "Account inactive. Restore it from Wallet → Recover.")
    recipients = [{"phone": p.strip()} for p in body.recipients if p.strip()]
    if not recipients:
        raise HTTPException(400, "No recipients")
    seg = gsm_segments(body.message)
    rate = await credits_per_msg(user.get("country", "TZ"), body.channel)
    est_credits = rate * seg * len(recipients)
    w = await get_or_create_wallet(user["id"])
    if w["balance"] < est_credits:
        raise HTTPException(400, f"Need {est_credits} credits, you have {int(w['balance'])}.")
    campaign = {
        "id": new_id(), "user_id": user["id"],
        "name": f"Quick send {now_utc().strftime('%H:%M')}",
        "channel": body.channel, "sender_id": body.sender_id,
        "country": user.get("country", "TZ"),
        "message": body.message, "template": body.message,
        "recipients": recipients, "total": len(recipients),
        "status": "running", "created_at": iso(now_utc()),
        "sent": 0, "delivered": 0, "failed": 0, "total_cost": 0,
        "kind": "quick", "schedule_at": body.schedule_at,
    }
    await db.campaigns.insert_one(campaign)
    asyncio.create_task(execute_campaign(campaign))
    await db.users.update_one({"id": user["id"]}, {"$set": {"last_active_at": iso(now_utc())}})
    return {"ok": True, "campaign_id": campaign["id"], "estimated_credits": est_credits}


@msg_r.post("/bulk-send")
async def bulk_send(body: BulkSendIn, user: dict = Depends(get_current_user)):
    if user.get("status") == "inactive":
        raise HTTPException(403, "Account inactive. Restore it from Wallet → Recover.")
    if not body.recipients:
        raise HTTPException(400, "No recipients")
    sample = render(body.template, body.recipients[0]) if body.recipients else body.template
    seg = gsm_segments(sample)
    rate = await credits_per_msg(user.get("country", "TZ"), body.channel)
    est_credits = rate * seg * len(body.recipients)
    w = await get_or_create_wallet(user["id"])
    if w["balance"] < est_credits:
        raise HTTPException(400, f"Need {est_credits} credits, you have {int(w['balance'])}.")
    campaign = {
        "id": new_id(), "user_id": user["id"], "name": body.name,
        "channel": body.channel, "sender_id": body.sender_id,
        "country": user.get("country", "TZ"),
        "message": body.template, "template": body.template,
        "recipients": body.recipients, "total": len(body.recipients),
        "status": "scheduled" if body.schedule_at else "running",
        "created_at": iso(now_utc()),
        "sent": 0, "delivered": 0, "failed": 0, "total_cost": 0,
        "kind": "bulk", "schedule_at": body.schedule_at,
    }
    await db.campaigns.insert_one(campaign)
    if not body.schedule_at:
        asyncio.create_task(execute_campaign(campaign))
    await db.users.update_one({"id": user["id"]}, {"$set": {"last_active_at": iso(now_utc())}})
    return {"ok": True, "campaign_id": campaign["id"], "estimated_credits": est_credits}


# Pre-flight (Wave A) and saved CSV mappings (Wave B) are mounted from
# routes.messaging_extras at the bottom of this file.


@msg_r.get("/campaigns")
async def my_campaigns(limit: int = 100, user: dict = Depends(get_current_user)):
    items = await db.campaigns.find({"user_id": user["id"]}, {"_id": 0, "recipients": 0}).sort(
        "created_at", -1).limit(limit).to_list(limit)
    return items


@msg_r.get("/campaigns/{cid}")
async def campaign_detail(cid: str, user: dict = Depends(get_current_user)):
    c = await db.campaigns.find_one({"id": cid}, {"_id": 0})
    if not c:
        raise HTTPException(404, "Not found")
    if c["user_id"] != user["id"] and user["role"] not in ("super_admin", "country_admin"):
        raise HTTPException(403)
    msgs = await db.messages.find({"campaign_id": cid}, {"_id": 0}).limit(500).to_list(500)
    # Enrich failed messages with plain-English reason
    for m in msgs:
        if m.get("status") in ("failed", "undelivered"):
            reason = classify_delivery_error(m.get("error"))
            m["failure_code"] = reason["code"]
            m["failure_reason"] = reason["reason"]
    # Failure breakdown for the campaign
    breakdown: Dict[str, Dict[str, Any]] = {}
    for m in msgs:
        if m.get("failure_code"):
            b = breakdown.setdefault(m["failure_code"],
                {"code": m["failure_code"], "reason": m["failure_reason"], "count": 0})
            b["count"] += 1
    return {"campaign": c, "messages": msgs,
             "failure_breakdown": sorted(breakdown.values(),
                                          key=lambda x: -x["count"])}


@msg_r.get("/messages")
async def my_messages(limit: int = 100, user: dict = Depends(get_current_user)):
    items = await db.messages.find({"user_id": user["id"]}, {"_id": 0}).sort(
        "created_at", -1).limit(limit).to_list(limit)
    for m in items:
        if m.get("status") in ("failed", "undelivered"):
            reason = classify_delivery_error(m.get("error"))
            m["failure_code"] = reason["code"]
            m["failure_reason"] = reason["reason"]
    return items


@msg_r.get("/stats")
async def my_stats(user: dict = Depends(get_current_user)):
    pipe = [
        {"$match": {"user_id": user["id"]}},
        {"$group": {"_id": "$status", "count": {"$sum": 1},
                    "cost": {"$sum": "$cost"}}}
    ]
    rows = await db.messages.aggregate(pipe).to_list(20)
    by_status = {r["_id"]: {"count": r["count"], "cost": round(r["cost"], 4)} for r in rows}
    total = sum(v["count"] for v in by_status.values())
    delivered = by_status.get("delivered", {"count": 0})["count"]
    return {
        "total_messages": total,
        "delivered": delivered,
        "failed": by_status.get("failed", {"count": 0})["count"],
        "delivery_rate": round((delivered / total * 100) if total else 0, 2),
        "by_status": by_status,
    }


@msg_r.get("/delivery-report")
async def delivery_report(days: int = 30,
                            user: dict = Depends(get_current_user)):
    """Daily delivery rate + plain-English failure reason breakdown."""
    since = now_utc() - timedelta(days=max(1, min(365, days)))
    q = {"user_id": user["id"], "created_at": {"$gte": iso(since)}}
    # by-day
    day_pipe = [
        {"$match": q},
        {"$group": {
            "_id": {"day": {"$substr": ["$created_at", 0, 10]}, "status": "$status"},
            "n": {"$sum": 1},
        }},
    ]
    day_rows = await db.messages.aggregate(day_pipe).to_list(2000)
    by_day: Dict[str, Dict[str, int]] = {}
    for r in day_rows:
        d = r["_id"]["day"]; s = r["_id"]["status"]; n = r["n"]
        by_day.setdefault(d, {"sent": 0, "delivered": 0, "failed": 0, "queued": 0})
        if s in ("sent", "delivered"):
            by_day[d]["delivered"] += n
        elif s in ("failed", "undelivered"):
            by_day[d]["failed"] += n
        else:
            by_day[d][s] = by_day[d].get(s, 0) + n
        by_day[d]["sent"] = (by_day[d]["delivered"] + by_day[d]["failed"]
                              + by_day[d].get("queued", 0))
    days_list = sorted(by_day.items())
    timeline = [{
        "day": d, **v,
        "delivery_rate": round((v["delivered"] / v["sent"] * 100)
                                 if v["sent"] else 0, 2),
    } for d, v in days_list]

    # overall counters
    total = sum(v["sent"] for _, v in days_list) or 0
    delivered_total = sum(v["delivered"] for _, v in days_list) or 0
    failed_total = sum(v["failed"] for _, v in days_list) or 0

    # failure reason breakdown
    fail_pipe = [
        {"$match": {**q, "status": {"$in": ["failed", "undelivered"]}}},
        {"$project": {"error": 1}},
        {"$limit": 5000},
    ]
    fails = await db.messages.aggregate(fail_pipe).to_list(5000)
    reason_counts: Dict[str, Dict[str, Any]] = {}
    for f in fails:
        reason = classify_delivery_error(f.get("error"))
        bucket = reason_counts.setdefault(reason["code"],
            {"code": reason["code"], "reason": reason["reason"], "count": 0})
        bucket["count"] += 1
    reasons = sorted(reason_counts.values(), key=lambda x: -x["count"])

    # by country
    country_pipe = [
        {"$match": q},
        {"$group": {"_id": {"country": "$country", "status": "$status"},
                     "n": {"$sum": 1}}},
    ]
    country_rows = await db.messages.aggregate(country_pipe).to_list(2000)
    by_country: Dict[str, Dict[str, int]] = {}
    for r in country_rows:
        c = r["_id"].get("country") or "—"
        s = r["_id"]["status"]
        by_country.setdefault(c, {"sent": 0, "delivered": 0, "failed": 0})
        if s in ("sent", "delivered"):
            by_country[c]["delivered"] += r["n"]
        elif s in ("failed", "undelivered"):
            by_country[c]["failed"] += r["n"]
        by_country[c]["sent"] = (by_country[c]["delivered"] + by_country[c]["failed"])
    countries = [{
        "country": c, **v,
        "delivery_rate": round((v["delivered"] / v["sent"] * 100)
                                 if v["sent"] else 0, 2),
    } for c, v in sorted(by_country.items(), key=lambda x: -x[1]["sent"])]

    return {
        "days": days,
        "total_sent": total,
        "delivered": delivered_total,
        "failed": failed_total,
        "delivery_rate": round((delivered_total / total * 100) if total else 0, 2),
        "timeline": timeline,
        "failure_reasons": reasons,
        "by_country": countries,
    }


# ============================================================
# RESELLER
# ============================================================
res_r = APIRouter(prefix="/reseller", tags=["reseller"])


@res_r.get("/clients")
async def reseller_clients(user: dict = Depends(require_roles("reseller"))):
    items = await db.users.find({"reseller_id": user["id"]}, {"_id": 0, "password_hash": 0}).to_list(500)
    # attach wallets
    for c in items:
        w = await db.wallets.find_one({"user_id": c["id"]}, {"_id": 0})
        c["wallet_balance"] = w["balance"] if w else 0
    return items


@res_r.post("/transfer")
async def reseller_transfer(body: TransferIn, user: dict = Depends(require_roles("reseller"))):
    target = await db.users.find_one({"id": body.target_user_id, "reseller_id": user["id"]})
    if not target:
        raise HTTPException(404, "Client not found under you")
    await adjust_wallet(user["id"], -body.amount, "transfer_out",
                        note=f"To {target['email']}", ref=target["id"], by=user["id"])
    await adjust_wallet(target["id"], body.amount, "transfer_in",
                        note=f"From reseller {user['email']}", ref=user["id"], by=user["id"])
    await add_notification(target["id"], "Credits received",
                           f"+${body.amount:.2f} from your reseller.", "success")
    return {"ok": True}


@res_r.get("/earnings")
async def reseller_earnings(user: dict = Depends(require_roles("reseller"))):
    # commission = sum(client_charge) * commission_rate
    rate = float(user.get("commission_rate", 0.1))
    client_ids = [c["id"] for c in await db.users.find(
        {"reseller_id": user["id"]}, {"id": 1}).to_list(500)]
    pipe = [
        {"$match": {"user_id": {"$in": client_ids}, "kind": "sms_charge"}},
        {"$group": {"_id": None, "total": {"$sum": "$amount"}}},
    ]
    rows = await db.wallet_transactions.aggregate(pipe).to_list(1)
    spent = -float(rows[0]["total"]) if rows else 0
    return {"clients": len(client_ids), "client_spend": round(spent, 4),
            "commission_rate": rate, "earned": round(spent * rate, 4)}


@res_r.get("/code")
async def reseller_code(user: dict = Depends(require_roles("reseller"))):
    return {"code": user.get("reseller_code", "")}


# ============================================================
# ADMIN
# ============================================================
adm_r = APIRouter(prefix="/admin", tags=["admin"])


@adm_r.get("/overview")
async def admin_overview(user: dict = Depends(require_roles("super_admin"))):
    users_total = await db.users.count_documents({})
    clients = await db.users.count_documents({"role": "client"})
    resellers = await db.users.count_documents({"role": "reseller"})
    msgs_total = await db.messages.count_documents({})
    delivered = await db.messages.count_documents({"status": "delivered"})
    failed = await db.messages.count_documents({"status": "failed"})
    pending_sids = await db.sender_id_requests.count_documents({"status": "pending"})
    # revenue = sum of charges (positive number)
    pipe = [{"$match": {"kind": "sms_charge"}},
            {"$group": {"_id": None, "total": {"$sum": "$amount"}}}]
    rows = await db.wallet_transactions.aggregate(pipe).to_list(1)
    revenue = round(-float(rows[0]["total"]) if rows else 0, 4)
    pipe2 = [{"$group": {"_id": None, "total": {"$sum": "$balance"}}}]
    rows2 = await db.wallets.aggregate(pipe2).to_list(1)
    liabilities = round(float(rows2[0]["total"]) if rows2 else 0, 4)
    # by country
    pipe3 = [{"$group": {"_id": "$country", "count": {"$sum": 1}}}]
    by_country = await db.users.aggregate(pipe3).to_list(50)
    # provider health
    providers = await db.providers.find({}, {"_id": 0}).to_list(50)
    # recent activity
    recent = await db.audit_logs.find({}, {"_id": 0}).sort("created_at", -1).limit(15).to_list(15)
    return {
        "kpi": {
            "users_total": users_total, "clients": clients, "resellers": resellers,
            "msgs_total": msgs_total, "delivered": delivered, "failed": failed,
            "delivery_rate": round((delivered / msgs_total * 100) if msgs_total else 0, 2),
            "pending_sender_ids": pending_sids,
            "revenue": revenue, "wallet_liabilities": liabilities,
        },
        "by_country": [{"country": r["_id"] or "??", "count": r["count"]} for r in by_country],
        "providers": providers,
        "recent_activity": recent,
    }


@adm_r.get("/users")
async def admin_users(user: dict = Depends(require_roles("super_admin", "country_admin"))):
    items = await db.users.find({}, {"_id": 0, "password_hash": 0}).to_list(2000)
    return items


@adm_r.patch("/users/{uid}")
async def admin_update_user(uid: str, body: Dict[str, Any],
                             user: dict = Depends(require_roles("super_admin"))):
    allowed = {k: v for k, v in body.items() if k in ("status", "role", "country", "name",
                                                       "commission_rate", "kyc_verified")}
    if not allowed:
        raise HTTPException(400, "No valid fields")
    await db.users.update_one({"id": uid}, {"$set": allowed})
    await add_audit(user["id"], "user.update", target=uid, meta=allowed)
    return {"ok": True}


@adm_r.post("/users/{uid}/credit")
async def admin_credit_user(uid: str, body: TopUpIn,
                             user: dict = Depends(require_roles("super_admin"))):
    tx = await adjust_wallet(uid, body.amount, "manual_adjustment",
                             note=body.note or "Admin credit", by=user["id"])
    await add_notification(uid, "Wallet credited",
                           f"Admin added ${body.amount:.2f}.", "success")
    await add_audit(user["id"], "wallet.credit", target=uid, meta={"amount": body.amount})
    return tx


@adm_r.get("/sender-ids")
async def admin_sids(user: dict = Depends(require_roles("super_admin", "compliance"))):
    items = await db.sender_id_requests.find({}, {"_id": 0}).sort("created_at", -1).to_list(500)
    return items


@adm_r.post("/sender-ids/{sid}/review")
async def admin_review_sid(sid: str, body: SenderIdReviewIn,
                            user: dict = Depends(require_roles("super_admin", "compliance"))):
    if body.status not in ("approved", "rejected"):
        raise HTTPException(400, "Invalid status")
    rec = await db.sender_id_requests.find_one({"id": sid})
    if not rec:
        raise HTTPException(404)
    patch = {
        "status": body.status, "review_note": body.note,
        "reviewed_by": user["id"], "reviewed_at": iso(now_utc())
    }
    if body.status == "approved":
        # charge credits from client (best-effort; if no balance we still approve but flag)
        cost = int(await get_setting("credits.sender_id_cost", 500) or 0)
        days = int(await get_setting("credits.sender_id_expiry_days", 365) or 365)
        w = await get_or_create_wallet(rec["user_id"])
        if cost > 0 and w["balance"] >= cost:
            await adjust_wallet(rec["user_id"], -cost, "sender_id_creation",
                                note=f"Sender ID {rec['sender_id']}",
                                ref=sid, by=user["id"])
        patch["expires_at"] = iso(now_utc() + timedelta(days=days))
    await db.sender_id_requests.update_one({"id": sid}, {"$set": patch})
    await add_notification(rec["user_id"], f"Sender ID {body.status}",
                           f"{rec['sender_id']} {body.status}.",
                           "success" if body.status == "approved" else "warning")
    await add_audit(user["id"], f"sender_id.{body.status}", target=sid)
    return {"ok": True}


# Countries
@adm_r.get("/countries")
async def list_countries(user: dict = Depends(get_current_user)):
    items = await db.countries.find({}, {"_id": 0}).to_list(300)
    return items


@adm_r.post("/countries")
async def add_country(body: CountryIn, user: dict = Depends(require_roles("super_admin"))):
    doc = {"id": new_id(), **body.model_dump(), "created_at": iso(now_utc())}
    await db.countries.insert_one(doc)
    return clean(doc)


@adm_r.patch("/countries/{cid}")
async def update_country(cid: str, body: Dict[str, Any],
                          user: dict = Depends(require_roles("super_admin"))):
    await db.countries.update_one({"id": cid}, {"$set": body})
    return {"ok": True}


# Providers
@adm_r.get("/providers")
async def list_providers(user: dict = Depends(require_roles("super_admin", "country_admin"))):
    items = await db.providers.find({}, {"_id": 0}).to_list(100)
    return items


@adm_r.post("/providers")
async def add_provider(body: ProviderIn, user: dict = Depends(require_roles("super_admin"))):
    doc = {"id": new_id(), **body.model_dump(), "health": "unknown",
           "created_at": iso(now_utc())}
    await db.providers.insert_one(doc)
    return clean(doc)


@adm_r.patch("/providers/{pid}")
async def update_provider(pid: str, body: Dict[str, Any],
                           user: dict = Depends(require_roles("super_admin"))):
    await db.providers.update_one({"id": pid}, {"$set": body})
    return {"ok": True}


@adm_r.delete("/providers/{pid}")
async def del_provider(pid: str, user: dict = Depends(require_roles("super_admin"))):
    await db.providers.delete_one({"id": pid})
    return {"ok": True}


# Pricing
@adm_r.get("/pricing")
async def list_pricing(user: dict = Depends(require_roles("super_admin", "country_admin"))):
    items = await db.pricing_plans.find({}, {"_id": 0}).to_list(500)
    return items


@adm_r.post("/pricing")
async def add_pricing(body: PricingPlanIn, user: dict = Depends(require_roles("super_admin"))):
    doc = {"id": new_id(), **body.model_dump(), "created_at": iso(now_utc())}
    await db.pricing_plans.insert_one(doc)
    return clean(doc)


@adm_r.patch("/pricing/{pid}")
async def update_pricing(pid: str, body: Dict[str, Any],
                          user: dict = Depends(require_roles("super_admin"))):
    await db.pricing_plans.update_one({"id": pid}, {"$set": body})
    return {"ok": True}


@adm_r.delete("/pricing/{pid}")
async def del_pricing(pid: str, user: dict = Depends(require_roles("super_admin"))):
    await db.pricing_plans.delete_one({"id": pid})
    return {"ok": True}


# Institutions
@adm_r.get("/institutions")
async def list_institutions(user: dict = Depends(require_roles("super_admin", "country_admin"))):
    items = await db.institutions.find({}, {"_id": 0}).to_list(100)
    return items


@adm_r.post("/institutions")
async def add_institution(body: InstitutionIn, user: dict = Depends(require_roles("super_admin"))):
    doc = {"id": new_id(), **body.model_dump(), "created_at": iso(now_utc())}
    await db.institutions.insert_one(doc)
    return clean(doc)


@adm_r.patch("/institutions/{iid}")
async def update_institution(iid: str, body: Dict[str, Any],
                              user: dict = Depends(require_roles("super_admin"))):
    await db.institutions.update_one({"id": iid}, {"$set": body})
    return {"ok": True}


# Promotions
@adm_r.get("/promotions")
async def list_promos(user: dict = Depends(require_roles("super_admin"))):
    items = await db.promotions.find({}, {"_id": 0}).to_list(100)
    return items


@adm_r.post("/promotions")
async def add_promo(body: PromotionIn, user: dict = Depends(require_roles("super_admin"))):
    doc = {"id": new_id(), **body.model_dump(), "created_at": iso(now_utc())}
    doc["code"] = doc["code"].upper()
    await db.promotions.insert_one(doc)
    return clean(doc)


@adm_r.patch("/promotions/{pid}")
async def update_promo(pid: str, body: Dict[str, Any],
                        user: dict = Depends(require_roles("super_admin"))):
    if "code" in body:
        body["code"] = body["code"].upper()
    await db.promotions.update_one({"id": pid}, {"$set": body})
    return {"ok": True}


@adm_r.delete("/promotions/{pid}")
async def del_promo(pid: str, user: dict = Depends(require_roles("super_admin"))):
    await db.promotions.delete_one({"id": pid})
    return {"ok": True}


# Settings (key/value driven)
@adm_r.get("/settings")
async def list_settings(category: Optional[str] = None,
                         user: dict = Depends(require_roles("super_admin"))):
    q = {"category": category} if category else {}
    items = await db.system_settings.find(q, {"_id": 0}).to_list(500)
    return items


@adm_r.put("/settings")
async def upsert_setting(body: SettingsIn,
                          user: dict = Depends(require_roles("super_admin"))):
    await db.system_settings.update_one(
        {"key": body.key},
        {"$set": {"key": body.key, "value": body.value, "category": body.category,
                  "updated_at": iso(now_utc()), "updated_by": user["id"]}},
        upsert=True)
    await add_audit(user["id"], "settings.update", target=body.key,
                     meta={"value": body.value})
    return {"ok": True}


@adm_r.get("/audit-logs")
async def admin_audit(limit: int = 200,
                       user: dict = Depends(require_roles("super_admin"))):
    items = await db.audit_logs.find({}, {"_id": 0}).sort(
        "created_at", -1).limit(limit).to_list(limit)
    return items


@adm_r.get("/campaigns")
async def admin_campaigns(limit: int = 100,
                           user: dict = Depends(require_roles("super_admin"))):
    items = await db.campaigns.find({}, {"_id": 0, "recipients": 0}).sort(
        "created_at", -1).limit(limit).to_list(limit)
    return items


@adm_r.get("/wallets")
async def admin_wallets(user: dict = Depends(require_roles("super_admin", "finance"))):
    items = await db.wallets.find({}, {"_id": 0}).to_list(2000)
    # attach user info
    user_ids = [w["user_id"] for w in items]
    users = await db.users.find({"id": {"$in": user_ids}},
                                 {"_id": 0, "id": 1, "email": 1, "name": 1, "role": 1}).to_list(2000)
    umap = {u["id"]: u for u in users}
    for w in items:
        w["user"] = umap.get(w["user_id"])
    return items


# Public list for any authed user (used by client to pick country)
@adm_r.get("/public/countries")
async def public_countries():
    items = await db.countries.find({"active": True}, {"_id": 0}).to_list(300)
    return items


# ============================================================
# NOTIFICATIONS
# ============================================================
notif_r = APIRouter(prefix="/notifications", tags=["notifications"])


@notif_r.get("")
async def my_notifs(user: dict = Depends(get_current_user)):
    items = await db.notifications.find(
        {"user_id": user["id"]}, {"_id": 0}).sort("created_at", -1).limit(100).to_list(100)
    unread = sum(1 for i in items if not i["read"])
    return {"items": items, "unread": unread}


@notif_r.post("/read-all")
async def mark_all_read(user: dict = Depends(get_current_user)):
    await db.notifications.update_many({"user_id": user["id"], "read": False},
                                        {"$set": {"read": True}})
    return {"ok": True}


@notif_r.post("/{nid}/read")
async def mark_one_read(nid: str, user: dict = Depends(get_current_user)):
    await db.notifications.update_one({"id": nid, "user_id": user["id"]},
                                       {"$set": {"read": True}})
    return {"ok": True}


# ============================================================
# API KEYS
# ============================================================
key_r = APIRouter(prefix="/api-keys", tags=["api_keys"])


@key_r.get("")
async def my_keys(user: dict = Depends(get_current_user)):
    items = await db.api_keys.find({"user_id": user["id"]}, {"_id": 0}).to_list(50)
    return items


@key_r.post("")
async def create_key(body: ApiKeyIn, user: dict = Depends(get_current_user)):
    key = "uxk_" + secrets.token_urlsafe(24)
    doc = {"id": new_id(), "user_id": user["id"], "name": body.name,
           "key": key, "active": True, "created_at": iso(now_utc())}
    await db.api_keys.insert_one(doc)
    return clean(doc)


@key_r.delete("/{kid}")
async def del_key(kid: str, user: dict = Depends(get_current_user)):
    await db.api_keys.delete_one({"id": kid, "user_id": user["id"]})
    return {"ok": True}


# ============================================================
# CREDITS (packs, buy, recover)
# ============================================================
credits_r = APIRouter(prefix="/credits", tags=["credits"])


class BuyPackIn(BaseModel):
    pack_id: str
    promo_code: Optional[str] = None


class AdminTransferIn(BaseModel):
    target_user_id: str
    credits: int = Field(gt=0)
    note: Optional[str] = None


@credits_r.get("/packs")
async def list_packs(user: dict = Depends(get_current_user)):
    """Return packs visible to this user:
    - Global packs (country == None)
    - Packs scoped to the user's country
    Country-scoped packs are shown first, then globals.
    Each pack is enriched with local currency pricing (falls back to USD).
    """
    user_country = user.get("country")
    q = {"active": True, "$or": [{"country": None}, {"country": {"$exists": False}}]}
    if user_country:
        q["$or"].append({"country": user_country})
    items = await db.credit_packs.find(q, {"_id": 0}).sort([
        ("country", -1), ("credits", 1)]).to_list(80)
    # Enrich with local pricing (defined later; forward-compatible lookup).
    from_country = user_country
    for p in items:
        c = await db.countries.find_one({"code": from_country}, {"_id": 0}) if from_country else None
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


@credits_r.get("/rates")
async def my_rates(user: dict = Depends(get_current_user)):
    rates = await get_setting("credits.country_rate", {}) or {}
    return {
        "country_rate": rates,
        "default_rate": await get_setting("credits.default_rate", 2),
        "whatsapp_rate": await get_setting("credits.whatsapp_rate", 3),
        "sender_id_cost": await get_setting("credits.sender_id_cost", 500),
        "sender_id_renewal": await get_setting("credits.sender_id_renewal", 500),
        "sender_id_expiry_days": await get_setting("credits.sender_id_expiry_days", 365),
        "inactivity_warn_days": await get_setting("credits.inactivity_warn_days", 30),
        "inactivity_suspend_days": await get_setting("credits.inactivity_suspend_days", 60),
        "inactivity_recovery_cost": await get_setting("credits.inactivity_recovery_cost", 1000),
    }


@credits_r.post("/buy")
async def buy_pack(body: BuyPackIn, user: dict = Depends(get_current_user)):
    """Mock payment — credits the wallet immediately with the pack's credits,
    applies promo bonus if applicable, records payment in platform_payments.
    Also pays referral reward (from pack profit) to the referrer if configured."""
    pack = await db.credit_packs.find_one({"id": body.pack_id, "active": True}, {"_id": 0})
    if not pack:
        raise HTTPException(404, "Pack not found")
    bonus = 0
    if body.promo_code:
        promo = await db.promotions.find_one({"code": body.promo_code.upper(), "active": True})
        if promo and float(pack.get("price_usd", 0)) >= float(promo.get("min_topup", 0)):
            promo_country = promo.get("country")
            user_country = user.get("country")
            # Enforce country scope: None = global; otherwise must match user country
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
    # Affiliate commission (replaces legacy referral_reward).
    # Pulls model + rate from Settings Hub keys (affiliate.*).
    try:
        from routes.affiliate import record_topup_commission
        await record_topup_commission(user, float(pack["price_usd"]), ref=payment_id)
    except Exception:
        pass
    await add_notification(user["id"], "Credits added",
                           f"+{total_credits} credits purchased. Happy sending!", "success")
    return {"ok": True, "credits_added": total_credits, "bonus": bonus}


@credits_r.post("/recover")
async def recover_account(user: dict = Depends(get_current_user)):
    if user.get("status") != "inactive":
        return {"ok": True, "message": "Account already active."}
    cost = int(await get_setting("credits.inactivity_recovery_cost", 1000) or 0)
    w = await get_or_create_wallet(user["id"])
    if w["balance"] < cost:
        raise HTTPException(400, f"Recovery needs {cost} credits. You have {int(w['balance'])}.")
    await adjust_wallet(user["id"], -cost, "account_recovery",
                        note="Inactivity recovery fee", by=user["id"])
    await db.users.update_one({"id": user["id"]},
                               {"$set": {"status": "active",
                                          "last_active_at": iso(now_utc())}})
    await add_notification(user["id"], "Account restored",
                           "Welcome back. You're active again.", "success")
    return {"ok": True}


# ============================================================
# MOBILE PREFIXES
# ============================================================
prefix_r = APIRouter(prefix="/prefixes", tags=["prefixes"])


class PrefixIn(BaseModel):
    country: str
    operator: str
    prefix: str
    active: bool = True


@prefix_r.get("")
async def list_prefixes(country: Optional[str] = None,
                         user: dict = Depends(get_current_user)):
    q = {"country": country} if country else {}
    return await db.mobile_prefixes.find(q, {"_id": 0}).sort("prefix", 1).to_list(5000)


@prefix_r.post("")
async def add_prefix(body: PrefixIn, user: dict = Depends(require_roles("super_admin"))):
    doc = {"id": new_id(), **body.model_dump(), "created_at": iso(now_utc())}
    await db.mobile_prefixes.insert_one(doc)
    return clean(doc)


@prefix_r.patch("/{pid}")
async def update_prefix(pid: str, body: Dict[str, Any],
                         user: dict = Depends(require_roles("super_admin"))):
    await db.mobile_prefixes.update_one({"id": pid}, {"$set": body})
    return {"ok": True}


@prefix_r.delete("/{pid}")
async def del_prefix(pid: str, user: dict = Depends(require_roles("super_admin"))):
    await db.mobile_prefixes.delete_one({"id": pid})
    return {"ok": True}


@prefix_r.post("/import")
async def import_prefixes(rows: List[PrefixIn],
                           user: dict = Depends(require_roles("super_admin"))):
    docs = [{"id": new_id(), **r.model_dump(), "created_at": iso(now_utc())} for r in rows]
    if docs:
        await db.mobile_prefixes.insert_many(docs)
    return {"ok": True, "count": len(docs)}


@prefix_r.get("/lookup")
async def lookup(phone: str, user: dict = Depends(get_current_user)):
    match = await phone_to_operator(phone)
    return {"match": match}


# ============================================================
# DLR WEBHOOK  (providers call this to update delivery status)
# ============================================================
dlr_r = APIRouter(prefix="/dlr", tags=["dlr"])


class DlrIn(BaseModel):
    provider_msg_id: str
    status: str  # delivered | failed | undelivered | expired
    error: Optional[str] = None


@dlr_r.post("/{provider_id}")
async def dlr_update(provider_id: str, body: DlrIn):
    # NB: in production, verify signature header against provider secret
    res = await db.messages.update_one(
        {"provider_id": provider_id, "provider_msg_id": body.provider_msg_id},
        {"$set": {"status": body.status, "error": body.error,
                  "delivered_at": iso(now_utc())}})
    return {"updated": res.modified_count}


# ============================================================
# ADMIN: credit packs, prefixes-bulk, reports, SID expiry
# ============================================================
class CreditPackIn(BaseModel):
    name: str
    credits: int = Field(gt=0)
    price_usd: float = Field(gt=0)
    tag: Optional[str] = None
    country: Optional[str] = None  # None = global pack. ISO-2 code = country-scoped.
    active: bool = True


adm_r2 = APIRouter(prefix="/admin", tags=["admin2"])


@adm_r2.get("/credit-packs")
async def a_list_packs(user: dict = Depends(require_roles("super_admin"))):
    return await db.credit_packs.find({}, {"_id": 0}).sort("credits", 1).to_list(100)


@adm_r2.post("/credit-packs")
async def a_add_pack(body: CreditPackIn, user: dict = Depends(require_roles("super_admin"))):
    doc = {"id": new_id(), **body.model_dump(), "created_at": iso(now_utc())}
    await db.credit_packs.insert_one(doc)
    return clean(doc)


@adm_r2.patch("/credit-packs/{pid}")
async def a_update_pack(pid: str, body: Dict[str, Any],
                          user: dict = Depends(require_roles("super_admin"))):
    await db.credit_packs.update_one({"id": pid}, {"$set": body})
    return {"ok": True}


@adm_r2.delete("/credit-packs/{pid}")
async def a_del_pack(pid: str, user: dict = Depends(require_roles("super_admin"))):
    await db.credit_packs.delete_one({"id": pid})
    return {"ok": True}


@adm_r2.get("/reports/margin")
async def reports_margin(user: dict = Depends(require_roles("super_admin", "finance"))):
    """Revenue (USD from pack purchases) vs Cost (USD to providers) = Margin."""
    # revenue
    pipe_rev = [{"$group": {"_id": None, "total": {"$sum": "$price_usd"}}}]
    rev_rows = await db.platform_payments.aggregate(pipe_rev).to_list(1)
    revenue = round(float(rev_rows[0]["total"]) if rev_rows else 0, 2)
    # cost
    pipe_cost = [{"$group": {"_id": None, "total": {"$sum": "$usd_cost"}}}]
    cost_rows = await db.platform_revenue_log.aggregate(pipe_cost).to_list(1)
    cost = round(float(cost_rows[0]["total"]) if cost_rows else 0, 4)
    # by country
    by_country = await db.platform_revenue_log.aggregate([
        {"$group": {"_id": "$country",
                    "msgs": {"$sum": 1},
                    "credits": {"$sum": "$credits"},
                    "cost": {"$sum": "$usd_cost"}}},
        {"$sort": {"cost": -1}},
    ]).to_list(50)
    # by reseller
    by_res = await db.platform_revenue_log.aggregate([
        {"$match": {"reseller_id": {"$ne": None}}},
        {"$group": {"_id": "$reseller_id",
                    "msgs": {"$sum": 1},
                    "credits": {"$sum": "$credits"},
                    "cost": {"$sum": "$usd_cost"}}},
    ]).to_list(50)
    # by provider
    by_prov = await db.platform_revenue_log.aggregate([
        {"$group": {"_id": "$provider_id",
                    "msgs": {"$sum": 1},
                    "cost": {"$sum": "$usd_cost"}}},
    ]).to_list(50)
    providers = {p["id"]: p["name"] for p in
                 await db.providers.find({}, {"id": 1, "name": 1, "_id": 0}).to_list(50)}
    # daily series (last 14 days)
    daily = await db.platform_revenue_log.aggregate([
        {"$group": {"_id": {"$substr": ["$created_at", 0, 10]},
                    "msgs": {"$sum": 1},
                    "cost": {"$sum": "$usd_cost"}}},
        {"$sort": {"_id": -1}}, {"$limit": 14},
    ]).to_list(14)
    return {
        "totals": {"revenue_usd": revenue, "cost_usd": cost,
                    "margin_usd": round(revenue - cost, 4),
                    "margin_pct": round((revenue - cost) / revenue * 100, 2) if revenue else 0},
        "by_country": [{"country": r["_id"] or "?", "msgs": r["msgs"],
                         "credits": r["credits"], "cost": round(r["cost"], 4)} for r in by_country],
        "by_reseller": by_res,
        "by_provider": [{"provider": providers.get(r["_id"], r["_id"]),
                          "msgs": r["msgs"], "cost": round(r["cost"], 4)} for r in by_prov],
        "daily": list(reversed([{"date": r["_id"], "msgs": r["msgs"],
                                   "cost": round(r["cost"], 4)} for r in daily])),
    }


# ============================================================
# Sender ID renewal
# ============================================================
@sid_r.post("/{sid}/renew")
async def renew_sender_id(sid: str, user: dict = Depends(get_current_user)):
    rec = await db.sender_id_requests.find_one({"id": sid, "user_id": user["id"]})
    if not rec:
        raise HTTPException(404)
    cost = int(await get_setting("credits.sender_id_renewal", 500) or 0)
    w = await get_or_create_wallet(user["id"])
    if w["balance"] < cost:
        raise HTTPException(400, f"Need {cost} credits to renew.")
    await adjust_wallet(user["id"], -cost, "sender_id_renewal",
                        note=f"Renew {rec['sender_id']}", ref=sid, by=user["id"])
    days = int(await get_setting("credits.sender_id_expiry_days", 365))
    new_exp = iso(now_utc() + timedelta(days=days))
    await db.sender_id_requests.update_one({"id": sid},
                                            {"$set": {"expires_at": new_exp,
                                                       "status": "approved"}})
    await add_notification(user["id"], "Sender ID renewed",
                           f"{rec['sender_id']} renewed for {days} days.", "success")
    return {"ok": True, "expires_at": new_exp}


# ============================================================
# Background tasks
# ============================================================
async def background_loop():
    """Runs forever: sender ID expiry + inactivity + scheduled-campaign dispatch."""
    await asyncio.sleep(5)
    while True:
        try:
            now = now_utc()
            # 1. SCHEDULED CAMPAIGN DRAINER (runs every cycle)
            cur = db.campaigns.find(
                {"status": "scheduled", "schedule_at": {"$lte": iso(now)}},
                {"_id": 0})
            async for c in cur:
                log.info(f"[scheduler] firing campaign {c['id']} ({c['name']})")
                await db.campaigns.update_one({"id": c["id"]},
                                                {"$set": {"status": "running"}})
                asyncio.create_task(execute_campaign(c))

            # 2. sender ID expiry → status=expired
            cur = db.sender_id_requests.find(
                {"status": "approved", "expires_at": {"$lt": iso(now)}},
                {"_id": 0})
            async for s in cur:
                await db.sender_id_requests.update_one({"id": s["id"]},
                                                        {"$set": {"status": "expired"}})
                await add_notification(s["user_id"], "Sender ID expired",
                                        f"{s['sender_id']} is expired. Renew to reuse.", "warning")
            # 3. inactivity
            warn_days = int(await get_setting("credits.inactivity_warn_days", 30) or 0)
            susp_days = int(await get_setting("credits.inactivity_suspend_days", 60) or 0)
            warn_before = iso(now - timedelta(days=warn_days))
            susp_before = iso(now - timedelta(days=susp_days))
            if susp_days > 0:
                async for u in db.users.find(
                        {"status": "active",
                         "role": {"$in": ["client", "reseller"]},
                         "$or": [{"last_active_at": {"$lt": susp_before}},
                                  {"last_active_at": {"$exists": False},
                                   "created_at": {"$lt": susp_before}}]},
                        {"_id": 0}):
                    await db.users.update_one({"id": u["id"]}, {"$set": {"status": "inactive"}})
                    await add_notification(u["id"], "Account set to inactive",
                                            "No activity for a while. Recover from the wallet "
                                            "to resume.", "warning")
            if warn_days > 0:
                async for u in db.users.find(
                        {"status": "active",
                         "last_active_at": {"$lt": warn_before},
                         "inactivity_warned": {"$ne": True}},
                        {"_id": 0}):
                    await db.users.update_one({"id": u["id"]},
                                               {"$set": {"inactivity_warned": True}})
                    await add_notification(u["id"], "Inactivity warning",
                                            f"You've been inactive for {warn_days}+ days. "
                                            f"Send something or your account will pause.", "warning")
        except Exception as e:
            log.exception(f"background_loop error: {e}")
        await asyncio.sleep(60)  # every minute (for scheduler responsiveness)


api.include_router(credits_r)
api.include_router(prefix_r)
api.include_router(dlr_r)
api.include_router(adm_r2)


# ============================================================
# REFERRALS (client-to-client)
# ============================================================
ref_r = APIRouter(prefix="/referrals", tags=["referrals"])


@ref_r.get("/me")
async def my_referral(user: dict = Depends(get_current_user)):
    code = user.get("referral_code")
    if not code:
        code = "U" + secrets.token_hex(3).upper()
        await db.users.update_one({"id": user["id"]},
                                   {"$set": {"referral_code": code}})
    # count referrals
    n = await db.users.count_documents({"referred_by": user["id"]})
    earned = int(user.get("referral_earned_credits", 0) or 0)
    return {"code": code, "referred_count": n, "earned": earned,
            "percent_of_pack": await get_setting("referral.percent_of_pack", 5),
            "max_per_referral": await get_setting("referral.max_credits_per_referral", 500),
            "active": await get_setting("referral.active", True)}


# ============================================================
# STREAK + PROFILE
# ============================================================
prof_r = APIRouter(prefix="/profile", tags=["profile"])


class WebhookIn(BaseModel):
    dlr_webhook_url: Optional[str] = ""
    dlr_webhook_secret: Optional[str] = ""


@prof_r.get("/streak")
async def my_streak(user: dict = Depends(get_current_user)):
    return {"streak": int(user.get("send_streak_days", 0) or 0),
            "last_send_date": user.get("last_send_date"),
            "bonuses": {
                "7": await get_setting("streak.7_day_bonus", 100),
                "30": await get_setting("streak.30_day_bonus", 1000),
                "90": await get_setting("streak.90_day_bonus", 5000),
            }}


@prof_r.put("/webhook")
async def set_webhook(body: WebhookIn, user: dict = Depends(get_current_user)):
    await db.users.update_one({"id": user["id"]}, {"$set": {
        "dlr_webhook_url": body.dlr_webhook_url or None,
        "dlr_webhook_secret": body.dlr_webhook_secret or None,
    }})
    return {"ok": True}


@prof_r.get("/webhook")
async def get_webhook(user: dict = Depends(get_current_user)):
    return {"dlr_webhook_url": user.get("dlr_webhook_url") or "",
            "dlr_webhook_secret": user.get("dlr_webhook_secret") or ""}


# ============================================================
# RESELLER COMMISSION (paid out of admin margin; client never overcharged)
# ============================================================
class ResellerCommissionIn(BaseModel):
    country: str  # "*" for default
    channel: str = "sms"
    commission_rate: float = Field(ge=0, le=1)  # 0.0–1.0 (e.g. 0.15 = 15%)
    active: bool = True


res_px_r = APIRouter(prefix="/reseller/pricing", tags=["reseller_pricing"])


@res_px_r.get("")
async def list_reseller_pricing(user: dict = Depends(require_roles("reseller"))):
    """Read-only: resellers see the commission rates set for them by admin."""
    items = await db.reseller_pricing.find({"reseller_id": user["id"]},
                                            {"_id": 0}).to_list(500)
    # Surface default commission from user doc + global fallback
    default_commission = user.get("commission_rate")
    if default_commission is None:
        default_commission = float(await get_setting("pricing.reseller_commission_default", 0.15) or 0.15)
    return {
        "default_commission_rate": float(default_commission),
        "overrides": items,
    }


# Admin-managed commission overrides per reseller
adm_res_px_r = APIRouter(prefix="/admin/resellers", tags=["admin_reseller_commission"])


@adm_res_px_r.get("/{reseller_id}/commissions")
async def admin_list_commissions(reseller_id: str,
                                   _: dict = Depends(require_roles("super_admin"))):
    items = await db.reseller_pricing.find({"reseller_id": reseller_id},
                                            {"_id": 0}).to_list(500)
    reseller = await db.users.find_one({"id": reseller_id, "role": "reseller"},
                                        {"_id": 0, "password_hash": 0})
    if not reseller:
        raise HTTPException(404, "Reseller not found")
    return {
        "reseller": {"id": reseller["id"], "email": reseller["email"],
                     "name": reseller.get("name", ""),
                     "default_commission_rate": reseller.get("commission_rate", 0.15)},
        "overrides": items,
    }


@adm_res_px_r.post("/{reseller_id}/commissions")
async def admin_set_commission(reseller_id: str, body: ResellerCommissionIn,
                                _: dict = Depends(require_roles("super_admin"))):
    reseller = await db.users.find_one({"id": reseller_id, "role": "reseller"})
    if not reseller:
        raise HTTPException(404, "Reseller not found")
    existing = await db.reseller_pricing.find_one({
        "reseller_id": reseller_id, "country": body.country, "channel": body.channel})
    doc = {"reseller_id": reseller_id, **body.model_dump(),
           "updated_at": iso(now_utc())}
    if existing:
        await db.reseller_pricing.update_one({"id": existing["id"]}, {"$set": doc})
        return {"ok": True, "id": existing["id"]}
    doc["id"] = new_id()
    doc["created_at"] = iso(now_utc())
    await db.reseller_pricing.insert_one(doc)
    return clean(doc)


@adm_res_px_r.delete("/{reseller_id}/commissions/{rid}")
async def admin_del_commission(reseller_id: str, rid: str,
                                 _: dict = Depends(require_roles("super_admin"))):
    await db.reseller_pricing.delete_one({"id": rid, "reseller_id": reseller_id})
    return {"ok": True}


@adm_res_px_r.put("/{reseller_id}/default-commission")
async def admin_set_default_commission(reseller_id: str,
                                         body: dict,
                                         _: dict = Depends(require_roles("super_admin"))):
    rate = float(body.get("commission_rate", 0.15))
    rate = max(0.0, min(1.0, rate))
    reseller = await db.users.find_one({"id": reseller_id, "role": "reseller"})
    if not reseller:
        raise HTTPException(404, "Reseller not found")
    await db.users.update_one({"id": reseller_id}, {"$set": {"commission_rate": rate}})
    return {"ok": True, "commission_rate": rate}


# ----- Reseller catalog, detail, float top-up, commission audit -----

@adm_res_px_r.get("")
async def admin_list_resellers(_: dict = Depends(require_roles("super_admin"))):
    """Reseller catalog with stats: float, client count, lifetime commission earned."""
    resellers = await db.users.find({"role": "reseller"},
                                      {"_id": 0, "password_hash": 0}).sort("created_at", -1).to_list(500)
    out = []
    for r in resellers:
        rid = r["id"]
        wallet = await db.wallets.find_one({"user_id": rid}, {"_id": 0}) or {"balance": 0}
        clients = await db.users.count_documents({"reseller_id": rid})
        # lifetime commission: sum of positive wallet tx with type commission_earned or markup_earned
        pipe = [
            {"$match": {"user_id": rid,
                         "kind": {"$in": ["commission_earned", "markup_earned"]}}},
            {"$group": {"_id": None, "total": {"$sum": "$amount"}}},
        ]
        agg = await db.wallet_transactions.aggregate(pipe).to_list(1)
        lifetime = int(agg[0]["total"]) if agg else 0
        last_seen = r.get("last_active_at") or r.get("created_at")
        out.append({
            "id": rid, "email": r["email"], "name": r.get("name", ""),
            "status": r.get("status", "active"),
            "business_name": r.get("business_name", ""),
            "country": r.get("country", ""),
            "reseller_code": r.get("reseller_code", ""),
            "commission_rate": float(r.get("commission_rate", 0.15)),
            "kyc_verified": bool(r.get("kyc_verified", False)),
            "float_balance": int(wallet.get("balance", 0)),
            "clients_count": clients,
            "lifetime_commission": lifetime,
            "last_active_at": last_seen,
            "created_at": r.get("created_at"),
        })
    return out


@adm_res_px_r.get("/{reseller_id}/detail")
async def admin_reseller_detail(reseller_id: str,
                                  _: dict = Depends(require_roles("super_admin"))):
    r = await db.users.find_one({"id": reseller_id, "role": "reseller"},
                                  {"_id": 0, "password_hash": 0})
    if not r:
        raise HTTPException(404, "Reseller not found")
    wallet = await db.wallets.find_one({"user_id": reseller_id}, {"_id": 0}) or {"balance": 0}
    clients = await db.users.find({"reseller_id": reseller_id},
                                    {"_id": 0, "password_hash": 0}).to_list(500)
    overrides = await db.reseller_pricing.find({"reseller_id": reseller_id},
                                                 {"_id": 0}).to_list(500)
    recent_commission = await db.wallet_transactions.find(
        {"user_id": reseller_id,
         "kind": {"$in": ["commission_earned", "markup_earned"]}},
        {"_id": 0}).sort("created_at", -1).limit(50).to_list(50)
    return {
        "reseller": r,
        "wallet": {"balance": int(wallet.get("balance", 0))},
        "clients": clients,
        "commission_overrides": overrides,
        "recent_commission": recent_commission,
    }


class ResellerFloatIn(BaseModel):
    amount: int  # positive to credit, negative to debit
    note: Optional[str] = None


@adm_res_px_r.post("/{reseller_id}/float")
async def admin_reseller_float(reseller_id: str, body: ResellerFloatIn,
                                admin: dict = Depends(require_roles("super_admin"))):
    r = await db.users.find_one({"id": reseller_id, "role": "reseller"})
    if not r:
        raise HTTPException(404, "Reseller not found")
    if body.amount == 0:
        raise HTTPException(400, "Amount must be non-zero")
    kind = "admin_topup" if body.amount > 0 else "admin_clawback"
    tx = await adjust_wallet(reseller_id, body.amount, kind,
                             note=body.note or f"Admin {kind}",
                             ref=admin["id"], by=admin["id"])
    await add_audit(admin["id"], f"reseller.{kind}", target=reseller_id,
                     meta={"amount": body.amount, "note": body.note})
    await add_notification(reseller_id,
                            "Float credited" if body.amount > 0 else "Float adjusted",
                            f"{'+' if body.amount > 0 else ''}{body.amount} credits by admin.",
                            "success" if body.amount > 0 else "info")
    return tx


@adm_res_px_r.put("/{reseller_id}/status")
async def admin_reseller_status(reseller_id: str, body: dict,
                                  admin: dict = Depends(require_roles("super_admin"))):
    status = body.get("status")
    if status not in ("active", "suspended", "inactive"):
        raise HTTPException(400, "status must be active, suspended or inactive")
    r = await db.users.find_one({"id": reseller_id, "role": "reseller"})
    if not r:
        raise HTTPException(404, "Reseller not found")
    await db.users.update_one({"id": reseller_id}, {"$set": {"status": status}})
    await add_audit(admin["id"], "reseller.status", target=reseller_id,
                     meta={"status": status})
    await add_notification(reseller_id, "Account status changed",
                            f"Your account is now {status}.",
                            "info" if status == "active" else "warning")
    return {"ok": True, "status": status}


@adm_res_px_r.get("/commission-audit")
async def admin_commission_audit(days: int = 30,
                                    _: dict = Depends(require_roles("super_admin"))):
    """Commission ledger across all resellers (last N days)."""
    since = now_utc() - timedelta(days=max(1, min(365, days)))
    rows = await db.wallet_transactions.find({
        "kind": {"$in": ["commission_earned", "markup_earned"]},
        "created_at": {"$gte": iso(since)},
    }, {"_id": 0}).sort("created_at", -1).limit(2000).to_list(2000)
    # enrich with reseller email
    ids = list({r["user_id"] for r in rows})
    users = {}
    if ids:
        async for u in db.users.find({"id": {"$in": ids}},
                                       {"_id": 0, "id": 1, "email": 1, "name": 1}):
            users[u["id"]] = u
    # totals by reseller
    totals: Dict[str, int] = {}
    for r in rows:
        totals[r["user_id"]] = totals.get(r["user_id"], 0) + int(r.get("amount", 0))
    by_reseller = [{
        "reseller_id": k,
        "email": users.get(k, {}).get("email", ""),
        "name": users.get(k, {}).get("name", ""),
        "total_credits": v,
    } for k, v in sorted(totals.items(), key=lambda x: -x[1])]
    # attach email to each tx
    for r in rows:
        u = users.get(r["user_id"], {})
        r["reseller_email"] = u.get("email", "")
        r["reseller_name"] = u.get("name", "")
    return {"since": iso(since), "transactions": rows, "by_reseller": by_reseller,
            "grand_total": sum(totals.values())}


# ============================================================
# COUNTRY HUB — unified per-country cockpit (catalog, detail, health)
# ============================================================

# Only the adapters actually implemented in this codebase. No defaults / placeholders.
INTEGRATED_ADAPTERS: List[Dict[str, Any]] = [
    {
        "kind": "twilio",
        "label": "Twilio",
        "description": "Global SMS/WhatsApp aggregator via Twilio REST API.",
        "channels": ["sms", "whatsapp"],
        "fields": [
            {"key": "api_key", "label": "Account SID", "type": "text", "required": True},
            {"key": "api_secret", "label": "Auth Token", "type": "password", "required": True},
            {"key": "base_url", "label": "Messaging base URL", "type": "text",
             "default": "https://api.twilio.com", "required": False},
        ],
    },
    {
        "kind": "tigo_tz",
        "label": "Tigo Tanzania (direct)",
        "description": "Direct-connect to Tigo TZ SMSC via IPsec VPN.",
        "channels": ["sms"],
        "fields": [
            {"key": "api_key", "label": "Username", "type": "text", "required": True},
            {"key": "api_secret", "label": "Password", "type": "password", "required": True},
            {"key": "base_url", "label": "VPN endpoint URL", "type": "text", "required": True},
        ],
    },
    {
        "kind": "mock",
        "label": "Mock (test-only)",
        "description": "Simulated provider — 95% success. Never use in production.",
        "channels": ["sms", "whatsapp"],
        "fields": [],
    },
]


def adapter_kind_from_name(name: str) -> str:
    n = (name or "").lower()
    if "twilio" in n: return "twilio"
    if "tigo" in n: return "tigo_tz"
    return "mock"


async def compute_provider_health(provider: dict, window_hours: int = 24) -> dict:
    """Compute a lightweight health signal based on real messages + creds + active flag."""
    if not provider.get("active"):
        return {"status": "offline", "success_rate": None, "sent_24h": 0,
                "creds": False, "last_error": None}
    kind = adapter_kind_from_name(provider.get("name", ""))
    creds_ok = (kind == "mock" or
                (bool(provider.get("api_key")) and bool(provider.get("api_secret"))))
    since = now_utc() - timedelta(hours=window_hours)
    pipe = [
        {"$match": {"provider_id": provider["id"], "created_at": {"$gte": iso(since)}}},
        {"$group": {"_id": "$status", "n": {"$sum": 1}}},
    ]
    counts = {row["_id"]: row["n"] async for row in db.messages.aggregate(pipe)}
    sent = sum(counts.values())
    delivered = counts.get("delivered", 0) + counts.get("sent", 0)
    failed = counts.get("failed", 0) + counts.get("undelivered", 0)
    rate = (delivered / sent) if sent else None
    last_err = None
    if failed:
        err_doc = await db.messages.find_one(
            {"provider_id": provider["id"], "status": {"$in": ["failed", "undelivered"]}},
            sort=[("created_at", -1)])
        if err_doc:
            last_err = err_doc.get("error")
    if not creds_ok:
        status = "degraded"
    elif sent == 0:
        status = "idle"
    elif rate is not None and rate >= 0.9:
        status = "healthy"
    elif rate is not None and rate >= 0.5:
        status = "degraded"
    else:
        status = "down"
    return {
        "status": status,
        "success_rate": round(rate, 4) if rate is not None else None,
        "sent_24h": sent, "delivered_24h": delivered, "failed_24h": failed,
        "creds": creds_ok, "last_error": last_err,
    }


async def country_health_summary(country_code: str, providers: List[dict]) -> dict:
    """Aggregate health across all providers of a country."""
    if not providers:
        return {"status": "unconfigured", "success_rate": None, "sent_24h": 0,
                "providers_total": 0, "providers_healthy": 0}
    results = [await compute_provider_health(p) for p in providers]
    sent = sum(r["sent_24h"] for r in results)
    delivered = sum(r.get("delivered_24h", 0) for r in results)
    healthy = sum(1 for r in results if r["status"] == "healthy")
    rate = (delivered / sent) if sent else None
    if all(r["status"] == "offline" for r in results):
        status = "offline"
    elif healthy == 0 and sent == 0:
        status = "idle"
    elif rate is not None and rate >= 0.9:
        status = "healthy"
    elif rate is not None and rate >= 0.5:
        status = "degraded"
    elif sent > 0:
        status = "down"
    else:
        status = "idle"
    return {"status": status, "success_rate": round(rate, 4) if rate is not None else None,
            "sent_24h": sent, "providers_total": len(results),
            "providers_healthy": healthy}


country_r = APIRouter(prefix="/admin/country-hub", tags=["country_hub"])


@country_r.get("/integrations/available")
async def available_integrations(_: dict = Depends(require_roles("super_admin"))):
    """Return ONLY the adapters actually implemented. No defaults."""
    return INTEGRATED_ADAPTERS


@country_r.get("")
async def country_catalog(_: dict = Depends(require_roles("super_admin", "country_admin"))):
    """Catalog of all countries with enriched summary (routes, prefixes, sender IDs, health)."""
    countries = await db.countries.find({}, {"_id": 0}).sort("name", 1).to_list(500)
    out = []
    for c in countries:
        code = c["code"]
        providers = await db.providers.find(
            {"countries": code, "active": True}, {"_id": 0}).sort("priority", 1).to_list(50)
        prefixes_count = await db.mobile_prefixes.count_documents({"country": code})
        operators = await db.mobile_prefixes.distinct("operator", {"country": code})
        sender_ids = await db.sender_id_requests.count_documents({"country": code})
        active_sender_ids = await db.sender_id_requests.count_documents(
            {"country": code, "status": "approved"})
        health = await country_health_summary(code, providers)
        rates_setting = await get_setting("credits.country_rate", {}) or {}
        out.append({
            **c,
            "credits_per_sms": rates_setting.get(code,
                await get_setting("credits.default_rate", 2)),
            "routes_count": len(providers),
            "prefixes_count": prefixes_count,
            "operators_count": len(operators),
            "sender_ids_count": sender_ids,
            "active_sender_ids": active_sender_ids,
            "health": health,
            "status": c.get("status") or ("active" if c.get("active", True) else "paused"),
        })
    return out


class CountryWizardIn(BaseModel):
    # Step 1 — identity
    code: str
    name: str
    dial_code: str = "+1"
    currency: str = "USD"
    timezone: Optional[str] = None
    # Step 2 — pricing
    credits_per_sms: int = 2
    credits_per_whatsapp: int = 3
    # Step 3 — routes (at least one required to go live)
    routes: List[Dict[str, Any]] = []  # [{provider_id: str, priority: int, operators: [str]}]
    # Step 4 — compliance
    sender_id_required: bool = True
    opt_out_footer: Optional[str] = ""
    allowed_sender_patterns: List[str] = []
    daily_cap: int = 100000


@country_r.post("/wizard")
async def country_wizard(body: CountryWizardIn,
                          _: dict = Depends(require_roles("super_admin"))):
    """Add/upsert a country via the guided wizard. Requires ALL 4 steps to go live;
    if any section is missing (no routes, etc.) the country stays in 'draft'."""
    code = body.code.upper().strip()
    if not code or not body.name.strip():
        raise HTTPException(400, "code and name are required")
    # Determine status — mandatory setup before 'active'
    has_identity = bool(body.code and body.name and body.dial_code)
    has_pricing = body.credits_per_sms > 0 and body.credits_per_whatsapp > 0
    has_routes = len(body.routes) > 0
    has_compliance = body.opt_out_footer is not None  # footer can be empty string
    complete = has_identity and has_pricing and has_routes and has_compliance
    status = "active" if complete else "draft"

    existing = await db.countries.find_one({"code": code})
    doc = {
        "code": code, "name": body.name, "dial_code": body.dial_code,
        "currency": body.currency,
        "timezone": body.timezone,
        "sender_id_required": body.sender_id_required,
        "opt_out_footer": body.opt_out_footer,
        "allowed_sender_patterns": body.allowed_sender_patterns,
        "daily_cap": body.daily_cap,
        "status": status, "active": status == "active",
        "updated_at": iso(now_utc()),
    }
    if existing:
        await db.countries.update_one({"code": code}, {"$set": doc})
        country_id = existing["id"]
    else:
        doc["id"] = new_id()
        doc["created_at"] = iso(now_utc())
        await db.countries.insert_one(doc)
        country_id = doc["id"]

    # Upsert credits rate
    rates = await get_setting("credits.country_rate", {}) or {}
    rates[code] = int(body.credits_per_sms)
    await db.system_settings.update_one(
        {"key": "credits.country_rate"},
        {"$set": {"key": "credits.country_rate", "value": rates, "category": "credits",
                  "updated_at": iso(now_utc())}},
        upsert=True)

    # Wire routes: for each route entry, append this country to provider.countries
    for r in body.routes:
        pid = r.get("provider_id")
        if not pid:
            continue
        update: Dict[str, Any] = {"$addToSet": {"countries": code}}
        sets: Dict[str, Any] = {}
        if "priority" in r:
            sets["priority"] = int(r["priority"])
        if "operators" in r and isinstance(r["operators"], list):
            sets["operators"] = r["operators"]
        if sets:
            update["$set"] = sets
        await db.providers.update_one({"id": pid}, update)

    await add_audit(_["id"], "country.wizard", target=code,
                     meta={"status": status, "routes": len(body.routes)})
    return {"ok": True, "code": code, "id": country_id, "status": status,
            "complete": complete,
            "missing": [k for k, v in {
                "identity": has_identity, "pricing": has_pricing,
                "routes": has_routes, "compliance": has_compliance}.items() if not v]}


@country_r.get("/{code}")
async def country_detail(code: str,
                          _: dict = Depends(require_roles("super_admin", "country_admin"))):
    code = code.upper()
    country = await db.countries.find_one({"code": code}, {"_id": 0})
    if not country:
        raise HTTPException(404, "Country not found")
    providers = await db.providers.find(
        {"countries": code}, {"_id": 0}).sort("priority", 1).to_list(50)
    health_list = []
    for p in providers:
        h = await compute_provider_health(p)
        kind = adapter_kind_from_name(p.get("name", ""))
        health_list.append({**p, "health": h, "kind": kind})
    prefixes = await db.mobile_prefixes.find(
        {"country": code}, {"_id": 0}).sort("prefix", 1).to_list(2000)
    operators: Dict[str, int] = {}
    for pf in prefixes:
        operators[pf.get("operator", "Unknown")] = operators.get(pf.get("operator", "Unknown"), 0) + 1
    sender_ids = await db.sender_id_requests.find(
        {"country": code}, {"_id": 0}).sort("created_at", -1).to_list(200)
    rates_setting = await get_setting("credits.country_rate", {}) or {}
    credits_per_sms = rates_setting.get(code,
        await get_setting("credits.default_rate", 2))
    health = await country_health_summary(code, [p for p in providers if p.get("active")])
    return {
        "country": country,
        "credits_per_sms": credits_per_sms,
        "credits_per_whatsapp": await get_setting("credits.whatsapp_rate", 3),
        "routes": health_list,
        "operators": [{"name": k, "prefixes": v} for k, v in
                       sorted(operators.items(), key=lambda x: -x[1])],
        "prefixes": prefixes[:500],
        "sender_ids": sender_ids,
        "health": health,
    }


class CountryStatusIn(BaseModel):
    status: str  # active | paused | draft


@country_r.put("/{code}/status")
async def country_status(code: str, body: CountryStatusIn,
                          admin: dict = Depends(require_roles("super_admin"))):
    if body.status not in ("active", "paused", "draft"):
        raise HTTPException(400, "Invalid status")
    code = code.upper()
    r = await db.countries.find_one({"code": code})
    if not r:
        raise HTTPException(404, "Country not found")
    await db.countries.update_one({"code": code}, {"$set": {
        "status": body.status, "active": body.status == "active",
        "updated_at": iso(now_utc())}})
    await add_audit(admin["id"], "country.status", target=code, meta={"status": body.status})
    return {"ok": True, "status": body.status}


@country_r.put("/{code}/compliance")
async def country_compliance(code: str, body: Dict[str, Any],
                               admin: dict = Depends(require_roles("super_admin"))):
    allowed = {k: body[k] for k in ["opt_out_footer", "allowed_sender_patterns",
                                      "daily_cap", "sender_id_required"]
                if k in body}
    if not allowed:
        raise HTTPException(400, "Nothing to update")
    await db.countries.update_one({"code": code.upper()},
                                    {"$set": {**allowed, "updated_at": iso(now_utc())}})
    await add_audit(admin["id"], "country.compliance", target=code.upper(), meta=allowed)
    return {"ok": True}


# ---------- Integration health dashboard ----------
int_r = APIRouter(prefix="/admin/integrations", tags=["integrations"])


@int_r.get("/health")
async def integrations_health(_: dict = Depends(require_roles("super_admin", "country_admin"))):
    """Every provider × every country it covers, with health."""
    providers = await db.providers.find({}, {"_id": 0}).to_list(200)
    out = []
    for p in providers:
        h = await compute_provider_health(p)
        out.append({
            "id": p["id"], "name": p["name"],
            "kind": adapter_kind_from_name(p.get("name", "")),
            "countries": p.get("countries", []),
            "channels": p.get("channels", []),
            "operators": p.get("operators", []),
            "priority": p.get("priority", 100),
            "active": p.get("active", True),
            "cost_per_sms": p.get("cost_per_sms"),
            "health": h,
        })
    out.sort(key=lambda x: (x["health"]["status"] != "healthy", -x["health"]["sent_24h"]))
    return out


@int_r.post("/{provider_id}/test")
async def integrations_test(provider_id: str,
                              _: dict = Depends(require_roles("super_admin"))):
    """Test connection — runs a lightweight adapter.send() with a test number.
    For stub adapters this returns simulated result; for real Twilio/Tigo calls happen."""
    p = await db.providers.find_one({"id": provider_id}, {"_id": 0})
    if not p:
        raise HTTPException(404, "Provider not found")
    adapter = adapter_for(p)
    start = time.time()
    try:
        res = await adapter.send("+10000000000", "TEST", "unitxt health ping", "sms")
        latency_ms = int((time.time() - start) * 1000)
        return {"ok": bool(res.get("ok")),
                "latency_ms": latency_ms,
                "provider_msg_id": res.get("provider_msg_id"),
                "status": res.get("status"),
                "error": res.get("error")}
    except Exception as e:
        return {"ok": False, "latency_ms": int((time.time() - start) * 1000),
                "error": str(e)}


# ============================================================
# UNIFIED APPROVALS INBOX (Sender IDs + WhatsApp templates + Reseller KYC + Institution apps)
# ============================================================
approv_r = APIRouter(prefix="/admin/approvals", tags=["approvals"])


@approv_r.get("/inbox")
async def approvals_inbox(_: dict = Depends(require_roles("super_admin", "compliance"))):
    """Single pending-approvals inbox across all queues."""
    sids = await db.sender_id_requests.find(
        {"status": "pending"}, {"_id": 0}).sort("created_at", -1).to_list(200)
    watpls = await db.wa_templates.find(
        {"status": "pending"}, {"_id": 0}).sort("created_at", -1).to_list(200)
    reseller_apps = await db.reseller_applications.find(
        {"status": "pending"}, {"_id": 0}).sort("created_at", -1).to_list(200)
    inst_apps = await db.institution_applications.find(
        {"status": "pending"}, {"_id": 0}).sort("created_at", -1).to_list(200)
    topups = await db.topup_requests.find(
        {"status": "pending"}, {"_id": 0, "proof_image": 0}).sort(
        "created_at", -1).to_list(200)
    # enrich with user info
    async def enrich(rows, user_key="user_id"):
        ids = list({r.get(user_key) for r in rows if r.get(user_key)})
        users = {}
        if ids:
            async for u in db.users.find({"id": {"$in": ids}},
                                           {"_id": 0, "id": 1, "email": 1, "name": 1}):
                users[u["id"]] = u
        for r in rows:
            u = users.get(r.get(user_key), {})
            r["requester_email"] = u.get("email", "")
            r["requester_name"] = u.get("name", "")
        return rows
    return {
        "sender_ids": await enrich(sids),
        "wa_templates": await enrich(watpls),
        "reseller_applications": reseller_apps,
        "institution_applications": inst_apps,
        "topup_requests": topups,
        "total": len(sids) + len(watpls) + len(reseller_apps) + len(inst_apps) + len(topups),
    }


# ============================================================
# RESELLER APPLICATIONS (public form → admin KYC review)
# ============================================================
class ResellerApplicationIn(BaseModel):
    company_name: str
    contact_name: str
    email: EmailStr
    phone: Optional[str] = ""
    country: str
    website: Optional[str] = ""
    expected_monthly_volume: Optional[int] = 0
    pitch: Optional[str] = ""  # why they want to resell
    agree_terms: bool = True


pub_r = APIRouter(prefix="/public", tags=["public_apply"])


@pub_r.post("/apply/reseller")
async def apply_reseller(body: ResellerApplicationIn):
    if not body.agree_terms:
        raise HTTPException(400, "You must accept the reseller terms.")
    # Dedupe on email
    existing = await db.reseller_applications.find_one({"email": body.email,
                                                         "status": "pending"})
    if existing:
        raise HTTPException(400, "We already have a pending application for this email.")
    doc = {"id": new_id(), **body.model_dump(), "status": "pending",
           "created_at": iso(now_utc())}
    await db.reseller_applications.insert_one(doc)
    return {"ok": True, "id": doc["id"]}


class InstitutionApplicationIn(BaseModel):
    institution_name: str
    institution_type: str = "bank"  # bank | mobile_money | fintech | enterprise
    contact_name: str
    email: EmailStr
    phone: Optional[str] = ""
    country: str
    use_cases: Optional[str] = ""  # what they want to send (OTP, marketing, etc.)
    expected_monthly_volume: Optional[int] = 0
    api_integration_needed: bool = True


@pub_r.post("/apply/institution")
async def apply_institution(body: InstitutionApplicationIn):
    existing = await db.institution_applications.find_one({"email": body.email,
                                                             "status": "pending"})
    if existing:
        raise HTTPException(400, "We already have a pending application for this email.")
    doc = {"id": new_id(), **body.model_dump(), "status": "pending",
           "created_at": iso(now_utc())}
    await db.institution_applications.insert_one(doc)
    return {"ok": True, "id": doc["id"]}


# Admin side: list/review reseller + institution applications
app_r = APIRouter(prefix="/admin/applications", tags=["applications"])


class ApplicationReviewIn(BaseModel):
    status: str  # approved | rejected
    note: Optional[str] = ""


@app_r.get("/resellers")
async def list_reseller_apps(status: Optional[str] = None,
                               _: dict = Depends(require_roles("super_admin"))):
    q = {"status": status} if status else {}
    items = await db.reseller_applications.find(q, {"_id": 0}).sort("created_at", -1).to_list(500)
    return items


@app_r.post("/resellers/{aid}/review")
async def review_reseller_app(aid: str, body: ApplicationReviewIn,
                                admin: dict = Depends(require_roles("super_admin"))):
    if body.status not in ("approved", "rejected"):
        raise HTTPException(400, "Invalid status")
    a = await db.reseller_applications.find_one({"id": aid})
    if not a:
        raise HTTPException(404, "Application not found")
    await db.reseller_applications.update_one({"id": aid}, {"$set": {
        "status": body.status, "note": body.note,
        "reviewed_at": iso(now_utc()), "reviewed_by": admin["id"],
    }})
    # If approved, create a reseller user with a temp password; owner sets it via reset flow later.
    if body.status == "approved":
        existing_user = await db.users.find_one({"email": a["email"]})
        if not existing_user:
            tmp_pwd = secrets.token_hex(6)
            user = {
                "id": new_id(), "email": a["email"], "name": a["contact_name"],
                "role": "reseller", "business_name": a["company_name"],
                "country": a["country"], "phone": a.get("phone", ""),
                "password_hash": bcrypt.hashpw(tmp_pwd.encode(), bcrypt.gensalt()).decode(),
                "status": "active", "kyc_verified": True,
                "reseller_code": "R" + secrets.token_hex(3).upper(),
                "commission_rate": float(await get_setting(
                    "pricing.reseller_commission_default", 0.15)),
                "created_at": iso(now_utc()),
            }
            await db.users.insert_one(user)
            await get_or_create_wallet(user["id"])
            await db.reseller_applications.update_one({"id": aid}, {"$set": {
                "provisioned_user_id": user["id"], "temp_password": tmp_pwd,
            }})
    await add_audit(admin["id"], f"reseller_app.{body.status}", target=aid,
                     meta={"note": body.note})
    return {"ok": True}


@app_r.get("/institutions")
async def list_inst_apps(status: Optional[str] = None,
                           _: dict = Depends(require_roles("super_admin"))):
    q = {"status": status} if status else {}
    items = await db.institution_applications.find(q, {"_id": 0}).sort("created_at", -1).to_list(500)
    return items


@app_r.post("/institutions/{aid}/review")
async def review_inst_app(aid: str, body: ApplicationReviewIn,
                             admin: dict = Depends(require_roles("super_admin"))):
    if body.status not in ("approved", "rejected"):
        raise HTTPException(400, "Invalid status")
    a = await db.institution_applications.find_one({"id": aid})
    if not a:
        raise HTTPException(404, "Application not found")
    await db.institution_applications.update_one({"id": aid}, {"$set": {
        "status": body.status, "note": body.note,
        "reviewed_at": iso(now_utc()), "reviewed_by": admin["id"],
    }})
    if body.status == "approved":
        # Promote to institutions collection
        doc = {
            "id": new_id(),
            "name": a["institution_name"], "type": a.get("institution_type", "bank"),
            "country": a["country"], "contact_email": a["email"],
            "api_credentials": {}, "callback_url": "",
            "active": True, "created_at": iso(now_utc()),
            "from_application_id": aid,
        }
        await db.institutions.insert_one(doc)
    await add_audit(admin["id"], f"inst_app.{body.status}", target=aid,
                     meta={"note": body.note})
    return {"ok": True}


# ============================================================
# COUNTRY ECONOMICS — credits ↔ USD mapping + per-country P&L
# ============================================================
@country_r.get("/{code}/economics")
async def country_economics(code: str,
                              _: dict = Depends(require_roles("super_admin", "country_admin"))):
    """Per-country economics: credits ↔ USD, cost, margin, P&L over windows.

    Model:
      - usd_per_credit   : global reference (settings economy.usd_per_credit; default 0.01).
      - credits_per_sms  : what the client pays (settings credits.country_rate[code]).
      - retail_usd_per_sms = credits_per_sms * usd_per_credit.
      - cost_usd_per_sms  : weighted-avg of active providers' cost_per_sms for this country.
      - margin_usd_per_sms = retail - cost. Margin % = margin / retail.
    """
    code = code.upper()
    country = await db.countries.find_one({"code": code}, {"_id": 0})
    if not country:
        raise HTTPException(404, "Country not found")
    usd_per_credit = float(await get_setting("economy.usd_per_credit", 0.01) or 0.01)
    rates = await get_setting("credits.country_rate", {}) or {}
    credits_per_sms = int(rates.get(code,
                                     await get_setting("credits.default_rate", 2)) or 2)
    whatsapp_credits = int(await get_setting("credits.whatsapp_rate", 3) or 3)

    providers = await db.providers.find(
        {"countries": code, "active": True}, {"_id": 0}).to_list(50)
    if providers:
        costs = [float(p.get("cost_per_sms", 0.01)) for p in providers]
        cost_min = min(costs)
        cost_max = max(costs)
        cost_avg = sum(costs) / len(costs)
    else:
        cost_min = cost_max = cost_avg = None

    retail_usd_per_sms = round(credits_per_sms * usd_per_credit, 6)
    margin_usd_per_sms = round((retail_usd_per_sms - cost_avg), 6) if cost_avg is not None else None
    margin_pct = round((margin_usd_per_sms / retail_usd_per_sms * 100), 2) if (
        margin_usd_per_sms is not None and retail_usd_per_sms > 0) else None

    # P&L windows from platform_revenue_log
    async def window(days: int):
        since = now_utc() - timedelta(days=days)
        pipe = [
            {"$match": {"country": code, "created_at": {"$gte": iso(since)}}},
            {"$group": {"_id": None,
                         "credits": {"$sum": "$credits"},
                         "usd_cost": {"$sum": "$usd_cost"},
                         "sends": {"$sum": 1}}},
        ]
        async for row in db.platform_revenue_log.aggregate(pipe):
            credits = int(row.get("credits", 0))
            cost = round(float(row.get("usd_cost", 0)), 4)
            revenue = round(credits * usd_per_credit, 4)
            return {"days": days, "sends": row.get("sends", 0), "credits": credits,
                    "revenue_usd": revenue, "cost_usd": cost,
                    "margin_usd": round(revenue - cost, 4),
                    "margin_pct": round((revenue - cost) / revenue * 100, 2) if revenue else None}
        return {"days": days, "sends": 0, "credits": 0, "revenue_usd": 0,
                "cost_usd": 0, "margin_usd": 0, "margin_pct": None}

    return {
        "country": {"code": code, "name": country["name"]},
        "unit_economics": {
            "usd_per_credit": usd_per_credit,
            "credits_per_sms": credits_per_sms,
            "credits_per_whatsapp": whatsapp_credits,
            "retail_usd_per_sms": retail_usd_per_sms,
            "cost_usd_per_sms_min": cost_min,
            "cost_usd_per_sms_max": cost_max,
            "cost_usd_per_sms_avg": round(cost_avg, 6) if cost_avg is not None else None,
            "margin_usd_per_sms": margin_usd_per_sms,
            "margin_pct": margin_pct,
            "providers_count": len(providers),
        },
        "windows": [
            await window(1),
            await window(7),
            await window(30),
            await window(90),
        ],
    }


class CountryRateIn(BaseModel):
    credits_per_sms: int = Field(ge=1, le=100)


@country_r.put("/{code}/rate")
async def country_set_rate(code: str, body: CountryRateIn,
                             admin: dict = Depends(require_roles("super_admin"))):
    code = code.upper()
    rates = await get_setting("credits.country_rate", {}) or {}
    rates[code] = int(body.credits_per_sms)
    await db.system_settings.update_one(
        {"key": "credits.country_rate"},
        {"$set": {"key": "credits.country_rate", "value": rates, "category": "credits",
                  "updated_at": iso(now_utc())}},
        upsert=True)
    await add_audit(admin["id"], "country.rate", target=code,
                     meta={"credits_per_sms": body.credits_per_sms})
    return {"ok": True, "credits_per_sms": body.credits_per_sms}


@country_r.post("/{code}/prefixes")
async def country_add_prefix(code: str, body: Dict[str, Any],
                               _: dict = Depends(require_roles("super_admin"))):
    doc = {"id": new_id(), "country": code.upper(),
           "prefix": body.get("prefix", "").strip(),
           "operator": body.get("operator", "Unknown"),
           "active": body.get("active", True),
           "created_at": iso(now_utc())}
    if not doc["prefix"]:
        raise HTTPException(400, "Prefix is required")
    await db.mobile_prefixes.insert_one(doc)
    return clean(doc)


@country_r.delete("/{code}/prefixes/{pid}")
async def country_del_prefix(code: str, pid: str,
                               _: dict = Depends(require_roles("super_admin"))):
    await db.mobile_prefixes.delete_one({"id": pid, "country": code.upper()})
    return {"ok": True}


# ============================================================
# WHATSAPP TEMPLATES
# ============================================================
class WaTemplateIn(BaseModel):
    name: str
    body: str
    category: str = "utility"  # utility | marketing | authentication
    language: str = "en"


class WaTemplateReviewIn(BaseModel):
    status: str  # approved | rejected
    note: Optional[str] = None


wa_r = APIRouter(prefix="/wa-templates", tags=["wa_templates"])


@wa_r.get("")
async def list_wa(user: dict = Depends(get_current_user)):
    return await db.wa_templates.find(
        {"user_id": user["id"]}, {"_id": 0}).sort("created_at", -1).to_list(200)


@wa_r.post("")
async def add_wa(body: WaTemplateIn, user: dict = Depends(get_current_user)):
    doc = {"id": new_id(), "user_id": user["id"], **body.model_dump(),
           "status": "pending", "created_at": iso(now_utc())}
    await db.wa_templates.insert_one(doc)
    admins = await db.users.find({"role": "super_admin"}, {"id": 1}).to_list(20)
    for a in admins:
        await add_notification(a["id"], "WhatsApp template submitted",
                                f"{body.name} by {user['email']}", "info")
    return clean(doc)


@wa_r.delete("/{tid}")
async def del_wa(tid: str, user: dict = Depends(get_current_user)):
    await db.wa_templates.delete_one({"id": tid, "user_id": user["id"]})
    return {"ok": True}


@adm_r.get("/wa-templates")
async def adm_wa(user: dict = Depends(require_roles("super_admin", "compliance"))):
    return await db.wa_templates.find({}, {"_id": 0}).sort("created_at", -1).to_list(500)


@adm_r.post("/wa-templates/{tid}/review")
async def adm_wa_review(tid: str, body: WaTemplateReviewIn,
                         user: dict = Depends(require_roles("super_admin", "compliance"))):
    if body.status not in ("approved", "rejected"):
        raise HTTPException(400, "Invalid status")
    rec = await db.wa_templates.find_one({"id": tid})
    if not rec:
        raise HTTPException(404)
    await db.wa_templates.update_one({"id": tid}, {"$set": {
        "status": body.status, "review_note": body.note,
        "reviewed_by": user["id"], "reviewed_at": iso(now_utc())
    }})
    await add_notification(rec["user_id"], f"WhatsApp template {body.status}",
                           f"{rec['name']} {body.status}.",
                           "success" if body.status == "approved" else "warning")
    return {"ok": True}


api.include_router(ref_r)
api.include_router(prof_r)
api.include_router(res_px_r)
api.include_router(adm_res_px_r)
api.include_router(country_r)
api.include_router(int_r)
api.include_router(approv_r)
api.include_router(app_r)
api.include_router(pub_r)
api.include_router(wa_r)


# ============================================================
# Mount routers
# ============================================================
api.include_router(auth_r)
api.include_router(wallet_r)
api.include_router(contacts_r)
api.include_router(num_r)


# ============================================================
# FX & LOCAL CURRENCY per country + Bank transfer top-ups
# ============================================================

async def convert_usd_to_local(country_code: str, usd: float) -> Dict[str, Any]:
    """Given USD amount, return {amount, currency, fx_rate, rounded}."""
    if not country_code:
        return {"amount": usd, "currency": "USD", "fx_rate": 1.0, "rounded": round(usd, 2)}
    country = await db.countries.find_one({"code": country_code.upper()}, {"_id": 0})
    if not country:
        return {"amount": usd, "currency": "USD", "fx_rate": 1.0, "rounded": round(usd, 2)}
    fx = float(country.get("fx_rate_to_usd", 0) or 0)
    cur = country.get("currency") or "USD"
    if fx <= 0 or cur == "USD":
        return {"amount": usd, "currency": cur, "fx_rate": 1.0, "rounded": round(usd, 2)}
    local = usd * fx
    step = float(country.get("fx_rounding", 1) or 1)
    if step > 0:
        local = round(local / step) * step
    return {"amount": round(local, 2), "currency": cur, "fx_rate": fx, "rounded": local}


class CountryFxIn(BaseModel):
    currency: str
    fx_rate_to_usd: float = Field(gt=0)
    fx_rounding: float = 1.0


@country_r.put("/{code}/fx")
async def country_set_fx(code: str, body: CountryFxIn,
                           admin: dict = Depends(require_roles("super_admin"))):
    code = code.upper()
    r = await db.countries.find_one({"code": code})
    if not r:
        raise HTTPException(404, "Country not found")
    await db.countries.update_one({"code": code}, {"$set": {
        "currency": body.currency.upper(),
        "fx_rate_to_usd": float(body.fx_rate_to_usd),
        "fx_rounding": float(body.fx_rounding),
        "updated_at": iso(now_utc()),
    }})
    await add_audit(admin["id"], "country.fx", target=code, meta=body.model_dump())
    return {"ok": True, **body.model_dump()}


# ---------- Country economics: VAT + local pricing per SMS ----------
class CountryEconomicsIn(BaseModel):
    vat_rate_pct: float = Field(ge=0, le=100)
    sell_per_sms_local: float = Field(ge=0)        # what direct clients pay (gross, VAT-incl)
    wholesale_per_sms_local: float = Field(ge=0)   # internal reference for resellers/affiliates


# Distinct prefixes so /pnl doesn't get eaten by country_r's /{code} catch-all
econ_r = APIRouter(prefix="/admin/country-economics", tags=["country_economics"])
pnl_r  = APIRouter(prefix="/admin/country-pnl",       tags=["country_pnl"])


@pnl_r.get("")
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


@econ_r.get("/{code}")
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


@econ_r.put("/{code}")
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


api.include_router(econ_r)
api.include_router(pnl_r)


# ---------- Banks & top-up routes have moved to routes/banks.py and routes/topups.py ----------
# (See bottom of file for the import + include_router statements.)


from routes import banks as routes_banks            # bank accounts CRUD + client lookup
from routes import topups as routes_topups          # bank-transfer top-up flow (client + admin)
from routes import messaging_extras as routes_msg_extras  # preflight + saved CSV mappings

api.include_router(routes_banks.admin_r)
api.include_router(routes_banks.client_r)
api.include_router(routes_topups.client_r)
api.include_router(routes_topups.admin_r)
api.include_router(routes_msg_extras.router)
from routes import affiliate as routes_affiliate
api.include_router(routes_affiliate.self_r)
api.include_router(routes_affiliate.admin_r)
api.include_router(sid_r)
api.include_router(tpl_r)
api.include_router(msg_r)
api.include_router(res_r)
api.include_router(adm_r)
api.include_router(notif_r)
api.include_router(key_r)


@api.get("/")
async def root():
    return {"app": "unitxt", "version": "1.0.0", "status": "ok"}


app.include_router(api)

app.add_middleware(
    CORSMiddleware,
    allow_credentials=True,
    allow_origins=["*"],
    allow_origin_regex=".*",
    allow_methods=["*"],
    allow_headers=["*"],
)


# ============================================================
# Startup: seed
# ============================================================
@app.on_event("startup")
async def startup():
    await db.users.create_index("email", unique=True)
    await db.users.create_index("reseller_code")
    await db.login_attempts.create_index("identifier")
    await db.password_reset_tokens.create_index("token", unique=True)
    await db.notifications.create_index([("user_id", 1), ("created_at", -1)])
    await db.messages.create_index([("user_id", 1), ("created_at", -1)])
    await db.campaigns.create_index([("user_id", 1), ("created_at", -1)])
    await db.providers.create_index("priority")

    # seed admin
    admin_email = os.environ.get("ADMIN_EMAIL", "admin@unitxt.io")
    admin_pwd = os.environ.get("ADMIN_PASSWORD", "Admin@2026")
    existing = await db.users.find_one({"email": admin_email})
    if not existing:
        admin = {
            "id": new_id(), "email": admin_email,
            "password_hash": hash_password(admin_pwd),
            "name": "Platform Admin", "role": "super_admin",
            "country": "TZ", "status": "active",
            "kyc_verified": True, "created_at": iso(now_utc()),
        }
        await db.users.insert_one(admin)
        await get_or_create_wallet(admin["id"])
    elif not verify_password(admin_pwd, existing["password_hash"]):
        await db.users.update_one({"email": admin_email},
                                   {"$set": {"password_hash": hash_password(admin_pwd)}})

    # seed demo reseller
    res_email = os.environ.get("DEMO_RESELLER_EMAIL", "reseller@unitxt.io")
    res_pwd = os.environ.get("DEMO_RESELLER_PASSWORD", "Reseller@2026")
    res_user = await db.users.find_one({"email": res_email})
    if not res_user:
        res_user = {
            "id": new_id(), "email": res_email,
            "password_hash": hash_password(res_pwd),
            "name": "Demo Reseller", "role": "reseller",
            "business_name": "Acme Telecom", "country": "TZ",
            "status": "active", "kyc_verified": True,
            "reseller_code": "RDEMO1", "commission_rate": 0.15,
            "created_at": iso(now_utc()),
        }
        await db.users.insert_one(res_user)
        await get_or_create_wallet(res_user["id"])
        await adjust_wallet(res_user["id"], 500000, "topup",
                            note="Initial reseller float (credits)", by=res_user["id"])
    else:
        if not verify_password(res_pwd, res_user["password_hash"]):
            await db.users.update_one({"email": res_email},
                                       {"$set": {"password_hash": hash_password(res_pwd)}})

    # Make sure the demo reseller has at least one affiliate code (idempotent)
    if not await db.affiliate_codes.find_one({"owner_user_id": res_user["id"]}):
        await db.affiliate_codes.insert_one({
            "id": new_id(), "code": "RDEMO1", "note": "Demo reseller default code",
            "active": True, "owner_user_id": res_user["id"],
            "created_at": iso(now_utc()), "uses": 0,
        })

    # seed demo client
    cli_email = os.environ.get("DEMO_CLIENT_EMAIL", "client@unitxt.io")
    cli_pwd = os.environ.get("DEMO_CLIENT_PASSWORD", "Client@2026")
    cli_user = await db.users.find_one({"email": cli_email})
    if not cli_user:
        cli_user = {
            "id": new_id(), "email": cli_email,
            "password_hash": hash_password(cli_pwd),
            "name": "Demo Client", "role": "client",
            "business_name": "Sunrise Bank", "country": "TZ",
            "reseller_id": res_user["id"], "status": "active",
            "kyc_verified": True, "created_at": iso(now_utc()),
        }
        await db.users.insert_one(cli_user)
        await get_or_create_wallet(cli_user["id"])
        await adjust_wallet(cli_user["id"], 25000, "topup",
                            note="Welcome credits", by=cli_user["id"])
    else:
        if not verify_password(cli_pwd, cli_user["password_hash"]):
            await db.users.update_one({"email": cli_email},
                                       {"$set": {"password_hash": hash_password(cli_pwd)}})

    # seed countries
    if await db.countries.count_documents({}) == 0:
        seed_countries = [
            ("TZ", "Tanzania", "TZS", "+255"),
            ("KE", "Kenya", "KES", "+254"),
            ("UG", "Uganda", "UGX", "+256"),
            ("ZM", "Zambia", "ZMW", "+260"),
            ("GH", "Ghana", "GHS", "+233"),
            ("NG", "Nigeria", "NGN", "+234"),
            ("ZA", "South Africa", "ZAR", "+27"),
            ("RW", "Rwanda", "RWF", "+250"),
            ("US", "United States", "USD", "+1"),
            ("GB", "United Kingdom", "GBP", "+44"),
            ("IN", "India", "INR", "+91"),
            ("AE", "United Arab Emirates", "AED", "+971"),
        ]
        await db.countries.insert_many([
            {"id": new_id(), "code": c, "name": n, "currency": cur,
             "dial_code": dc, "sender_id_required": True, "active": True,
             "created_at": iso(now_utc())} for c, n, cur, dc in seed_countries])

    # seed providers
    if await db.providers.count_documents({}) == 0:
        await db.providers.insert_many([
            {"id": new_id(), "name": "Tigo Tanzania (Direct)", "type": "direct_telco",
             "countries": ["TZ"], "operators": ["Tigo"],
             "channels": ["sms"], "api_key": "", "api_secret": "",
             "base_url": "",  # fill with VPN endpoint when provided
             "cost_per_sms": 0.006, "priority": 1, "active": True,
             "supports_unicode": True, "supports_dlr": True,
             "health": "healthy", "rate_limit": 200,
             "created_at": iso(now_utc())},
            {"id": new_id(), "name": "TZ Direct Telco", "type": "direct_telco",
             "countries": ["TZ"], "channels": ["sms"], "api_key": "", "api_secret": "",
             "base_url": "https://example-tz.local",
             "cost_per_sms": 0.008, "priority": 2, "active": True,
             "supports_unicode": True, "supports_dlr": True,
             "health": "healthy", "rate_limit": 100,
             "created_at": iso(now_utc())},
            {"id": new_id(), "name": "Twilio Global", "type": "aggregator",
             "countries": ["*"], "channels": ["sms", "whatsapp"],
             "api_key": "", "api_secret": "",
             "base_url": "https://api.twilio.com",
             "cost_per_sms": 0.04, "priority": 5, "active": True,
             "supports_unicode": True, "supports_dlr": True,
             "health": "healthy", "rate_limit": 50,
             "created_at": iso(now_utc())},
            {"id": new_id(), "name": "Infobip Africa", "type": "aggregator",
             "countries": ["KE", "UG", "ZM", "GH", "NG"], "channels": ["sms", "whatsapp"],
             "api_key": "", "api_secret": "",
             "base_url": "https://api.infobip.com",
             "cost_per_sms": 0.02, "priority": 3, "active": True,
             "supports_unicode": True, "supports_dlr": True,
             "health": "healthy", "rate_limit": 100,
             "created_at": iso(now_utc())},
        ])

    # seed pricing
    if await db.pricing_plans.count_documents({}) == 0:
        await db.pricing_plans.insert_many([
            {"id": new_id(), "name": "Tanzania Standard", "country": "TZ",
             "channel": "sms", "base_price": 0.012, "reseller_price": 0.015,
             "client_price": 0.020, "min_volume": 0, "active": True,
             "created_at": iso(now_utc())},
            {"id": new_id(), "name": "Kenya Standard", "country": "KE",
             "channel": "sms", "base_price": 0.025, "reseller_price": 0.030,
             "client_price": 0.040, "min_volume": 0, "active": True,
             "created_at": iso(now_utc())},
            {"id": new_id(), "name": "WhatsApp Tanzania", "country": "TZ",
             "channel": "whatsapp", "base_price": 0.005, "reseller_price": 0.008,
             "client_price": 0.012, "min_volume": 0, "active": True,
             "created_at": iso(now_utc())},
        ])

    # seed promotions
    if await db.promotions.count_documents({}) == 0:
        await db.promotions.insert_many([
            {"id": new_id(), "name": "Welcome 10%", "code": "WELCOME10",
             "type": "percent_discount", "value": 10, "min_topup": 50,
             "active": True, "created_at": iso(now_utc())},
            {"id": new_id(), "name": "$25 Bonus", "code": "BONUS25",
             "type": "bonus_credit", "value": 25, "min_topup": 200,
             "active": True, "created_at": iso(now_utc())},
        ])

    # seed institutions
    if await db.institutions.count_documents({}) == 0:
        await db.institutions.insert_many([
            {"id": new_id(), "name": "M-Pesa Tanzania", "type": "mobile_money",
             "country": "TZ", "api_credentials": {}, "callback_url": "",
             "active": True, "created_at": iso(now_utc())},
            {"id": new_id(), "name": "CRDB Bank", "type": "bank",
             "country": "TZ", "api_credentials": {}, "callback_url": "",
             "active": True, "created_at": iso(now_utc())},
        ])

    # seed settings
    defaults = [
        ("platform.name", "unitxt", "platform"),
        ("platform.support_email", "support@unitxt.io", "platform"),
        ("platform.default_currency", "USD", "platform"),
        ("platform.default_timezone", "Africa/Dar_es_Salaam", "platform"),
        ("platform.maintenance_mode", False, "platform"),
        ("compliance.kyc_required", False, "compliance"),
        ("compliance.daily_send_limit", 100000, "compliance"),
        ("compliance.spam_keywords", ["lottery", "winner"], "compliance"),
        ("notifications.low_balance_threshold", 10, "notifications"),
        ("notifications.low_credits_threshold", 100, "notifications"),
        ("onboarding.reseller_signup_open", True, "onboarding"),
        # Reseller distribution policy (configurable from Settings Hub → Distribution)
        ("reseller.default_commission", 0.15, "reseller"),
        ("reseller.kyc_required", True, "reseller"),
        ("reseller.min_float_topup", 1000, "reseller"),
        ("reseller.max_sub_clients", 500, "reseller"),
        ("reseller.allow_invite_clients", True, "reseller"),
        # Reseller commission paid out of admin margin (never inflates client price)
        ("pricing.reseller_commission_default", 0.15, "pricing"),
        # Economy — credits ↔ USD reference rate. Drives all country P&L calculations.
        # Default 0.01 = $1 per 100 credits (matches Scale pack's blended price).
        ("economy.usd_per_credit", 0.01, "economy"),
        # Number lookup services — pricing & availability live here.
        ("numbers.smart_validation_cost", 1, "numbers"),
        ("numbers.hlr_lookup_cost", 5, "numbers"),
        ("numbers.hlr_enabled_countries", [], "numbers"),
        # Credits engine — drives all money-related behavior
        ("credits.default_rate", 2, "credits"),
        ("credits.whatsapp_rate", 3, "credits"),
        ("credits.country_rate", {
            "TZ": 1, "KE": 2, "UG": 2, "RW": 2, "ZM": 2,
            "GH": 3, "NG": 3, "ZA": 3,
            "US": 5, "GB": 4, "AE": 4, "IN": 1,
        }, "credits"),
        ("credits.unicode_surcharge", 1, "credits"),
        ("credits.sender_id_cost", 500, "credits"),
        ("credits.sender_id_renewal", 500, "credits"),
        ("credits.sender_id_expiry_days", 365, "credits"),
        ("credits.inactivity_warn_days", 30, "inactivity"),
        ("credits.inactivity_suspend_days", 60, "inactivity"),
        ("credits.inactivity_recovery_cost", 1000, "inactivity"),
        # queue
        ("queue.max_concurrency", 200, "queue"),
        ("queue.batch_size", 1000, "queue"),
        ("queue.max_retries", 2, "queue"),
        # Referrals — loss-proof (paid from pack revenue)
        ("referral.active", True, "referrals"),
        ("referral.percent_of_pack", 5, "referrals"),
        ("referral.max_credits_per_referral", 500, "referrals"),
        # Streak gamification
        ("streak.7_day_bonus", 100, "streaks"),
        ("streak.30_day_bonus", 1000, "streaks"),
        ("streak.90_day_bonus", 5000, "streaks"),
        # Messaging — pre-flight (free sample validation) thresholds
        ("messaging.preflight_free_sample", 20, "queue"),
        ("messaging.preflight_red_threshold", 50, "queue"),
        ("messaging.preflight_amber_threshold", 80, "queue"),
        # Top-up flow (manual bank transfer) — receipt size cap, in KB
        ("topups.max_proof_kb", 4096, "compliance"),
        # ── AFFILIATE PROGRAM (replaces legacy referral.*) ─────────────────
        # Default model = time_window (Option A: 6 months @ 10%)
        ("affiliate.active",                     True,          "affiliate"),
        ("affiliate.model",                      "time_window", "affiliate"),
        ("affiliate.commission_pct",             10,            "affiliate"),
        # Option A — time_window
        ("affiliate.window_months",              6,             "affiliate"),
        # Option B — first_n_topups
        ("affiliate.first_n",                    3,             "affiliate"),
        # Option C — tier_bonus
        ("affiliate.tier_first_pct",             15,            "affiliate"),
        ("affiliate.tier_bonus_1_threshold_usd", 200,           "affiliate"),
        ("affiliate.tier_bonus_1_amount_usd",    20,            "affiliate"),
        ("affiliate.tier_bonus_2_threshold_usd", 1000,          "affiliate"),
        ("affiliate.tier_bonus_2_amount_usd",    50,            "affiliate"),
        ("affiliate.tier_window_months",         12,            "affiliate"),
        # Welcome bonus delivered to the REFERRED user on their first paid top-up
        ("affiliate.welcome_bonus_pct",          5,             "affiliate"),
        ("affiliate.welcome_bonus_max_credits",  500,           "affiliate"),
        # Payouts & limits
        ("affiliate.payout_threshold_usd",       50,            "affiliate"),
        ("affiliate.max_codes_per_user",         5,             "affiliate"),
    ]
    for k, v, cat in defaults:
        await db.system_settings.update_one(
            {"key": k},
            {"$setOnInsert": {"key": k, "value": v, "category": cat,
                              "updated_at": iso(now_utc())}},
            upsert=True)

    # seed credit packs
    if await db.credit_packs.count_documents({}) == 0:
        await db.credit_packs.insert_many([
            {"id": new_id(), "name": "Starter", "credits": 1000, "price_usd": 15.00,
             "tag": "Try it", "active": True, "created_at": iso(now_utc())},
            {"id": new_id(), "name": "Growth", "credits": 10000, "price_usd": 120.00,
             "tag": "Popular", "active": True, "created_at": iso(now_utc())},
            {"id": new_id(), "name": "Scale", "credits": 100000, "price_usd": 1000.00,
             "tag": "Best value", "active": True, "created_at": iso(now_utc())},
            {"id": new_id(), "name": "Enterprise", "credits": 1000000, "price_usd": 8500.00,
             "tag": "Contract", "active": True, "created_at": iso(now_utc())},
        ])

    # seed mobile prefixes for African countries
    if await db.mobile_prefixes.count_documents({}) == 0:
        pfx = [
            # Tanzania
            ("TZ", "Vodacom", "+25574"), ("TZ", "Vodacom", "+25575"), ("TZ", "Vodacom", "+25576"),
            ("TZ", "Tigo", "+25565"), ("TZ", "Tigo", "+25567"), ("TZ", "Tigo", "+25571"),
            ("TZ", "Airtel", "+25568"), ("TZ", "Airtel", "+25569"), ("TZ", "Airtel", "+25578"),
            ("TZ", "Halotel", "+25561"), ("TZ", "Halotel", "+25562"),
            ("TZ", "TTCL", "+25573"), ("TZ", "Zantel", "+25577"),
            # Kenya
            ("KE", "Safaricom", "+25470"), ("KE", "Safaricom", "+25471"),
            ("KE", "Safaricom", "+25472"), ("KE", "Safaricom", "+25479"),
            ("KE", "Airtel", "+25473"), ("KE", "Airtel", "+25478"),
            ("KE", "Telkom", "+25477"),
            # Uganda
            ("UG", "MTN", "+25677"), ("UG", "MTN", "+25678"), ("UG", "MTN", "+25676"),
            ("UG", "Airtel", "+25670"), ("UG", "Airtel", "+25675"),
            ("UG", "Africell", "+25679"),
            # Rwanda
            ("RW", "MTN", "+25078"), ("RW", "Airtel", "+25073"),
            # Zambia
            ("ZM", "MTN", "+26076"), ("ZM", "MTN", "+26096"),
            ("ZM", "Airtel", "+26077"), ("ZM", "Airtel", "+26097"),
            ("ZM", "Zamtel", "+26095"),
            # Ghana
            ("GH", "MTN", "+23354"), ("GH", "MTN", "+23355"), ("GH", "MTN", "+23359"),
            ("GH", "Vodafone", "+23320"), ("GH", "AirtelTigo", "+23327"),
            # Nigeria
            ("NG", "MTN", "+23480"), ("NG", "MTN", "+23481"), ("NG", "MTN", "+23490"),
            ("NG", "Airtel", "+23470"), ("NG", "Airtel", "+23491"),
            ("NG", "Glo", "+23485"), ("NG", "9mobile", "+23489"),
            # South Africa
            ("ZA", "Vodacom", "+2782"), ("ZA", "MTN", "+2783"),
            ("ZA", "Cell C", "+2784"), ("ZA", "Telkom Mobile", "+2781"),
            # Senegal, Ivory Coast, Ethiopia — starter set
            ("SN", "Orange", "+22177"), ("SN", "Free", "+22176"),
            ("CI", "Orange", "+22507"), ("CI", "MTN", "+22505"),
            ("ET", "Ethio Telecom", "+2519"),
            # Egypt / Morocco
            ("EG", "Vodafone Egypt", "+2010"), ("EG", "Orange Egypt", "+2012"),
            ("MA", "Maroc Telecom", "+2126"), ("MA", "Orange Maroc", "+2127"),
            # Global samples
            ("US", "Generic", "+1"),
            ("GB", "Generic", "+44"),
            ("IN", "Generic", "+91"),
            ("AE", "Generic", "+971"),
        ]
        await db.mobile_prefixes.insert_many([
            {"id": new_id(), "country": c, "operator": op, "prefix": p,
             "active": True, "created_at": iso(now_utc())}
            for c, op, p in pfx
        ])

    # seed an approved sender ID for client
    cli_user = await db.users.find_one({"email": cli_email})
    if cli_user and await db.sender_id_requests.count_documents({"user_id": cli_user["id"]}) == 0:
        await db.sender_id_requests.insert_one({
            "id": new_id(), "user_id": cli_user["id"], "sender_id": "SUNRISE",
            "country": "TZ", "use_case": "Bank notifications",
            "sample_message": "Your account...", "documents": [],
            "status": "approved", "reviewed_at": iso(now_utc()),
            "expires_at": iso(now_utc() + timedelta(days=365)),
            "created_at": iso(now_utc()),
        })

    # kick off background loop
    asyncio.create_task(background_loop())

    # v1.9 FX migration: ensure every country has fx_rate_to_usd + fx_rounding.
    # Admin can override later via Country hub → Economics.
    SEED_FX = {
        "TZ": (2600, 100), "KE": (130, 10), "UG": (3700, 100), "ZM": (26, 1),
        "GH": (15, 1), "NG": (1550, 10), "ZA": (18, 1), "RW": (1350, 50),
        "US": (1, 1), "GB": (0.79, 1), "IN": (83, 1), "AE": (3.67, 1),
    }
    for code, (rate, step) in SEED_FX.items():
        await db.countries.update_one(
            {"code": code, "fx_rate_to_usd": {"$exists": False}},
            {"$set": {"fx_rate_to_usd": float(rate), "fx_rounding": float(step),
                       "updated_at": iso(now_utc())}})

    # Seed a default Tanzania bank account on first boot
    if await db.bank_accounts.count_documents({}) == 0:
        await db.bank_accounts.insert_one({
            "id": new_id(),
            "country": "TZ", "bank_name": "CRDB Bank",
            "account_name": "Unitxt Tanzania Ltd",
            "account_number": "0150123456700",
            "branch": "Dar es Salaam", "swift": "CORUTZTZ",
            "currency": "TZS",
            "instructions": "Use your registered email as the payment reference, "
                             "take a screenshot or photo of the receipt and attach it when submitting "
                             "your top-up request. Credits are added within one business day of verification.",
            "active": True,
            "created_at": iso(now_utc()),
        })

    # v1.3 migration: retire legacy `markup`-based reseller pricing records. Replaced by
    # admin-controlled commission_rate (0.0-1.0). Keep only records that already carry
    # commission_rate; delete stale markup-only rows so the new pricing model is clean.
    await db.reseller_pricing.delete_many({
        "commission_rate": {"$exists": False},
        "markup": {"$exists": True},
    })

    log.info("unitxt startup seed complete")


@app.on_event("shutdown")
async def shutdown():
    mongo_client.close()
