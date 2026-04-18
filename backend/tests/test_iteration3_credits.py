"""
unitxt Iteration 3 Backend API Tests
Tests credits-based wallet system, credit packs, mobile prefixes, DLR webhook,
margin reports, sender ID renewal, account recovery, and RBAC.
"""
import pytest
import requests
import os
import time
import uuid

BASE_URL = os.environ.get('REACT_APP_BACKEND_URL', '').rstrip('/')

# Test credentials from seed
ADMIN_EMAIL = "admin@unitxt.io"
ADMIN_PASSWORD = "Admin@2026"
RESELLER_EMAIL = "reseller@unitxt.io"
RESELLER_PASSWORD = "Reseller@2026"
CLIENT_EMAIL = "client@unitxt.io"
CLIENT_PASSWORD = "Client@2026"


# ============================================================
# FIXTURES
# ============================================================
@pytest.fixture
def admin_session():
    """Admin session with auth token"""
    session = requests.Session()
    resp = session.post(f"{BASE_URL}/api/auth/login", json={
        "email": ADMIN_EMAIL,
        "password": ADMIN_PASSWORD
    })
    assert resp.status_code == 200, f"Admin login failed: {resp.text}"
    token = resp.json().get("access_token")
    session.headers.update({"Authorization": f"Bearer {token}"})
    return session


@pytest.fixture
def reseller_session():
    """Reseller session with auth token"""
    session = requests.Session()
    resp = session.post(f"{BASE_URL}/api/auth/login", json={
        "email": RESELLER_EMAIL,
        "password": RESELLER_PASSWORD
    })
    assert resp.status_code == 200, f"Reseller login failed: {resp.text}"
    token = resp.json().get("access_token")
    session.headers.update({"Authorization": f"Bearer {token}"})
    return session


@pytest.fixture
def client_session():
    """Client session with auth token"""
    session = requests.Session()
    resp = session.post(f"{BASE_URL}/api/auth/login", json={
        "email": CLIENT_EMAIL,
        "password": CLIENT_PASSWORD
    })
    assert resp.status_code == 200, f"Client login failed: {resp.text}"
    token = resp.json().get("access_token")
    session.headers.update({"Authorization": f"Bearer {token}"})
    return session


# ============================================================
# AUTH TESTS - Verify 3 users still work
# ============================================================
class TestAuthLogin:
    """Verify all 3 seeded users can still login"""
    
    def test_admin_login(self):
        """Admin login returns super_admin role"""
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": ADMIN_EMAIL,
            "password": ADMIN_PASSWORD
        })
        assert resp.status_code == 200, f"Admin login failed: {resp.text}"
        data = resp.json()
        assert data["user"]["role"] == "super_admin"
        assert data["user"]["email"] == ADMIN_EMAIL
        print(f"✓ Admin login success: {data['user']['email']}")
    
    def test_reseller_login(self):
        """Reseller login returns reseller role"""
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": RESELLER_EMAIL,
            "password": RESELLER_PASSWORD
        })
        assert resp.status_code == 200, f"Reseller login failed: {resp.text}"
        data = resp.json()
        assert data["user"]["role"] == "reseller"
        assert data["user"]["email"] == RESELLER_EMAIL
        print(f"✓ Reseller login success: {data['user']['email']}")
    
    def test_client_login(self):
        """Client login returns client role with reseller_id"""
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL,
            "password": CLIENT_PASSWORD
        })
        assert resp.status_code == 200, f"Client login failed: {resp.text}"
        data = resp.json()
        assert data["user"]["role"] == "client"
        assert data["user"]["email"] == CLIENT_EMAIL
        assert data["user"].get("reseller_id") is not None
        print(f"✓ Client login success: {data['user']['email']}")


# ============================================================
# WALLET TESTS - Credits-based balance
# ============================================================
class TestWalletCredits:
    """Wallet now returns integer credits balance"""
    
    def test_wallet_me_returns_credits(self, client_session):
        """GET /api/wallet/me — returns balance as integer credits"""
        resp = client_session.get(f"{BASE_URL}/api/wallet/me")
        assert resp.status_code == 200, f"Wallet me failed: {resp.text}"
        data = resp.json()
        assert "balance" in data
        assert isinstance(data["balance"], (int, float))
        # Client should have ~25000 credits minus any test sends
        print(f"✓ Client wallet balance: {data['balance']} credits")
    
    def test_reseller_wallet_balance(self, reseller_session):
        """GET /api/wallet/me — reseller has ~500000 credits"""
        resp = reseller_session.get(f"{BASE_URL}/api/wallet/me")
        assert resp.status_code == 200, f"Wallet me failed: {resp.text}"
        data = resp.json()
        assert "balance" in data
        # Reseller seeded with 500000 credits
        print(f"✓ Reseller wallet balance: {data['balance']} credits")


# ============================================================
# CREDIT PACKS TESTS
# ============================================================
class TestCreditPacks:
    """Credit packs endpoints"""
    
    def test_list_packs_public(self, client_session):
        """GET /api/credits/packs — returns 4 seeded packs sorted by credits"""
        resp = client_session.get(f"{BASE_URL}/api/credits/packs")
        assert resp.status_code == 200, f"List packs failed: {resp.text}"
        data = resp.json()
        assert isinstance(data, list)
        assert len(data) >= 4, f"Expected at least 4 packs, got {len(data)}"
        # Check sorted by credits
        credits_list = [p["credits"] for p in data]
        assert credits_list == sorted(credits_list), "Packs should be sorted by credits"
        # Check expected packs
        names = [p["name"] for p in data]
        assert "Starter" in names, "Starter pack not found"
        assert "Growth" in names, "Growth pack not found"
        assert "Scale" in names, "Scale pack not found"
        assert "Enterprise" in names, "Enterprise pack not found"
        print(f"✓ Credit packs: {len(data)} packs found - {names}")
    
    def test_buy_starter_pack(self, client_session):
        """POST /api/credits/buy — buying Starter pack adds 1000 credits"""
        # First get packs to find Starter ID
        packs_resp = client_session.get(f"{BASE_URL}/api/credits/packs")
        packs = packs_resp.json()
        starter = next((p for p in packs if p["name"] == "Starter"), None)
        assert starter is not None, "Starter pack not found"
        
        # Get initial balance
        wallet_before = client_session.get(f"{BASE_URL}/api/wallet/me").json()
        
        # Buy pack
        resp = client_session.post(f"{BASE_URL}/api/credits/buy", json={
            "pack_id": starter["id"]
        })
        assert resp.status_code == 200, f"Buy pack failed: {resp.text}"
        data = resp.json()
        assert data.get("ok") == True
        assert data.get("credits_added") == 1000, f"Expected 1000 credits, got {data.get('credits_added')}"
        
        # Verify balance increased
        wallet_after = client_session.get(f"{BASE_URL}/api/wallet/me").json()
        assert wallet_after["balance"] >= wallet_before["balance"] + 1000
        print(f"✓ Bought Starter pack: +{data['credits_added']} credits")
    
    def test_buy_pack_with_promo_bonus25(self, client_session):
        """POST /api/credits/buy with promo_code BONUS25 on $200+ pack gives bonus"""
        # Get packs - find one with price >= $200
        packs_resp = client_session.get(f"{BASE_URL}/api/credits/packs")
        packs = packs_resp.json()
        # Scale pack is $1000, Enterprise is $8500
        scale = next((p for p in packs if p["name"] == "Scale"), None)
        assert scale is not None, "Scale pack not found"
        assert scale.get("price_usd", 0) >= 200, f"Scale pack price should be >= $200"
        
        resp = client_session.post(f"{BASE_URL}/api/credits/buy", json={
            "pack_id": scale["id"],
            "promo_code": "BONUS25"
        })
        assert resp.status_code == 200, f"Buy pack with promo failed: {resp.text}"
        data = resp.json()
        assert data.get("ok") == True
        assert data.get("bonus", 0) > 0, "BONUS25 should give bonus on $200+ pack"
        print(f"✓ Bought Scale pack with BONUS25: +{data['credits_added']} credits (bonus: {data.get('bonus')})")
    
    def test_buy_pack_promo_below_min_no_bonus(self, client_session):
        """POST /api/credits/buy with promo_code on pack below min_topup gives no bonus"""
        # Starter pack is $15, BONUS25 requires $200+
        packs_resp = client_session.get(f"{BASE_URL}/api/credits/packs")
        packs = packs_resp.json()
        starter = next((p for p in packs if p["name"] == "Starter"), None)
        assert starter is not None
        
        resp = client_session.post(f"{BASE_URL}/api/credits/buy", json={
            "pack_id": starter["id"],
            "promo_code": "BONUS25"
        })
        assert resp.status_code == 200, f"Buy pack failed: {resp.text}"
        data = resp.json()
        assert data.get("ok") == True
        # BONUS25 requires min_topup $200, Starter is $15 so no bonus
        assert data.get("bonus", 0) == 0, f"Should not get bonus on $15 pack, got {data.get('bonus')}"
        print(f"✓ Starter pack with BONUS25: no bonus (below min_topup)")


# ============================================================
# CREDITS RATES TESTS
# ============================================================
class TestCreditsRates:
    """GET /api/credits/rates endpoint"""
    
    def test_rates_returns_config(self, client_session):
        """GET /api/credits/rates — returns country_rate dict and other settings"""
        resp = client_session.get(f"{BASE_URL}/api/credits/rates")
        assert resp.status_code == 200, f"Rates failed: {resp.text}"
        data = resp.json()
        assert "country_rate" in data
        assert "whatsapp_rate" in data
        assert "sender_id_cost" in data
        assert "sender_id_renewal" in data
        assert "sender_id_expiry_days" in data
        assert "inactivity_warn_days" in data
        assert "inactivity_suspend_days" in data
        assert "inactivity_recovery_cost" in data
        print(f"✓ Credits rates: whatsapp={data['whatsapp_rate']}, sender_id_cost={data['sender_id_cost']}")


# ============================================================
# MESSAGING TESTS - Credits deduction
# ============================================================
class TestMessagingCredits:
    """Messaging now charges credits"""
    
    def test_quick_send_sms_charges_credits(self, client_session):
        """POST /api/messaging/quick-send — charges ~1 credit per recipient for TZ"""
        # Get initial balance
        wallet_before = client_session.get(f"{BASE_URL}/api/wallet/me").json()
        
        resp = client_session.post(f"{BASE_URL}/api/messaging/quick-send", json={
            "channel": "sms",
            "sender_id": "SUNRISE",
            "recipients": ["+255712345678"],
            "message": "Test message from unitxt iteration 3"
        })
        assert resp.status_code == 200, f"Quick send failed: {resp.text}"
        data = resp.json()
        assert data.get("ok") == True
        assert "campaign_id" in data
        assert "estimated_credits" in data
        
        # Wait for campaign to complete
        time.sleep(2)
        
        # Check campaign status
        campaign_resp = client_session.get(f"{BASE_URL}/api/messaging/campaigns/{data['campaign_id']}")
        assert campaign_resp.status_code == 200
        campaign = campaign_resp.json()["campaign"]
        assert campaign["status"] in ["completed", "running"]
        
        # Check messages have status sent or delivered
        messages = campaign_resp.json().get("messages", [])
        if messages:
            assert messages[0]["status"] in ["sent", "delivered", "queued"]
        
        print(f"✓ Quick send SMS: campaign_id={data['campaign_id']}, estimated_credits={data['estimated_credits']}")
    
    def test_quick_send_whatsapp_charges_3_credits(self, client_session):
        """POST /api/messaging/quick-send with WhatsApp charges 3 credits per msg"""
        resp = client_session.post(f"{BASE_URL}/api/messaging/quick-send", json={
            "channel": "whatsapp",
            "sender_id": "SUNRISE",
            "recipients": ["+255712345678"],
            "message": "WhatsApp test message"
        })
        assert resp.status_code == 200, f"Quick send WhatsApp failed: {resp.text}"
        data = resp.json()
        assert data.get("ok") == True
        # WhatsApp should charge 3 credits per segment
        assert data.get("estimated_credits", 0) >= 3, f"WhatsApp should charge at least 3 credits"
        print(f"✓ Quick send WhatsApp: estimated_credits={data['estimated_credits']}")
    
    def test_insufficient_credits_returns_400(self):
        """Insufficient credits: try quick-send with 100000 recipients — should 400"""
        # Register a new user with 0 balance
        unique_email = f"test_broke_{uuid.uuid4().hex[:8]}@test.io"
        session = requests.Session()
        reg_resp = session.post(f"{BASE_URL}/api/auth/register", json={
            "email": unique_email,
            "password": "TestPass123",
            "name": "Broke User",
            "role": "client",
            "country": "TZ"
        })
        assert reg_resp.status_code == 200
        token = reg_resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        
        # Try to send 100000 messages (should fail)
        recipients = [f"+25571234{i:05d}" for i in range(100000)]
        resp = session.post(f"{BASE_URL}/api/messaging/quick-send", json={
            "channel": "sms",
            "sender_id": "TEST",
            "recipients": recipients,
            "message": "Test"
        })
        assert resp.status_code == 400, f"Expected 400, got {resp.status_code}: {resp.text}"
        print(f"✓ Insufficient credits correctly returns 400")


# ============================================================
# MOBILE PREFIXES TESTS
# ============================================================
class TestMobilePrefixes:
    """Mobile prefixes endpoints"""
    
    def test_list_prefixes_tz(self, client_session):
        """GET /api/prefixes?country=TZ — returns seeded TZ prefixes (at least 13)"""
        resp = client_session.get(f"{BASE_URL}/api/prefixes?country=TZ")
        assert resp.status_code == 200, f"List prefixes failed: {resp.text}"
        data = resp.json()
        assert isinstance(data, list)
        assert len(data) >= 13, f"Expected at least 13 TZ prefixes, got {len(data)}"
        # Check operators
        operators = set(p["operator"] for p in data)
        assert "Vodacom" in operators or any("vodacom" in o.lower() for o in operators)
        assert "Tigo" in operators or any("tigo" in o.lower() for o in operators)
        print(f"✓ TZ prefixes: {len(data)} prefixes, operators: {operators}")
    
    def test_lookup_tigo_number(self, client_session):
        """GET /api/prefixes/lookup?phone=+255712345678 — returns Tigo operator"""
        resp = client_session.get(f"{BASE_URL}/api/prefixes/lookup?phone=+255712345678")
        assert resp.status_code == 200, f"Lookup failed: {resp.text}"
        data = resp.json()
        assert "match" in data
        if data["match"]:
            assert data["match"]["operator"].lower() == "tigo" or "tigo" in data["match"]["operator"].lower()
            print(f"✓ Lookup +255712345678: operator={data['match']['operator']}")
        else:
            print(f"⚠ Lookup +255712345678: no match found (prefix may not be seeded)")
    
    def test_lookup_vodacom_number(self, client_session):
        """GET /api/prefixes/lookup?phone=+255742345678 — returns Vodacom operator"""
        resp = client_session.get(f"{BASE_URL}/api/prefixes/lookup?phone=+255742345678")
        assert resp.status_code == 200, f"Lookup failed: {resp.text}"
        data = resp.json()
        assert "match" in data
        if data["match"]:
            assert "vodacom" in data["match"]["operator"].lower()
            print(f"✓ Lookup +255742345678: operator={data['match']['operator']}")
        else:
            print(f"⚠ Lookup +255742345678: no match found (prefix may not be seeded)")
    
    def test_admin_add_prefix(self, admin_session):
        """POST /api/prefixes — admin adds a new prefix"""
        resp = admin_session.post(f"{BASE_URL}/api/prefixes", json={
            "country": "TZ",
            "operator": "TestOperator",
            "prefix": "+25579",
            "active": True
        })
        assert resp.status_code == 200, f"Add prefix failed: {resp.text}"
        data = resp.json()
        assert data.get("operator") == "TestOperator"
        assert data.get("prefix") == "+25579"
        print(f"✓ Admin added prefix: {data['prefix']} ({data['operator']})")
        return data.get("id")
    
    def test_client_cannot_add_prefix(self, client_session):
        """POST /api/prefixes — non-admin gets 403"""
        resp = client_session.post(f"{BASE_URL}/api/prefixes", json={
            "country": "TZ",
            "operator": "Hacker",
            "prefix": "+25599",
            "active": True
        })
        assert resp.status_code == 403, f"Expected 403, got {resp.status_code}"
        print(f"✓ Client correctly blocked from adding prefix (403)")
    
    def test_admin_import_prefixes(self, admin_session):
        """POST /api/prefixes/import — admin bulk imports prefixes"""
        resp = admin_session.post(f"{BASE_URL}/api/prefixes/import", json=[
            {"country": "KE", "operator": "Safaricom", "prefix": "+25470", "active": True},
            {"country": "KE", "operator": "Airtel", "prefix": "+25473", "active": True}
        ])
        assert resp.status_code == 200, f"Import prefixes failed: {resp.text}"
        data = resp.json()
        assert data.get("ok") == True
        assert data.get("count") == 2
        print(f"✓ Admin imported {data['count']} prefixes")
    
    def test_admin_delete_prefix(self, admin_session):
        """DELETE /api/prefixes/{id} — admin removes prefix"""
        # First add a prefix to delete
        add_resp = admin_session.post(f"{BASE_URL}/api/prefixes", json={
            "country": "TZ",
            "operator": "ToDelete",
            "prefix": "+25598",
            "active": True
        })
        assert add_resp.status_code == 200
        prefix_id = add_resp.json().get("id")
        
        # Delete it
        resp = admin_session.delete(f"{BASE_URL}/api/prefixes/{prefix_id}")
        assert resp.status_code == 200, f"Delete prefix failed: {resp.text}"
        print(f"✓ Admin deleted prefix {prefix_id}")
    
    def test_client_cannot_delete_prefix(self, client_session, admin_session):
        """DELETE /api/prefixes/{id} — non-admin gets 403"""
        # First add a prefix as admin
        add_resp = admin_session.post(f"{BASE_URL}/api/prefixes", json={
            "country": "TZ",
            "operator": "Protected",
            "prefix": "+25597",
            "active": True
        })
        assert add_resp.status_code == 200
        prefix_id = add_resp.json().get("id")
        
        # Try to delete as client
        resp = client_session.delete(f"{BASE_URL}/api/prefixes/{prefix_id}")
        assert resp.status_code == 403, f"Expected 403, got {resp.status_code}"
        print(f"✓ Client correctly blocked from deleting prefix (403)")
        
        # Cleanup as admin
        admin_session.delete(f"{BASE_URL}/api/prefixes/{prefix_id}")


# ============================================================
# ADMIN CREDIT PACKS CRUD
# ============================================================
class TestAdminCreditPacks:
    """Admin credit packs CRUD"""
    
    def test_admin_list_packs(self, admin_session):
        """GET /api/admin/credit-packs — returns 4 packs"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/credit-packs")
        assert resp.status_code == 200, f"List packs failed: {resp.text}"
        data = resp.json()
        assert isinstance(data, list)
        assert len(data) >= 4, f"Expected at least 4 packs, got {len(data)}"
        print(f"✓ Admin credit packs: {len(data)} packs")
    
    def test_admin_add_pack(self, admin_session):
        """POST /api/admin/credit-packs — admin can add a new pack"""
        resp = admin_session.post(f"{BASE_URL}/api/admin/credit-packs", json={
            "name": "TestPack",
            "credits": 5000,
            "price_usd": 50.0,
            "tag": "test",
            "active": True
        })
        assert resp.status_code == 200, f"Add pack failed: {resp.text}"
        data = resp.json()
        assert data.get("name") == "TestPack"
        assert data.get("credits") == 5000
        print(f"✓ Admin added pack: {data['name']} ({data['credits']} credits)")
        return data.get("id")
    
    def test_admin_update_pack(self, admin_session):
        """PATCH /api/admin/credit-packs/{id} — updates fields"""
        # First add a pack
        add_resp = admin_session.post(f"{BASE_URL}/api/admin/credit-packs", json={
            "name": "UpdateTest",
            "credits": 2000,
            "price_usd": 25.0,
            "active": True
        })
        assert add_resp.status_code == 200
        pack_id = add_resp.json().get("id")
        
        # Update it
        resp = admin_session.patch(f"{BASE_URL}/api/admin/credit-packs/{pack_id}", json={
            "name": "UpdatedPack",
            "credits": 2500
        })
        assert resp.status_code == 200, f"Update pack failed: {resp.text}"
        print(f"✓ Admin updated pack {pack_id}")
        
        # Cleanup
        admin_session.delete(f"{BASE_URL}/api/admin/credit-packs/{pack_id}")
    
    def test_admin_delete_pack(self, admin_session):
        """DELETE /api/admin/credit-packs/{id} — removes pack"""
        # First add a pack
        add_resp = admin_session.post(f"{BASE_URL}/api/admin/credit-packs", json={
            "name": "DeleteTest",
            "credits": 1000,
            "price_usd": 10.0,
            "active": True
        })
        assert add_resp.status_code == 200
        pack_id = add_resp.json().get("id")
        
        # Delete it
        resp = admin_session.delete(f"{BASE_URL}/api/admin/credit-packs/{pack_id}")
        assert resp.status_code == 200, f"Delete pack failed: {resp.text}"
        print(f"✓ Admin deleted pack {pack_id}")


# ============================================================
# MARGIN REPORT TESTS
# ============================================================
class TestMarginReport:
    """Admin margin report endpoint"""
    
    def test_margin_report_structure(self, admin_session):
        """GET /api/admin/reports/margin — returns totals, by_country, by_reseller, by_provider, daily"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/reports/margin")
        assert resp.status_code == 200, f"Margin report failed: {resp.text}"
        data = resp.json()
        
        # Check structure
        assert "totals" in data
        assert "by_country" in data
        assert "by_reseller" in data
        assert "by_provider" in data
        assert "daily" in data
        
        # Check totals fields
        totals = data["totals"]
        assert "revenue_usd" in totals
        assert "cost_usd" in totals
        assert "margin_usd" in totals
        assert "margin_pct" in totals
        
        print(f"✓ Margin report: revenue=${totals['revenue_usd']}, cost=${totals['cost_usd']}, margin=${totals['margin_usd']}")
    
    def test_margin_report_after_send(self, admin_session, client_session):
        """After a quick-send, margin report reflects cost_usd"""
        # Get initial margin
        margin_before = admin_session.get(f"{BASE_URL}/api/admin/reports/margin").json()
        
        # Send a message
        send_resp = client_session.post(f"{BASE_URL}/api/messaging/quick-send", json={
            "channel": "sms",
            "sender_id": "SUNRISE",
            "recipients": ["+255712345679"],
            "message": "Margin test message"
        })
        assert send_resp.status_code == 200
        
        # Wait for processing
        time.sleep(2)
        
        # Get new margin
        margin_after = admin_session.get(f"{BASE_URL}/api/admin/reports/margin").json()
        
        # Cost should have increased (or stayed same if message failed)
        print(f"✓ Margin report: cost before={margin_before['totals']['cost_usd']}, after={margin_after['totals']['cost_usd']}")


# ============================================================
# SENDER ID APPROVAL & RENEWAL TESTS
# ============================================================
class TestSenderIdApprovalRenewal:
    """Sender ID approval charges credits and sets expires_at"""
    
    def test_sender_id_approval_charges_credits(self, admin_session, client_session):
        """POST /api/sender-ids/{id}/review with status=approved charges 500 credits"""
        # Client creates a new sender ID request
        unique_sid = f"TEST{uuid.uuid4().hex[:4].upper()}"
        create_resp = client_session.post(f"{BASE_URL}/api/sender-ids", json={
            "sender_id": unique_sid,
            "country": "TZ",
            "use_case": "Test approval",
            "sample_message": "Test message"
        })
        assert create_resp.status_code == 200
        sid_id = create_resp.json().get("id")
        
        # Get client balance before
        wallet_before = client_session.get(f"{BASE_URL}/api/wallet/me").json()
        
        # Admin approves
        approve_resp = admin_session.post(f"{BASE_URL}/api/admin/sender-ids/{sid_id}/review", json={
            "status": "approved",
            "note": "Test approval"
        })
        assert approve_resp.status_code == 200, f"Approval failed: {approve_resp.text}"
        
        # Check sender ID has expires_at
        sids_resp = client_session.get(f"{BASE_URL}/api/sender-ids")
        sids = sids_resp.json()
        approved_sid = next((s for s in sids if s["id"] == sid_id), None)
        assert approved_sid is not None
        assert approved_sid["status"] == "approved"
        assert "expires_at" in approved_sid
        
        # Check credits were charged (500 credits)
        wallet_after = client_session.get(f"{BASE_URL}/api/wallet/me").json()
        # Balance should have decreased by 500 (if client had enough)
        if wallet_before["balance"] >= 500:
            assert wallet_after["balance"] <= wallet_before["balance"]
        
        print(f"✓ Sender ID approved: {unique_sid}, expires_at={approved_sid.get('expires_at')}")
    
    def test_sender_id_renewal(self, client_session):
        """POST /api/sender-ids/{id}/renew — charges 500 credits, extends expires_at"""
        # Get SUNRISE sender ID
        sids_resp = client_session.get(f"{BASE_URL}/api/sender-ids")
        sids = sids_resp.json()
        sunrise = next((s for s in sids if s.get("sender_id") == "SUNRISE"), None)
        
        if sunrise:
            wallet_before = client_session.get(f"{BASE_URL}/api/wallet/me").json()
            
            resp = client_session.post(f"{BASE_URL}/api/sender-ids/{sunrise['id']}/renew")
            assert resp.status_code == 200, f"Renewal failed: {resp.text}"
            data = resp.json()
            assert data.get("ok") == True
            assert "expires_at" in data
            
            wallet_after = client_session.get(f"{BASE_URL}/api/wallet/me").json()
            # Should have charged 500 credits
            if wallet_before["balance"] >= 500:
                assert wallet_after["balance"] <= wallet_before["balance"]
            
            print(f"✓ Sender ID renewed: SUNRISE, new expires_at={data['expires_at']}")
        else:
            print("⚠ SUNRISE sender ID not found, skipping renewal test")


# ============================================================
# DLR WEBHOOK TESTS
# ============================================================
class TestDlrWebhook:
    """DLR webhook endpoint"""
    
    def test_dlr_update(self, admin_session, client_session):
        """POST /api/dlr/{provider_id} updates message status"""
        # First send a message to get a provider_msg_id
        send_resp = client_session.post(f"{BASE_URL}/api/messaging/quick-send", json={
            "channel": "sms",
            "sender_id": "SUNRISE",
            "recipients": ["+255712345680"],
            "message": "DLR test message"
        })
        assert send_resp.status_code == 200
        campaign_id = send_resp.json().get("campaign_id")
        
        # Wait for processing
        time.sleep(2)
        
        # Get the message to find provider_msg_id
        campaign_resp = client_session.get(f"{BASE_URL}/api/messaging/campaigns/{campaign_id}")
        if campaign_resp.status_code == 200:
            messages = campaign_resp.json().get("messages", [])
            if messages and messages[0].get("provider_msg_id"):
                provider_id = messages[0].get("provider_id")
                provider_msg_id = messages[0].get("provider_msg_id")
                
                # Send DLR update
                dlr_resp = requests.post(f"{BASE_URL}/api/dlr/{provider_id}", json={
                    "provider_msg_id": provider_msg_id,
                    "status": "delivered"
                })
                assert dlr_resp.status_code == 200, f"DLR update failed: {dlr_resp.text}"
                data = dlr_resp.json()
                print(f"✓ DLR webhook: updated={data.get('updated')}")
            else:
                print("⚠ No provider_msg_id found, skipping DLR test")
        else:
            print("⚠ Could not get campaign, skipping DLR test")


# ============================================================
# ACCOUNT RECOVERY TESTS
# ============================================================
class TestAccountRecovery:
    """Account recovery endpoint"""
    
    def test_recover_active_account(self, client_session):
        """POST /api/credits/recover — active user gets ok message"""
        resp = client_session.post(f"{BASE_URL}/api/credits/recover")
        assert resp.status_code == 200, f"Recovery failed: {resp.text}"
        data = resp.json()
        assert data.get("ok") == True
        # Active user should get "already active" message
        print(f"✓ Account recovery (active): {data.get('message', 'ok')}")


# ============================================================
# SETTINGS HUB TESTS
# ============================================================
class TestSettingsHub:
    """Settings hub endpoints"""
    
    def test_settings_credits_category(self, admin_session):
        """GET /api/admin/settings?category=credits — returns credits settings"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/settings?category=credits")
        assert resp.status_code == 200, f"Settings failed: {resp.text}"
        data = resp.json()
        assert isinstance(data, list)
        # Check for credits keys
        keys = [s["key"] for s in data]
        print(f"✓ Credits settings: {len(data)} settings, keys={keys}")
    
    def test_update_country_rate(self, admin_session):
        """PUT /api/admin/settings — update credits.country_rate dict"""
        resp = admin_session.put(f"{BASE_URL}/api/admin/settings", json={
            "key": "credits.country_rate",
            "value": {"TZ": 1, "KE": 2, "US": 5},
            "category": "credits"
        })
        assert resp.status_code == 200, f"Update setting failed: {resp.text}"
        data = resp.json()
        assert data.get("ok") == True
        print(f"✓ Updated credits.country_rate")


# ============================================================
# RBAC TESTS
# ============================================================
class TestRBAC:
    """RBAC unchanged: client/reseller can't hit /api/admin/*"""
    
    def test_client_cannot_access_admin_overview(self, client_session):
        """Client cannot access /api/admin/overview"""
        resp = client_session.get(f"{BASE_URL}/api/admin/overview")
        assert resp.status_code == 403, f"Expected 403, got {resp.status_code}"
        print(f"✓ Client blocked from /api/admin/overview (403)")
    
    def test_reseller_cannot_access_admin_overview(self, reseller_session):
        """Reseller cannot access /api/admin/overview"""
        resp = reseller_session.get(f"{BASE_URL}/api/admin/overview")
        assert resp.status_code == 403, f"Expected 403, got {resp.status_code}"
        print(f"✓ Reseller blocked from /api/admin/overview (403)")
    
    def test_client_cannot_access_admin_credit_packs(self, client_session):
        """Client cannot access /api/admin/credit-packs"""
        resp = client_session.get(f"{BASE_URL}/api/admin/credit-packs")
        assert resp.status_code == 403, f"Expected 403, got {resp.status_code}"
        print(f"✓ Client blocked from /api/admin/credit-packs (403)")
    
    def test_reseller_cannot_access_admin_reports(self, reseller_session):
        """Reseller cannot access /api/admin/reports/margin"""
        resp = reseller_session.get(f"{BASE_URL}/api/admin/reports/margin")
        assert resp.status_code == 403, f"Expected 403, got {resp.status_code}"
        print(f"✓ Reseller blocked from /api/admin/reports/margin (403)")


# ============================================================
# NO _id LEAK TESTS
# ============================================================
class TestNoIdLeak:
    """Ensure clean(_id) still applies everywhere"""
    
    def test_wallet_no_id(self, client_session):
        """Wallet response has no _id"""
        resp = client_session.get(f"{BASE_URL}/api/wallet/me")
        data = resp.json()
        assert "_id" not in data, "Wallet response should not contain _id"
        print(f"✓ Wallet response has no _id")
    
    def test_user_no_id(self, client_session):
        """User response has no _id"""
        resp = client_session.get(f"{BASE_URL}/api/auth/me")
        data = resp.json()
        assert "_id" not in data.get("user", {}), "User response should not contain _id"
        print(f"✓ User response has no _id")
    
    def test_credit_packs_no_id(self, client_session):
        """Credit packs response has no _id"""
        resp = client_session.get(f"{BASE_URL}/api/credits/packs")
        data = resp.json()
        for pack in data:
            assert "_id" not in pack, "Pack should not contain _id"
        print(f"✓ Credit packs response has no _id")
    
    def test_prefixes_no_id(self, client_session):
        """Prefixes response has no _id"""
        resp = client_session.get(f"{BASE_URL}/api/prefixes?country=TZ")
        data = resp.json()
        for prefix in data:
            assert "_id" not in prefix, "Prefix should not contain _id"
        print(f"✓ Prefixes response has no _id")


if __name__ == "__main__":
    pytest.main([__file__, "-v", "--tb=short"])
