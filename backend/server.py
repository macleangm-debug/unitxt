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
    tags: List[str] = []


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
        "status": "active",
        "kyc_verified": False,
        "created_at": iso(now_utc()),
    }
    if role == "reseller":
        user["reseller_code"] = "R" + secrets.token_hex(3).upper()
        user["commission_rate"] = 0.10
    await db.users.insert_one(user)
    await get_or_create_wallet(user["id"])
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


async def execute_campaign(campaign: dict):
    """Process all messages for a campaign. Charges credits; records USD provider cost
    for admin margin reporting. Concurrency-limited per provider."""
    cid = campaign["id"]
    user_id = campaign["user_id"]
    channel = campaign["channel"]
    sender_id = campaign["sender_id"]
    country = campaign.get("country", "TZ")
    user = await db.users.find_one({"id": user_id})
    if not user:
        return
    credits_rate = await credits_per_msg(country, channel)
    provider = await pick_provider(country, channel)
    if not provider:
        await db.campaigns.update_one({"id": cid}, {"$set": {"status": "failed",
                                                              "error": "No active provider"}})
        return
    adapter = adapter_for(provider)
    provider_usd = float(provider.get("cost_per_sms", 0.01))
    sent = delivered = failed = 0
    total_credits = 0
    total_usd_cost = 0.0

    # Queue concurrency: defaults 50, overridable via provider.rate_limit
    max_conc = int(provider.get("rate_limit") or await get_setting("queue.max_concurrency", 50) or 50)
    sem = asyncio.Semaphore(max_conc)
    lock = asyncio.Lock()

    async def send_one(r):
        nonlocal sent, delivered, failed, total_credits, total_usd_cost
        text = render(campaign["template"], r) if campaign.get("template") else campaign["message"]
        seg = gsm_segments(text)
        credits_cost = credits_rate * seg
        usd_cost = round(provider_usd * seg, 6)
        # charge credits up-front (atomic under lock to avoid oversell)
        async with lock:
            w = await get_or_create_wallet(user_id)
            if w["balance"] < credits_cost:
                await db.messages.insert_one({
                    "id": new_id(), "campaign_id": cid, "user_id": user_id, "to": r["phone"],
                    "channel": channel, "sender_id": sender_id, "body": text,
                    "segments": seg, "cost": 0, "usd_cost": 0, "status": "failed",
                    "provider_id": provider["id"], "provider_msg_id": None,
                    "error": "INSUFFICIENT_CREDITS", "created_at": iso(now_utc())
                })
                failed += 1
                return
            await adjust_wallet(user_id, -credits_cost, "sms_charge",
                                note=f"Campaign {campaign['name'][:30]} · {seg}seg",
                                ref=cid, by=user_id)
        # initial queued message row
        mid = new_id()
        await db.messages.insert_one({
            "id": mid, "campaign_id": cid, "user_id": user_id, "to": r["phone"],
            "channel": channel, "sender_id": sender_id, "body": text,
            "segments": seg, "cost": credits_cost, "usd_cost": usd_cost,
            "status": "queued", "provider_id": provider["id"],
            "provider_msg_id": None, "error": None,
            "created_at": iso(now_utc())
        })
        async with sem:
            # retry up to 2 times on transient failure
            last = None
            for attempt in range(2):
                try:
                    res = await adapter.send(r["phone"], sender_id, text, channel)
                    last = res
                    if res["ok"]:
                        break
                except Exception as e:
                    last = {"ok": False, "status": "failed", "error": str(e),
                            "provider_msg_id": None}
                await asyncio.sleep(0.2 * (attempt + 1))
            res = last or {"ok": False, "status": "failed", "error": "UNKNOWN"}
        async with lock:
            sent += 1
            if res["ok"]:
                delivered += 1
            else:
                failed += 1
            total_credits += credits_cost
            total_usd_cost += usd_cost
        await db.messages.update_one({"id": mid}, {"$set": {
            "status": res.get("status", "sent"),
            "provider_msg_id": res.get("provider_msg_id"),
            "error": res.get("error"),
            "sent_at": iso(now_utc()),
        }})
        # record margin log
        await db.platform_revenue_log.insert_one({
            "id": new_id(), "campaign_id": cid, "user_id": user_id,
            "reseller_id": user.get("reseller_id"),
            "country": country, "channel": channel,
            "credits": credits_cost, "usd_cost": usd_cost,
            "provider_id": provider["id"], "created_at": iso(now_utc())
        })

    await asyncio.gather(*[send_one(r) for r in campaign["recipients"]])

    await db.campaigns.update_one({"id": cid}, {"$set": {
        "status": "completed", "sent": sent, "delivered": delivered,
        "failed": failed,
        "total_cost": total_credits,
        "total_usd_cost": round(total_usd_cost, 6),
        "completed_at": iso(now_utc()),
    }})
    await add_notification(user_id, "Campaign completed",
                           f"{campaign['name']}: {delivered}/{sent} delivered. {total_credits} credits used.",
                           "success")
    # check low-credits threshold
    w = await get_or_create_wallet(user_id)
    thr = int(await get_setting("notifications.low_credits_threshold", 100) or 0)
    if thr > 0 and w["balance"] < thr:
        await add_notification(user_id, "Credits running low",
                               f"Your balance is {w['balance']} credits. Top up to keep sending.",
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
    return {"campaign": c, "messages": msgs}


@msg_r.get("/messages")
async def my_messages(limit: int = 100, user: dict = Depends(get_current_user)):
    items = await db.messages.find({"user_id": user["id"]}, {"_id": 0}).sort(
        "created_at", -1).limit(limit).to_list(limit)
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
async def list_packs():
    items = await db.credit_packs.find({"active": True}, {"_id": 0}).sort("credits", 1).to_list(50)
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
    applies promo bonus if applicable, records payment in platform_payments."""
    pack = await db.credit_packs.find_one({"id": body.pack_id, "active": True}, {"_id": 0})
    if not pack:
        raise HTTPException(404, "Pack not found")
    bonus = 0
    if body.promo_code:
        promo = await db.promotions.find_one({"code": body.promo_code.upper(), "active": True})
        if promo and float(pack.get("price_usd", 0)) >= float(promo.get("min_topup", 0)):
            if promo["type"] == "bonus_credit":
                bonus = int(float(promo["value"]) * 100)  # value interpreted as credits bonus
            elif promo["type"] == "percent_discount":
                bonus = int(pack["credits"] * float(promo["value"]) / 100.0)
    total_credits = int(pack["credits"]) + int(bonus)
    await adjust_wallet(user["id"], total_credits, "pack_purchase",
                        note=f"Pack {pack['name']} · {pack['credits']} credits"
                             + (f" + {bonus} promo" if bonus else ""),
                        ref=pack["id"], by=user["id"])
    await db.platform_payments.insert_one({
        "id": new_id(), "user_id": user["id"], "pack_id": pack["id"],
        "credits": pack["credits"], "bonus": bonus,
        "price_usd": float(pack["price_usd"]), "method": "mock",
        "promo_code": body.promo_code, "created_at": iso(now_utc()),
    })
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
    """Runs forever: inactivity flagging + sender ID expiry + daily metrics snapshot."""
    await asyncio.sleep(5)  # start after boot
    while True:
        try:
            # sender ID expiry → status=expired
            now = now_utc()
            cur = db.sender_id_requests.find(
                {"status": "approved", "expires_at": {"$lt": iso(now)}},
                {"_id": 0})
            async for s in cur:
                await db.sender_id_requests.update_one({"id": s["id"]},
                                                        {"$set": {"status": "expired"}})
                await add_notification(s["user_id"], "Sender ID expired",
                                        f"{s['sender_id']} is expired. Renew to reuse.", "warning")
            # inactivity
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
        await asyncio.sleep(3600)  # every hour


api.include_router(credits_r)
api.include_router(prefix_r)
api.include_router(dlr_r)
api.include_router(adm_r2)


# ============================================================
# Mount routers
# ============================================================
api.include_router(auth_r)
api.include_router(wallet_r)
api.include_router(contacts_r)
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
             "countries": ["TZ"], "channels": ["sms"], "api_key": "", "api_secret": "",
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
        ("queue.max_concurrency", 50, "queue"),
        ("queue.max_retries", 2, "queue"),
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

    log.info("unitxt startup seed complete")


@app.on_event("shutdown")
async def shutdown():
    mongo_client.close()
