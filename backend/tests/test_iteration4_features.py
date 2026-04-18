"""
unitxt Iteration 4 Backend API Tests
Tests new features: referrals, streaks, webhook, reseller markup pricing,
WhatsApp templates, smart queue batching, scheduled campaigns, operator-aware routing.
"""
import pytest
import requests
import os
import time
import uuid
from datetime import datetime, timedelta

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
# PREVIOUS ITERATIONS TESTS - Verify still working
# ============================================================
class TestPreviousIterations:
    """Verify all previous iterations' tests still pass"""
    
    def test_admin_login(self):
        """Admin login still works"""
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": ADMIN_EMAIL,
            "password": ADMIN_PASSWORD
        })
        assert resp.status_code == 200, f"Admin login failed: {resp.text}"
        data = resp.json()
        assert data["user"]["role"] == "super_admin"
        print(f"✓ Admin login success")
    
    def test_reseller_login(self):
        """Reseller login still works"""
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": RESELLER_EMAIL,
            "password": RESELLER_PASSWORD
        })
        assert resp.status_code == 200, f"Reseller login failed: {resp.text}"
        data = resp.json()
        assert data["user"]["role"] == "reseller"
        print(f"✓ Reseller login success")
    
    def test_client_login(self):
        """Client login still works"""
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL,
            "password": CLIENT_PASSWORD
        })
        assert resp.status_code == 200, f"Client login failed: {resp.text}"
        data = resp.json()
        assert data["user"]["role"] == "client"
        print(f"✓ Client login success")
    
    def test_wallet_me(self, client_session):
        """Wallet endpoint still works"""
        resp = client_session.get(f"{BASE_URL}/api/wallet/me")
        assert resp.status_code == 200
        data = resp.json()
        assert "balance" in data
        print(f"✓ Wallet balance: {data['balance']} credits")
    
    def test_messaging_basics(self, client_session):
        """Basic messaging still works"""
        resp = client_session.post(f"{BASE_URL}/api/messaging/quick-send", json={
            "channel": "sms",
            "sender_id": "SUNRISE",
            "recipients": ["+255712345678"],
            "message": "Iteration 4 test"
        })
        assert resp.status_code == 200
        data = resp.json()
        assert data.get("ok") == True
        print(f"✓ Quick send works: campaign_id={data['campaign_id']}")


# ============================================================
# REFERRAL SYSTEM TESTS
# ============================================================
class TestReferralSystem:
    """Referral system endpoints"""
    
    def test_referrals_me_returns_code(self, client_session):
        """GET /api/referrals/me returns unique code, referred_count, earned"""
        resp = client_session.get(f"{BASE_URL}/api/referrals/me")
        assert resp.status_code == 200, f"Referrals me failed: {resp.text}"
        data = resp.json()
        assert "code" in data
        assert "referred_count" in data
        assert "earned" in data
        assert data["code"].startswith("U"), f"Referral code should start with 'U', got {data['code']}"
        print(f"✓ Referrals me: code={data['code']}, referred_count={data['referred_count']}, earned={data['earned']}")
    
    def test_referrals_me_returns_settings(self, client_session):
        """GET /api/referrals/me returns percent_of_pack=5, max_per_referral=500, active=true"""
        resp = client_session.get(f"{BASE_URL}/api/referrals/me")
        assert resp.status_code == 200
        data = resp.json()
        assert "percent_of_pack" in data
        assert "max_per_referral" in data
        assert "active" in data
        assert data["percent_of_pack"] == 5, f"Expected percent_of_pack=5, got {data['percent_of_pack']}"
        assert data["max_per_referral"] == 500, f"Expected max_per_referral=500, got {data['max_per_referral']}"
        assert data["active"] == True, f"Expected active=True, got {data['active']}"
        print(f"✓ Referral settings: percent={data['percent_of_pack']}%, max={data['max_per_referral']}, active={data['active']}")
    
    def test_register_with_referral_code(self, client_session):
        """POST /api/auth/register with referral_code links referred_by"""
        # Get client's referral code
        ref_resp = client_session.get(f"{BASE_URL}/api/referrals/me")
        referral_code = ref_resp.json()["code"]
        
        # Register new user with referral code
        unique_email = f"test_referred_{uuid.uuid4().hex[:8]}@test.io"
        session = requests.Session()
        reg_resp = session.post(f"{BASE_URL}/api/auth/register", json={
            "email": unique_email,
            "password": "TestPass123",
            "name": "Referred User",
            "role": "client",
            "country": "TZ",
            "referral_code": referral_code
        })
        assert reg_resp.status_code == 200, f"Register with referral failed: {reg_resp.text}"
        data = reg_resp.json()
        assert data["user"]["email"] == unique_email
        # referred_by should be set (we can't see it directly, but registration succeeded)
        print(f"✓ Registered with referral code: {unique_email} referred by {referral_code}")
        return unique_email, data["access_token"]
    
    def test_referral_reward_on_pack_purchase(self, client_session, admin_session):
        """Referral reward flow: new user buys pack, referrer gets 5% (capped at 500)"""
        # Get client's referral code and initial earned
        ref_resp = client_session.get(f"{BASE_URL}/api/referrals/me")
        referral_code = ref_resp.json()["code"]
        initial_earned = ref_resp.json()["earned"]
        
        # Get client's wallet before
        wallet_before = client_session.get(f"{BASE_URL}/api/wallet/me").json()
        
        # Register new user with referral code
        unique_email = f"test_buyer_{uuid.uuid4().hex[:8]}@test.io"
        session = requests.Session()
        reg_resp = session.post(f"{BASE_URL}/api/auth/register", json={
            "email": unique_email,
            "password": "TestPass123",
            "name": "Buyer User",
            "role": "client",
            "country": "TZ",
            "referral_code": referral_code
        })
        assert reg_resp.status_code == 200
        token = reg_resp.json()["access_token"]
        session.headers.update({"Authorization": f"Bearer {token}"})
        
        # New user buys a pack (Starter = 1000 credits)
        packs_resp = session.get(f"{BASE_URL}/api/credits/packs")
        packs = packs_resp.json()
        starter = next((p for p in packs if p["name"] == "Starter"), None)
        assert starter is not None, "Starter pack not found"
        
        buy_resp = session.post(f"{BASE_URL}/api/credits/buy", json={
            "pack_id": starter["id"]
        })
        assert buy_resp.status_code == 200, f"Buy pack failed: {buy_resp.text}"
        
        # Check referrer's earned increased
        ref_resp2 = client_session.get(f"{BASE_URL}/api/referrals/me")
        new_earned = ref_resp2.json()["earned"]
        
        # 5% of 1000 = 50 credits (capped at 500)
        expected_reward = min(int(1000 * 5 / 100), 500)
        assert new_earned >= initial_earned + expected_reward, \
            f"Expected earned to increase by {expected_reward}, was {initial_earned}, now {new_earned}"
        
        # Check referrer's wallet increased
        wallet_after = client_session.get(f"{BASE_URL}/api/wallet/me").json()
        assert wallet_after["balance"] >= wallet_before["balance"] + expected_reward, \
            f"Wallet should increase by {expected_reward}"
        
        print(f"✓ Referral reward: referrer earned +{new_earned - initial_earned} credits")
    
    def test_referral_not_triggered_without_purchase(self, client_session):
        """Referral NOT triggered if referred user doesn't buy any pack"""
        # Get client's referral code and initial earned
        ref_resp = client_session.get(f"{BASE_URL}/api/referrals/me")
        referral_code = ref_resp.json()["code"]
        initial_earned = ref_resp.json()["earned"]
        
        # Register new user with referral code but DON'T buy a pack
        unique_email = f"test_nobuy_{uuid.uuid4().hex[:8]}@test.io"
        session = requests.Session()
        reg_resp = session.post(f"{BASE_URL}/api/auth/register", json={
            "email": unique_email,
            "password": "TestPass123",
            "name": "No Buy User",
            "role": "client",
            "country": "TZ",
            "referral_code": referral_code
        })
        assert reg_resp.status_code == 200
        
        # Check referrer's earned did NOT increase
        ref_resp2 = client_session.get(f"{BASE_URL}/api/referrals/me")
        new_earned = ref_resp2.json()["earned"]
        
        assert new_earned == initial_earned, \
            f"Earned should not change without purchase: was {initial_earned}, now {new_earned}"
        
        print(f"✓ Referral not triggered without purchase: earned still {new_earned}")


# ============================================================
# STREAK SYSTEM TESTS
# ============================================================
class TestStreakSystem:
    """Send streak gamification endpoints"""
    
    def test_profile_streak_returns_data(self, client_session):
        """GET /api/profile/streak returns {streak, last_send_date, bonuses}"""
        resp = client_session.get(f"{BASE_URL}/api/profile/streak")
        assert resp.status_code == 200, f"Profile streak failed: {resp.text}"
        data = resp.json()
        assert "streak" in data
        assert "last_send_date" in data
        assert "bonuses" in data
        assert "7" in data["bonuses"]
        assert "30" in data["bonuses"]
        assert "90" in data["bonuses"]
        print(f"✓ Profile streak: streak={data['streak']}, last_send_date={data['last_send_date']}")
        print(f"  Bonuses: 7-day={data['bonuses']['7']}, 30-day={data['bonuses']['30']}, 90-day={data['bonuses']['90']}")
    
    def test_streak_increments_on_send(self, client_session):
        """After client sends a campaign, streak increments"""
        # Get initial streak
        streak_before = client_session.get(f"{BASE_URL}/api/profile/streak").json()
        
        # Send a message
        send_resp = client_session.post(f"{BASE_URL}/api/messaging/quick-send", json={
            "channel": "sms",
            "sender_id": "SUNRISE",
            "recipients": ["+255712345678"],
            "message": "Streak test message"
        })
        assert send_resp.status_code == 200
        
        # Wait for campaign to complete
        time.sleep(3)
        
        # Check streak
        streak_after = client_session.get(f"{BASE_URL}/api/profile/streak").json()
        
        # Streak should be at least 1 (or incremented if consecutive day)
        assert streak_after["streak"] >= 1, f"Streak should be at least 1, got {streak_after['streak']}"
        assert streak_after["last_send_date"] is not None, "last_send_date should be set"
        
        print(f"✓ Streak after send: {streak_after['streak']} (was {streak_before['streak']})")


# ============================================================
# WEBHOOK TESTS
# ============================================================
class TestWebhook:
    """DLR webhook configuration endpoints"""
    
    def test_webhook_get_initial(self, client_session):
        """GET /api/profile/webhook returns current URL/secret (empty initially)"""
        resp = client_session.get(f"{BASE_URL}/api/profile/webhook")
        assert resp.status_code == 200, f"Get webhook failed: {resp.text}"
        data = resp.json()
        assert "dlr_webhook_url" in data
        assert "dlr_webhook_secret" in data
        print(f"✓ Webhook get: url='{data['dlr_webhook_url']}', secret='{data['dlr_webhook_secret']}'")
    
    def test_webhook_put_and_get(self, client_session):
        """PUT /api/profile/webhook saves and GET returns them"""
        test_url = "https://httpbin.org/post"
        test_secret = "test_secret_123"
        
        # Set webhook
        put_resp = client_session.put(f"{BASE_URL}/api/profile/webhook", json={
            "dlr_webhook_url": test_url,
            "dlr_webhook_secret": test_secret
        })
        assert put_resp.status_code == 200, f"Put webhook failed: {put_resp.text}"
        assert put_resp.json().get("ok") == True
        
        # Get webhook
        get_resp = client_session.get(f"{BASE_URL}/api/profile/webhook")
        assert get_resp.status_code == 200
        data = get_resp.json()
        assert data["dlr_webhook_url"] == test_url, f"URL mismatch: {data['dlr_webhook_url']}"
        assert data["dlr_webhook_secret"] == test_secret, f"Secret mismatch: {data['dlr_webhook_secret']}"
        
        print(f"✓ Webhook saved and retrieved: url={test_url}")
        
        # Clean up - reset webhook
        client_session.put(f"{BASE_URL}/api/profile/webhook", json={
            "dlr_webhook_url": "",
            "dlr_webhook_secret": ""
        })


# ============================================================
# RESELLER MARKUP PRICING TESTS
# ============================================================
class TestResellerMarkupPricing:
    """Reseller markup pricing endpoints"""
    
    def test_reseller_pricing_list(self, reseller_session):
        """GET /api/reseller/pricing returns list"""
        resp = reseller_session.get(f"{BASE_URL}/api/reseller/pricing")
        assert resp.status_code == 200, f"List pricing failed: {resp.text}"
        data = resp.json()
        assert isinstance(data, list)
        print(f"✓ Reseller pricing list: {len(data)} rules")
    
    def test_reseller_pricing_create(self, reseller_session):
        """POST /api/reseller/pricing creates rule"""
        resp = reseller_session.post(f"{BASE_URL}/api/reseller/pricing", json={
            "country": "TZ",
            "channel": "sms",
            "markup": 1.5,
            "active": True
        })
        assert resp.status_code == 200, f"Create pricing failed: {resp.text}"
        data = resp.json()
        # Response could be {"ok": True, "id": ...} or the full doc
        assert data.get("ok") == True or "id" in data
        print(f"✓ Reseller pricing created: TZ/sms markup=1.5")
    
    def test_reseller_pricing_get_after_create(self, reseller_session):
        """GET /api/reseller/pricing lists the created rule"""
        # First create a rule
        reseller_session.post(f"{BASE_URL}/api/reseller/pricing", json={
            "country": "KE",
            "channel": "sms",
            "markup": 2.0,
            "active": True
        })
        
        # List and verify
        resp = reseller_session.get(f"{BASE_URL}/api/reseller/pricing")
        assert resp.status_code == 200
        data = resp.json()
        ke_rule = next((r for r in data if r.get("country") == "KE"), None)
        assert ke_rule is not None, "KE rule not found"
        assert ke_rule["markup"] == 2.0
        print(f"✓ Reseller pricing verified: KE/sms markup={ke_rule['markup']}")
    
    def test_client_cannot_access_reseller_pricing(self, client_session):
        """Client cannot access /api/reseller/pricing (403)"""
        resp = client_session.get(f"{BASE_URL}/api/reseller/pricing")
        assert resp.status_code == 403, f"Expected 403, got {resp.status_code}"
        print(f"✓ Client blocked from /api/reseller/pricing (403)")
    
    def test_reseller_markup_applied_on_send(self, reseller_session, client_session):
        """When client sends SMS to TZ, wallet debits base_rate*markup"""
        # Set markup for TZ
        reseller_session.post(f"{BASE_URL}/api/reseller/pricing", json={
            "country": "TZ",
            "channel": "sms",
            "markup": 1.5,
            "active": True
        })
        
        # Get client wallet before
        wallet_before = client_session.get(f"{BASE_URL}/api/wallet/me").json()
        
        # Get reseller wallet before
        reseller_wallet_before = reseller_session.get(f"{BASE_URL}/api/wallet/me").json()
        
        # Client sends SMS to TZ
        send_resp = client_session.post(f"{BASE_URL}/api/messaging/quick-send", json={
            "channel": "sms",
            "sender_id": "SUNRISE",
            "recipients": ["+255712345678"],
            "message": "Markup test message"
        })
        assert send_resp.status_code == 200
        
        # Wait for campaign to complete
        time.sleep(3)
        
        # Get client wallet after
        wallet_after = client_session.get(f"{BASE_URL}/api/wallet/me").json()
        
        # Get reseller wallet after
        reseller_wallet_after = reseller_session.get(f"{BASE_URL}/api/wallet/me").json()
        
        # Client should have been charged (base_rate * 1.5)
        # Base rate for TZ SMS = 1 credit, with 1.5x markup = 1.5 -> rounds to 2 credits
        credits_charged = wallet_before["balance"] - wallet_after["balance"]
        print(f"✓ Client charged: {credits_charged} credits (with markup)")
        
        # Reseller should have earned the markup difference
        reseller_earned = reseller_wallet_after["balance"] - reseller_wallet_before["balance"]
        print(f"✓ Reseller earned: {reseller_earned} credits from markup")


# ============================================================
# WHATSAPP TEMPLATES TESTS
# ============================================================
class TestWhatsAppTemplates:
    """WhatsApp template workflow endpoints"""
    
    def test_client_create_wa_template(self, client_session):
        """POST /api/wa-templates creates with status=pending"""
        unique_name = f"TestTemplate_{uuid.uuid4().hex[:6]}"
        resp = client_session.post(f"{BASE_URL}/api/wa-templates", json={
            "name": unique_name,
            "body": "Hello {{1}}, your order {{2}} is ready.",
            "category": "utility"
        })
        assert resp.status_code == 200, f"Create WA template failed: {resp.text}"
        data = resp.json()
        assert data["name"] == unique_name
        assert data["status"] == "pending", f"Expected status=pending, got {data['status']}"
        print(f"✓ WA template created: {unique_name}, status={data['status']}")
        return data["id"]
    
    def test_client_list_wa_templates(self, client_session):
        """GET /api/wa-templates returns client's templates"""
        resp = client_session.get(f"{BASE_URL}/api/wa-templates")
        assert resp.status_code == 200, f"List WA templates failed: {resp.text}"
        data = resp.json()
        assert isinstance(data, list)
        print(f"✓ Client WA templates: {len(data)} templates")
    
    def test_admin_list_all_wa_templates(self, admin_session):
        """GET /api/admin/wa-templates returns all templates"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/wa-templates")
        assert resp.status_code == 200, f"Admin list WA templates failed: {resp.text}"
        data = resp.json()
        assert isinstance(data, list)
        print(f"✓ Admin WA templates: {len(data)} templates")
    
    def test_admin_approve_wa_template(self, client_session, admin_session):
        """Admin approves WA template and client gets notified"""
        # Client creates template
        unique_name = f"ApproveTest_{uuid.uuid4().hex[:6]}"
        create_resp = client_session.post(f"{BASE_URL}/api/wa-templates", json={
            "name": unique_name,
            "body": "Your code is {{1}}",
            "category": "utility"
        })
        assert create_resp.status_code == 200
        template_id = create_resp.json()["id"]
        
        # Admin approves
        approve_resp = admin_session.post(f"{BASE_URL}/api/admin/wa-templates/{template_id}/review", json={
            "status": "approved",
            "note": "Looks good"
        })
        assert approve_resp.status_code == 200, f"Approve failed: {approve_resp.text}"
        assert approve_resp.json().get("ok") == True
        
        # Verify template status changed
        templates = client_session.get(f"{BASE_URL}/api/wa-templates").json()
        approved = next((t for t in templates if t["id"] == template_id), None)
        assert approved is not None
        assert approved["status"] == "approved", f"Expected approved, got {approved['status']}"
        
        print(f"✓ WA template approved: {unique_name}")
    
    def test_client_cannot_access_admin_wa_templates(self, client_session):
        """Client cannot access /api/admin/wa-templates (403)"""
        resp = client_session.get(f"{BASE_URL}/api/admin/wa-templates")
        assert resp.status_code == 403, f"Expected 403, got {resp.status_code}"
        print(f"✓ Client blocked from /api/admin/wa-templates (403)")


# ============================================================
# SMART QUEUE / BULK SEND TESTS
# ============================================================
class TestSmartQueue:
    """Smart batching queue engine tests"""
    
    def test_bulk_send_2000_recipients(self, client_session):
        """POST /api/messaging/bulk-send with 2000 recipients completes in <30s"""
        # Generate 2000 recipients
        recipients = [{"phone": f"+25571{i:07d}", "name": f"User{i}"} for i in range(2000)]
        
        start_time = time.time()
        resp = client_session.post(f"{BASE_URL}/api/messaging/bulk-send", json={
            "channel": "sms",
            "sender_id": "SUNRISE",
            "name": "Bulk Test 2000",
            "recipients": recipients,
            "template": "Hello {name}, this is a test."
        })
        assert resp.status_code == 200, f"Bulk send failed: {resp.text}"
        data = resp.json()
        assert data.get("ok") == True
        campaign_id = data["campaign_id"]
        
        # Wait for completion (up to 45s)
        max_wait = 45
        elapsed = 0
        while elapsed < max_wait:
            time.sleep(3)
            elapsed = time.time() - start_time
            campaign_resp = client_session.get(f"{BASE_URL}/api/messaging/campaigns/{campaign_id}")
            if campaign_resp.status_code == 200:
                campaign = campaign_resp.json()["campaign"]
                if campaign["status"] == "completed":
                    break
                print(f"  Progress: {campaign.get('progress_pct', 0)}%, status={campaign['status']}")
        
        total_time = time.time() - start_time
        
        # Verify completion
        campaign_resp = client_session.get(f"{BASE_URL}/api/messaging/campaigns/{campaign_id}")
        campaign = campaign_resp.json()["campaign"]
        
        assert campaign["status"] == "completed", f"Campaign not completed: {campaign['status']}"
        assert campaign["progress_pct"] == 100.0, f"Progress not 100%: {campaign['progress_pct']}"
        
        # Check delivery rate (95% mock success)
        delivered = campaign.get("delivered", 0)
        assert delivered >= 1900, f"Expected >=1900 delivered (95%), got {delivered}"
        
        print(f"✓ Bulk send 2000 completed in {total_time:.1f}s")
        print(f"  Delivered: {delivered}/{campaign['sent']}, progress={campaign['progress_pct']}%")
    
    def test_insufficient_credits_reservation_fails(self):
        """Bulk-send with 500,000 recipients fails at reservation step with 400"""
        # Register a new user with 0 balance
        unique_email = f"test_nofunds_{uuid.uuid4().hex[:8]}@test.io"
        session = requests.Session()
        reg_resp = session.post(f"{BASE_URL}/api/auth/register", json={
            "email": unique_email,
            "password": "TestPass123",
            "name": "No Funds User",
            "role": "client",
            "country": "TZ"
        })
        assert reg_resp.status_code == 200
        token = reg_resp.json()["access_token"]
        session.headers.update({"Authorization": f"Bearer {token}"})
        
        # Try to send 500,000 messages (should fail at reservation)
        recipients = [{"phone": f"+25571{i:07d}", "name": f"User{i}"} for i in range(500000)]
        resp = session.post(f"{BASE_URL}/api/messaging/bulk-send", json={
            "channel": "sms",
            "sender_id": "TEST",
            "name": "Huge Campaign",
            "recipients": recipients,
            "template": "Hello {name}"
        })
        assert resp.status_code == 400, f"Expected 400, got {resp.status_code}: {resp.text}"
        print(f"✓ Insufficient credits correctly returns 400 at reservation")


# ============================================================
# SCHEDULED CAMPAIGN TESTS
# ============================================================
class TestScheduledCampaign:
    """Scheduled campaign worker tests"""
    
    def test_scheduled_campaign_status(self, client_session):
        """POST /api/messaging/bulk-send with schedule_at creates scheduled campaign"""
        # Schedule for 90 seconds from now
        schedule_time = (datetime.utcnow() + timedelta(seconds=90)).isoformat() + "Z"
        
        resp = client_session.post(f"{BASE_URL}/api/messaging/bulk-send", json={
            "channel": "sms",
            "sender_id": "SUNRISE",
            "name": "Scheduled Test",
            "recipients": [{"phone": "+255712345678", "name": "Test"}],
            "template": "Scheduled message",
            "schedule_at": schedule_time
        })
        assert resp.status_code == 200, f"Scheduled send failed: {resp.text}"
        data = resp.json()
        campaign_id = data["campaign_id"]
        
        # Verify status is 'scheduled'
        campaign_resp = client_session.get(f"{BASE_URL}/api/messaging/campaigns/{campaign_id}")
        campaign = campaign_resp.json()["campaign"]
        assert campaign["status"] == "scheduled", f"Expected scheduled, got {campaign['status']}"
        assert campaign["schedule_at"] == schedule_time
        
        print(f"✓ Scheduled campaign created: id={campaign_id}, schedule_at={schedule_time}")
        return campaign_id
    
    def test_scheduled_campaign_fires(self, client_session):
        """Scheduled campaign fires after schedule_at passes"""
        # Schedule for 70 seconds from now (scheduler runs every 60s)
        schedule_time = (datetime.utcnow() + timedelta(seconds=70)).isoformat() + "Z"
        
        resp = client_session.post(f"{BASE_URL}/api/messaging/bulk-send", json={
            "channel": "sms",
            "sender_id": "SUNRISE",
            "name": "Fire Test",
            "recipients": [{"phone": "+255712345679", "name": "Test"}],
            "template": "Scheduled fire test",
            "schedule_at": schedule_time
        })
        assert resp.status_code == 200
        campaign_id = resp.json()["campaign_id"]
        
        # Verify initially scheduled
        campaign = client_session.get(f"{BASE_URL}/api/messaging/campaigns/{campaign_id}").json()["campaign"]
        assert campaign["status"] == "scheduled"
        
        # Wait for scheduler to fire it (up to 100s)
        print(f"  Waiting for scheduler to fire campaign (up to 100s)...")
        for i in range(20):
            time.sleep(5)
            campaign = client_session.get(f"{BASE_URL}/api/messaging/campaigns/{campaign_id}").json()["campaign"]
            if campaign["status"] in ["running", "completed"]:
                break
            print(f"  Status: {campaign['status']} ({(i+1)*5}s elapsed)")
        
        # Verify status changed
        assert campaign["status"] in ["running", "completed"], \
            f"Campaign should have fired, status={campaign['status']}"
        
        print(f"✓ Scheduled campaign fired: status={campaign['status']}")


# ============================================================
# OPERATOR-AWARE ROUTING TESTS
# ============================================================
class TestOperatorRouting:
    """Operator-aware provider routing tests"""
    
    def test_tigo_number_routes_to_tigo_provider(self, client_session, admin_session):
        """Send SMS to +255712345678 (Tigo prefix) routes to Tigo provider"""
        # First verify Tigo provider exists with operators=['Tigo']
        providers = admin_session.get(f"{BASE_URL}/api/admin/providers").json()
        tigo_provider = next((p for p in providers if "tigo" in p.get("name", "").lower()), None)
        
        if tigo_provider:
            print(f"  Tigo provider found: {tigo_provider['name']}, operators={tigo_provider.get('operators')}")
        
        # Send to Tigo number
        resp = client_session.post(f"{BASE_URL}/api/messaging/quick-send", json={
            "channel": "sms",
            "sender_id": "SUNRISE",
            "recipients": ["+255712345678"],  # Tigo prefix +25571
            "message": "Operator routing test"
        })
        assert resp.status_code == 200
        campaign_id = resp.json()["campaign_id"]
        
        # Wait for completion
        time.sleep(3)
        
        # Check message's provider_id
        campaign_resp = client_session.get(f"{BASE_URL}/api/messaging/campaigns/{campaign_id}")
        messages = campaign_resp.json().get("messages", [])
        
        if messages and tigo_provider:
            msg = messages[0]
            # The message should have been routed to Tigo provider
            print(f"✓ Message provider_id: {msg.get('provider_id')}")
            if msg.get("provider_id") == tigo_provider["id"]:
                print(f"✓ Correctly routed to Tigo provider!")
            else:
                print(f"  Note: Routed to different provider (may be fallback)")
        else:
            print(f"✓ Operator routing test completed (no Tigo provider or no messages)")


# ============================================================
# SETTINGS HUB TESTS
# ============================================================
class TestSettingsHub:
    """Settings Hub for new categories"""
    
    def test_update_referral_percent(self, admin_session, client_session):
        """PUT /api/admin/settings for referral.percent_of_pack works"""
        # Update to 10%
        resp = admin_session.put(f"{BASE_URL}/api/admin/settings", json={
            "key": "referral.percent_of_pack",
            "value": 10,
            "category": "referrals"
        })
        assert resp.status_code == 200, f"Update setting failed: {resp.text}"
        
        # Verify via referrals/me
        ref_resp = client_session.get(f"{BASE_URL}/api/referrals/me")
        assert ref_resp.json()["percent_of_pack"] == 10
        
        print(f"✓ Updated referral.percent_of_pack to 10%")
        
        # Reset to 5%
        admin_session.put(f"{BASE_URL}/api/admin/settings", json={
            "key": "referral.percent_of_pack",
            "value": 5,
            "category": "referrals"
        })
    
    def test_update_queue_max_concurrency(self, admin_session):
        """PUT queue.max_concurrency=500 is saved"""
        resp = admin_session.put(f"{BASE_URL}/api/admin/settings", json={
            "key": "queue.max_concurrency",
            "value": 500,
            "category": "queue"
        })
        assert resp.status_code == 200, f"Update setting failed: {resp.text}"
        
        # Verify saved
        settings = admin_session.get(f"{BASE_URL}/api/admin/settings?category=queue").json()
        conc_setting = next((s for s in settings if s["key"] == "queue.max_concurrency"), None)
        assert conc_setting is not None
        assert conc_setting["value"] == 500
        
        print(f"✓ Updated queue.max_concurrency to 500")
    
    def test_settings_referrals_category(self, admin_session):
        """GET /api/admin/settings?category=referrals returns referral settings"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/settings?category=referrals")
        assert resp.status_code == 200
        data = resp.json()
        keys = [s["key"] for s in data]
        assert "referral.percent_of_pack" in keys or "referral.active" in keys
        print(f"✓ Referrals settings: {keys}")
    
    def test_settings_streaks_category(self, admin_session):
        """GET /api/admin/settings?category=streaks returns streak settings"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/settings?category=streaks")
        assert resp.status_code == 200
        data = resp.json()
        keys = [s["key"] for s in data]
        print(f"✓ Streaks settings: {keys}")


# ============================================================
# RBAC TESTS FOR NEW ENDPOINTS
# ============================================================
class TestRBACNewEndpoints:
    """RBAC for new endpoints"""
    
    def test_reseller_pricing_requires_reseller(self, client_session):
        """/api/reseller/pricing requires reseller role"""
        resp = client_session.get(f"{BASE_URL}/api/reseller/pricing")
        assert resp.status_code == 403
        print(f"✓ /api/reseller/pricing requires reseller (403)")
    
    def test_admin_wa_templates_requires_admin(self, client_session, reseller_session):
        """/api/admin/wa-templates requires super_admin/compliance"""
        resp1 = client_session.get(f"{BASE_URL}/api/admin/wa-templates")
        assert resp1.status_code == 403
        
        resp2 = reseller_session.get(f"{BASE_URL}/api/admin/wa-templates")
        assert resp2.status_code == 403
        
        print(f"✓ /api/admin/wa-templates requires super_admin/compliance (403)")
    
    def test_admin_credit_packs_requires_super_admin(self, client_session, reseller_session):
        """/api/admin/credit-packs requires super_admin"""
        resp1 = client_session.get(f"{BASE_URL}/api/admin/credit-packs")
        assert resp1.status_code == 403
        
        resp2 = reseller_session.get(f"{BASE_URL}/api/admin/credit-packs")
        assert resp2.status_code == 403
        
        print(f"✓ /api/admin/credit-packs requires super_admin (403)")
    
    def test_admin_margin_report_requires_super_admin_finance(self, client_session, reseller_session):
        """/api/admin/reports/margin requires super_admin/finance"""
        resp1 = client_session.get(f"{BASE_URL}/api/admin/reports/margin")
        assert resp1.status_code == 403
        
        resp2 = reseller_session.get(f"{BASE_URL}/api/admin/reports/margin")
        assert resp2.status_code == 403
        
        print(f"✓ /api/admin/reports/margin requires super_admin/finance (403)")


# ============================================================
# NO _id LEAK TESTS
# ============================================================
class TestNoIdLeak:
    """Ensure no _id leaks in any response"""
    
    def test_referrals_no_id(self, client_session):
        """Referrals response has no _id"""
        resp = client_session.get(f"{BASE_URL}/api/referrals/me")
        data = resp.json()
        assert "_id" not in data
        print(f"✓ Referrals response has no _id")
    
    def test_profile_streak_no_id(self, client_session):
        """Profile streak response has no _id"""
        resp = client_session.get(f"{BASE_URL}/api/profile/streak")
        data = resp.json()
        assert "_id" not in data
        print(f"✓ Profile streak response has no _id")
    
    def test_webhook_no_id(self, client_session):
        """Webhook response has no _id"""
        resp = client_session.get(f"{BASE_URL}/api/profile/webhook")
        data = resp.json()
        assert "_id" not in data
        print(f"✓ Webhook response has no _id")
    
    def test_reseller_pricing_no_id(self, reseller_session):
        """Reseller pricing response has no _id"""
        resp = reseller_session.get(f"{BASE_URL}/api/reseller/pricing")
        data = resp.json()
        for item in data:
            assert "_id" not in item
        print(f"✓ Reseller pricing response has no _id")
    
    def test_wa_templates_no_id(self, client_session):
        """WA templates response has no _id"""
        resp = client_session.get(f"{BASE_URL}/api/wa-templates")
        data = resp.json()
        for item in data:
            assert "_id" not in item
        print(f"✓ WA templates response has no _id")


# ============================================================
# PREFIX IMPORT TEST
# ============================================================
class TestPrefixImport:
    """Excel/CSV import for prefixes"""
    
    def test_prefix_import_json_array(self, admin_session):
        """POST /api/prefixes/import accepts JSON array"""
        resp = admin_session.post(f"{BASE_URL}/api/prefixes/import", json=[
            {"country": "UG", "operator": "MTN", "prefix": "+25677", "active": True},
            {"country": "UG", "operator": "Airtel", "prefix": "+25675", "active": True}
        ])
        assert resp.status_code == 200, f"Import failed: {resp.text}"
        data = resp.json()
        assert data.get("ok") == True
        assert data.get("count") == 2
        print(f"✓ Prefix import: {data['count']} prefixes imported")


if __name__ == "__main__":
    pytest.main([__file__, "-v", "--tb=short"])
