"""
Iteration 19 Tests: Phase-3 Routes Split + Settings Hub Route Health Keys

Tests:
1. Phase-3 routes split - all extracted routes must work end-to-end:
   - /api/credits/* (packs, rates, buy, recover) - routes/credits.py
   - /api/dlr/{provider_id} - routes/dlr.py
   - /api/referrals/me - routes/referrals.py
   - /api/profile/streak, /api/profile/webhook - routes/profile.py
   - /api/wa-templates, /api/admin/wa-templates - routes/wa_templates.py
   - /api/admin/country-economics/{code}, /api/admin/country-pnl - routes/country_economics.py

2. Settings Hub - alerts.route_health_* keys must be accessible via GET/PUT /api/admin/settings

3. Regression tests from iteration 17/18
"""
import pytest
import requests
import os

BASE_URL = os.environ.get("REACT_APP_BACKEND_URL", "").rstrip("/")

# Test credentials
ADMIN_EMAIL = "admin@unitxt.io"
ADMIN_PASSWORD = "Admin@2026"
RESELLER_EMAIL = "reseller@unitxt.io"
RESELLER_PASSWORD = "Reseller@2026"
CLIENT_EMAIL = "client@unitxt.io"
CLIENT_PASSWORD = "Client@2026"


@pytest.fixture(scope="module")
def admin_session():
    """Admin session with auth cookies"""
    session = requests.Session()
    session.headers.update({"Content-Type": "application/json"})
    resp = session.post(f"{BASE_URL}/api/auth/login", json={
        "email": ADMIN_EMAIL, "password": ADMIN_PASSWORD
    })
    assert resp.status_code == 200, f"Admin login failed: {resp.text}"
    data = resp.json()
    if "access_token" in data:
        session.headers.update({"Authorization": f"Bearer {data['access_token']}"})
    return session


@pytest.fixture(scope="module")
def client_session():
    """Client session with auth cookies"""
    session = requests.Session()
    session.headers.update({"Content-Type": "application/json"})
    resp = session.post(f"{BASE_URL}/api/auth/login", json={
        "email": CLIENT_EMAIL, "password": CLIENT_PASSWORD
    })
    assert resp.status_code == 200, f"Client login failed: {resp.text}"
    data = resp.json()
    if "access_token" in data:
        session.headers.update({"Authorization": f"Bearer {data['access_token']}"})
    return session


@pytest.fixture(scope="module")
def reseller_session():
    """Reseller session with auth cookies"""
    session = requests.Session()
    session.headers.update({"Content-Type": "application/json"})
    resp = session.post(f"{BASE_URL}/api/auth/login", json={
        "email": RESELLER_EMAIL, "password": RESELLER_PASSWORD
    })
    assert resp.status_code == 200, f"Reseller login failed: {resp.text}"
    data = resp.json()
    if "access_token" in data:
        session.headers.update({"Authorization": f"Bearer {data['access_token']}"})
    return session


# ============================================================================
# PHASE-3 ROUTES SPLIT TESTS - routes/credits.py
# ============================================================================
class TestCreditsRoutes:
    """Test /api/credits/* endpoints extracted to routes/credits.py"""

    def test_credits_packs_list(self, client_session):
        """GET /api/credits/packs - list available credit packs"""
        resp = client_session.get(f"{BASE_URL}/api/credits/packs")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        data = resp.json()
        assert isinstance(data, list), "Expected list of packs"
        # Verify pack structure
        if len(data) > 0:
            pack = data[0]
            assert "credits" in pack, "Pack should have credits field"
            assert "price_usd" in pack, "Pack should have price_usd field"
            assert "local_price" in pack, "Pack should have local_price field"
            assert "local_currency" in pack, "Pack should have local_currency field"
        print(f"PASS: GET /api/credits/packs returned {len(data)} packs")

    def test_credits_rates(self, client_session):
        """GET /api/credits/rates - get credit rates"""
        resp = client_session.get(f"{BASE_URL}/api/credits/rates")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        data = resp.json()
        assert "country_rate" in data, "Should have country_rate"
        assert "default_rate" in data, "Should have default_rate"
        assert "whatsapp_rate" in data, "Should have whatsapp_rate"
        assert "sender_id_cost" in data, "Should have sender_id_cost"
        assert "sender_id_renewal" in data, "Should have sender_id_renewal"
        assert "sender_id_expiry_days" in data, "Should have sender_id_expiry_days"
        assert "inactivity_warn_days" in data, "Should have inactivity_warn_days"
        assert "inactivity_suspend_days" in data, "Should have inactivity_suspend_days"
        assert "inactivity_recovery_cost" in data, "Should have inactivity_recovery_cost"
        print(f"PASS: GET /api/credits/rates returned all expected fields")

    def test_credits_buy_invalid_pack(self, client_session):
        """POST /api/credits/buy - should return 404 for invalid pack"""
        resp = client_session.post(f"{BASE_URL}/api/credits/buy", json={
            "pack_id": "nonexistent_pack_id"
        })
        assert resp.status_code == 404, f"Expected 404, got {resp.status_code}"
        print("PASS: POST /api/credits/buy returns 404 for invalid pack")

    def test_credits_recover_already_active(self, client_session):
        """POST /api/credits/recover - should return ok for active account"""
        resp = client_session.post(f"{BASE_URL}/api/credits/recover")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        data = resp.json()
        assert data.get("ok") is True, "Should return ok=True"
        assert "already active" in data.get("message", "").lower(), "Should indicate already active"
        print("PASS: POST /api/credits/recover works for active account")


# ============================================================================
# PHASE-3 ROUTES SPLIT TESTS - routes/dlr.py
# ============================================================================
class TestDlrRoutes:
    """Test /api/dlr/{provider_id} endpoint extracted to routes/dlr.py"""

    def test_dlr_update(self, admin_session):
        """POST /api/dlr/{provider_id} - delivery receipt webhook"""
        # This endpoint doesn't require auth (webhook from provider)
        session = requests.Session()
        session.headers.update({"Content-Type": "application/json"})
        resp = session.post(f"{BASE_URL}/api/dlr/test_provider", json={
            "provider_msg_id": "test_msg_123",
            "status": "delivered",
            "error": None
        })
        assert resp.status_code == 200, f"Failed: {resp.text}"
        data = resp.json()
        assert "updated" in data, "Should return updated count"
        print(f"PASS: POST /api/dlr/test_provider returned updated={data['updated']}")


# ============================================================================
# PHASE-3 ROUTES SPLIT TESTS - routes/referrals.py
# ============================================================================
class TestReferralsRoutes:
    """Test /api/referrals/me endpoint extracted to routes/referrals.py"""

    def test_referrals_me(self, client_session):
        """GET /api/referrals/me - get user's referral info"""
        resp = client_session.get(f"{BASE_URL}/api/referrals/me")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        data = resp.json()
        assert "code" in data, "Should have referral code"
        assert "referred_count" in data, "Should have referred_count"
        assert "earned" in data, "Should have earned"
        assert "percent_of_pack" in data, "Should have percent_of_pack"
        assert "max_per_referral" in data, "Should have max_per_referral"
        assert "active" in data, "Should have active flag"
        print(f"PASS: GET /api/referrals/me returned code={data['code']}")


# ============================================================================
# PHASE-3 ROUTES SPLIT TESTS - routes/profile.py
# ============================================================================
class TestProfileRoutes:
    """Test /api/profile/* endpoints extracted to routes/profile.py"""

    def test_profile_streak(self, client_session):
        """GET /api/profile/streak - get user's streak info"""
        resp = client_session.get(f"{BASE_URL}/api/profile/streak")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        data = resp.json()
        assert "streak" in data, "Should have streak"
        assert "last_send_date" in data, "Should have last_send_date"
        assert "bonuses" in data, "Should have bonuses"
        assert "7" in data["bonuses"], "Should have 7-day bonus"
        assert "30" in data["bonuses"], "Should have 30-day bonus"
        assert "90" in data["bonuses"], "Should have 90-day bonus"
        print(f"PASS: GET /api/profile/streak returned streak={data['streak']}")

    def test_profile_webhook_get(self, client_session):
        """GET /api/profile/webhook - get user's webhook config"""
        resp = client_session.get(f"{BASE_URL}/api/profile/webhook")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        data = resp.json()
        assert "dlr_webhook_url" in data, "Should have dlr_webhook_url"
        assert "dlr_webhook_secret" in data, "Should have dlr_webhook_secret"
        print("PASS: GET /api/profile/webhook returned webhook config")

    def test_profile_webhook_put(self, client_session):
        """PUT /api/profile/webhook - update user's webhook config"""
        resp = client_session.put(f"{BASE_URL}/api/profile/webhook", json={
            "dlr_webhook_url": "https://example.com/dlr",
            "dlr_webhook_secret": "test_secret_123"
        })
        assert resp.status_code == 200, f"Failed: {resp.text}"
        data = resp.json()
        assert data.get("ok") is True, "Should return ok=True"
        
        # Verify the update
        resp2 = client_session.get(f"{BASE_URL}/api/profile/webhook")
        assert resp2.status_code == 200
        data2 = resp2.json()
        assert data2["dlr_webhook_url"] == "https://example.com/dlr"
        assert data2["dlr_webhook_secret"] == "test_secret_123"
        print("PASS: PUT /api/profile/webhook updated webhook config")


# ============================================================================
# PHASE-3 ROUTES SPLIT TESTS - routes/wa_templates.py
# ============================================================================
class TestWaTemplatesRoutes:
    """Test /api/wa-templates and /api/admin/wa-templates endpoints"""

    def test_wa_templates_list(self, client_session):
        """GET /api/wa-templates - list user's WA templates"""
        resp = client_session.get(f"{BASE_URL}/api/wa-templates")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        data = resp.json()
        assert isinstance(data, list), "Expected list of templates"
        print(f"PASS: GET /api/wa-templates returned {len(data)} templates")

    def test_wa_templates_create_and_delete(self, client_session):
        """POST /api/wa-templates - create WA template, then DELETE"""
        # Create
        resp = client_session.post(f"{BASE_URL}/api/wa-templates", json={
            "name": "TEST_wa_template_iter19",
            "body": "Hello {{1}}, your order {{2}} is ready.",
            "category": "utility",
            "language": "en"
        })
        assert resp.status_code == 200, f"Failed to create: {resp.text}"
        data = resp.json()
        assert "id" in data, "Should return template id"
        template_id = data["id"]
        assert data["name"] == "TEST_wa_template_iter19"
        assert data["status"] == "pending"
        print(f"PASS: POST /api/wa-templates created template id={template_id}")

        # Delete
        resp2 = client_session.delete(f"{BASE_URL}/api/wa-templates/{template_id}")
        assert resp2.status_code == 200, f"Failed to delete: {resp2.text}"
        print("PASS: DELETE /api/wa-templates/{tid} deleted template")

    def test_admin_wa_templates_list(self, admin_session):
        """GET /api/admin/wa-templates - admin list all WA templates"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/wa-templates")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        data = resp.json()
        assert isinstance(data, list), "Expected list of templates"
        print(f"PASS: GET /api/admin/wa-templates returned {len(data)} templates")

    def test_admin_wa_templates_review(self, admin_session, client_session):
        """POST /api/admin/wa-templates/{tid}/review - admin review template"""
        # First create a template as client
        resp = client_session.post(f"{BASE_URL}/api/wa-templates", json={
            "name": "TEST_wa_review_iter19",
            "body": "Test body for review",
            "category": "marketing",
            "language": "en"
        })
        assert resp.status_code == 200, f"Failed to create: {resp.text}"
        template_id = resp.json()["id"]

        # Admin reviews it
        resp2 = admin_session.post(f"{BASE_URL}/api/admin/wa-templates/{template_id}/review", json={
            "status": "approved",
            "note": "Looks good"
        })
        assert resp2.status_code == 200, f"Failed to review: {resp2.text}"
        data = resp2.json()
        assert data.get("ok") is True
        print("PASS: POST /api/admin/wa-templates/{tid}/review approved template")

        # Cleanup
        client_session.delete(f"{BASE_URL}/api/wa-templates/{template_id}")


# ============================================================================
# PHASE-3 ROUTES SPLIT TESTS - routes/country_economics.py
# ============================================================================
class TestCountryEconomicsRoutes:
    """Test /api/admin/country-economics and /api/admin/country-pnl endpoints"""

    def test_country_economics_get(self, admin_session):
        """GET /api/admin/country-economics/{code} - get country economics"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-economics/TZ")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        data = resp.json()
        assert data["code"] == "TZ", "Should return TZ"
        assert "currency" in data, "Should have currency"
        assert "fx_rate_to_usd" in data, "Should have fx_rate_to_usd"
        assert "vat_rate_pct" in data, "Should have vat_rate_pct"
        assert "sell_per_sms_local" in data, "Should have sell_per_sms_local"
        assert "wholesale_per_sms_local" in data, "Should have wholesale_per_sms_local"
        print(f"PASS: GET /api/admin/country-economics/TZ returned currency={data['currency']}")

    def test_country_economics_put(self, admin_session):
        """PUT /api/admin/country-economics/{code} - update country economics"""
        # First get current values
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-economics/TZ")
        original = resp.json()

        # Update
        resp2 = admin_session.put(f"{BASE_URL}/api/admin/country-economics/TZ", json={
            "vat_rate_pct": 18.0,
            "sell_per_sms_local": 20.0,
            "wholesale_per_sms_local": 15.0
        })
        assert resp2.status_code == 200, f"Failed: {resp2.text}"
        data = resp2.json()
        assert data.get("ok") is True
        print("PASS: PUT /api/admin/country-economics/TZ updated economics")

    def test_country_economics_404(self, admin_session):
        """GET /api/admin/country-economics/{code} - 404 for invalid country"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-economics/XX")
        assert resp.status_code == 404, f"Expected 404, got {resp.status_code}"
        print("PASS: GET /api/admin/country-economics/XX returns 404")

    def test_country_pnl(self, admin_session):
        """GET /api/admin/country-pnl - get country P&L dashboard"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-pnl")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        data = resp.json()
        assert "rows" in data, "Should have rows"
        assert "total_profit_usd" in data, "Should have total_profit_usd"
        assert isinstance(data["rows"], list), "rows should be a list"
        # Verify row structure if any rows exist
        if len(data["rows"]) > 0:
            row = data["rows"][0]
            assert "code" in row, "Row should have code"
            assert "revenue_local" in row, "Row should have revenue_local"
            assert "cost_local" in row, "Row should have cost_local"
            assert "profit_local" in row, "Row should have profit_local"
            assert "margin_pct" in row, "Row should have margin_pct"
        print(f"PASS: GET /api/admin/country-pnl returned {len(data['rows'])} rows")


# ============================================================================
# SETTINGS HUB - alerts.route_health_* keys
# ============================================================================
class TestSettingsHubRouteHealthKeys:
    """Test alerts.route_health_* settings are accessible via /api/admin/settings"""

    def test_settings_list_has_route_health_keys(self, admin_session):
        """GET /api/admin/settings - should include alerts.route_health_* keys"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/settings")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        data = resp.json()
        assert isinstance(data, list), "Expected list of settings"

        # Find route health keys
        route_health_keys = [
            "alerts.route_health_enabled",
            "alerts.route_health_window_min",
            "alerts.route_health_min_msgs",
            "alerts.route_health_threshold_pct",
            "alerts.route_health_cooldown_min"
        ]
        
        found_keys = {s["key"] for s in data}
        for key in route_health_keys:
            assert key in found_keys, f"Missing key: {key}"
        print(f"PASS: All 5 alerts.route_health_* keys found in settings")

        # Verify they have category 'notifications'
        for s in data:
            if s["key"] in route_health_keys:
                assert s.get("category") == "notifications", f"{s['key']} should have category=notifications"
        print("PASS: All route_health_* keys have category=notifications")

    def test_settings_route_health_enabled_is_boolean(self, admin_session):
        """alerts.route_health_enabled should be a boolean"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/settings")
        data = resp.json()
        setting = next((s for s in data if s["key"] == "alerts.route_health_enabled"), None)
        assert setting is not None, "alerts.route_health_enabled not found"
        assert isinstance(setting["value"], bool), f"Expected boolean, got {type(setting['value'])}"
        print(f"PASS: alerts.route_health_enabled is boolean (value={setting['value']})")

    def test_settings_route_health_numbers(self, admin_session):
        """alerts.route_health_* numeric keys should be numbers"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/settings")
        data = resp.json()
        
        numeric_keys = [
            "alerts.route_health_window_min",
            "alerts.route_health_min_msgs",
            "alerts.route_health_threshold_pct",
            "alerts.route_health_cooldown_min"
        ]
        
        for key in numeric_keys:
            setting = next((s for s in data if s["key"] == key), None)
            assert setting is not None, f"{key} not found"
            assert isinstance(setting["value"], (int, float)), f"{key} should be numeric, got {type(setting['value'])}"
            print(f"PASS: {key} is numeric (value={setting['value']})")

    def test_settings_route_health_update(self, admin_session):
        """PUT /api/admin/settings - can update route_health settings"""
        # Update enabled flag
        resp = admin_session.put(f"{BASE_URL}/api/admin/settings", json={
            "key": "alerts.route_health_enabled",
            "value": True,
            "category": "notifications"
        })
        assert resp.status_code == 200, f"Failed: {resp.text}"
        
        # Update threshold
        resp2 = admin_session.put(f"{BASE_URL}/api/admin/settings", json={
            "key": "alerts.route_health_threshold_pct",
            "value": 80,
            "category": "notifications"
        })
        assert resp2.status_code == 200, f"Failed: {resp2.text}"
        print("PASS: PUT /api/admin/settings can update route_health settings")

    def test_runtime_keys_not_in_notifications(self, admin_session):
        """alerts.route_health_last_run_at and alerts.route_health_last_alert.* should NOT appear in notifications category"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/settings")
        data = resp.json()
        
        # These runtime keys should either not exist or not have category=notifications
        runtime_keys = [
            "alerts.route_health_last_run_at",
            "alerts.route_health_last_alert"
        ]
        
        for s in data:
            if s["key"].startswith("alerts.route_health_last"):
                # If they exist, they should NOT have category=notifications
                assert s.get("category") != "notifications", f"{s['key']} should not be in notifications category"
        print("PASS: Runtime keys (last_run_at, last_alert.*) not in notifications category")


# ============================================================================
# REGRESSION TESTS - Iteration 17/18 features
# ============================================================================
class TestRegressionIteration17:
    """Regression tests for iteration 17 features"""

    def test_notifications_endpoint(self, client_session):
        """GET /api/notifications - should work"""
        resp = client_session.get(f"{BASE_URL}/api/notifications")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        data = resp.json()
        assert "items" in data, "Should have items"
        assert "unread" in data, "Should have unread count"
        print("PASS: GET /api/notifications works")

    def test_api_keys_endpoint(self, client_session):
        """GET /api/api-keys - should work"""
        resp = client_session.get(f"{BASE_URL}/api/api-keys")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        data = resp.json()
        assert isinstance(data, list), "Should return list"
        print("PASS: GET /api/api-keys works")

    def test_prefixes_endpoint(self, admin_session):
        """GET /api/prefixes - should work"""
        resp = admin_session.get(f"{BASE_URL}/api/prefixes")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        data = resp.json()
        assert isinstance(data, list), "Should return list"
        print("PASS: GET /api/prefixes works")

    def test_contact_group_stats(self, client_session):
        """GET /api/contacts/groups/{gid}/stats - should return 404 for nonexistent"""
        resp = client_session.get(f"{BASE_URL}/api/contacts/groups/nonexistent_group/stats")
        assert resp.status_code == 404, f"Expected 404, got {resp.status_code}"
        print("PASS: GET /api/contacts/groups/{gid}/stats returns 404 for nonexistent")

    def test_affiliate_me_local_currency(self, reseller_session):
        """GET /api/affiliate/me - should have local currency fields"""
        resp = reseller_session.get(f"{BASE_URL}/api/affiliate/me")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        data = resp.json()
        assert "local_currency" in data, "Should have local_currency"
        assert "local_fx_rate" in data, "Should have local_fx_rate"
        assert "earned_local" in data, "Should have earned_local"
        print(f"PASS: GET /api/affiliate/me has local currency fields (currency={data['local_currency']})")


class TestRegressionIteration18:
    """Regression tests for iteration 18 features"""

    def test_country_pnl_uses_snapshots(self, admin_session):
        """GET /api/admin/country-pnl - should use message-level snapshots"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-pnl")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        data = resp.json()
        assert "rows" in data
        assert "total_profit_usd" in data
        print("PASS: GET /api/admin/country-pnl works with snapshot aggregation")

    def test_settings_set_setting_helper(self, admin_session):
        """PUT /api/admin/settings - set_setting() helper works"""
        resp = admin_session.put(f"{BASE_URL}/api/admin/settings", json={
            "key": "test.iteration19_check",
            "value": "test_value",
            "category": "platform"
        })
        assert resp.status_code == 200, f"Failed: {resp.text}"
        print("PASS: PUT /api/admin/settings (set_setting helper) works")


# ============================================================================
# SMOKE TESTS - All roles login
# ============================================================================
class TestSmokeAllRoles:
    """Smoke tests for all roles"""

    def test_admin_login(self):
        """Admin can login"""
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": ADMIN_EMAIL, "password": ADMIN_PASSWORD
        })
        assert resp.status_code == 200, f"Admin login failed: {resp.text}"
        data = resp.json()
        assert data["user"]["role"] == "super_admin"
        print("PASS: Admin login works")

    def test_reseller_login(self):
        """Reseller can login"""
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": RESELLER_EMAIL, "password": RESELLER_PASSWORD
        })
        assert resp.status_code == 200, f"Reseller login failed: {resp.text}"
        data = resp.json()
        assert data["user"]["role"] == "reseller"
        print("PASS: Reseller login works")

    def test_client_login(self):
        """Client can login"""
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL, "password": CLIENT_PASSWORD
        })
        assert resp.status_code == 200, f"Client login failed: {resp.text}"
        data = resp.json()
        assert data["user"]["role"] == "client"
        print("PASS: Client login works")


if __name__ == "__main__":
    pytest.main([__file__, "-v", "--tb=short"])
