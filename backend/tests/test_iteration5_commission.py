"""
Iteration 5 Tests: Reseller Commission Model + Settings Hub UI
Tests the new commission-based reseller pricing (replacing markup model)
and verifies admin commission management endpoints.
"""
import pytest
import requests
import os
import time

BASE_URL = os.environ.get('REACT_APP_BACKEND_URL', '').rstrip('/')

# Test credentials
ADMIN_EMAIL = "admin@unitxt.io"
ADMIN_PASSWORD = "Admin@2026"
RESELLER_EMAIL = "reseller@unitxt.io"
RESELLER_PASSWORD = "Reseller@2026"
CLIENT_EMAIL = "client@unitxt.io"
CLIENT_PASSWORD = "Client@2026"


@pytest.fixture(scope="module")
def admin_session():
    """Admin login session"""
    session = requests.Session()
    resp = session.post(f"{BASE_URL}/api/auth/login", json={
        "email": ADMIN_EMAIL, "password": ADMIN_PASSWORD
    })
    assert resp.status_code == 200, f"Admin login failed: {resp.text}"
    return session


@pytest.fixture(scope="module")
def reseller_session():
    """Reseller login session"""
    session = requests.Session()
    resp = session.post(f"{BASE_URL}/api/auth/login", json={
        "email": RESELLER_EMAIL, "password": RESELLER_PASSWORD
    })
    assert resp.status_code == 200, f"Reseller login failed: {resp.text}"
    return session


@pytest.fixture(scope="module")
def client_session():
    """Client login session"""
    session = requests.Session()
    resp = session.post(f"{BASE_URL}/api/auth/login", json={
        "email": CLIENT_EMAIL, "password": CLIENT_PASSWORD
    })
    assert resp.status_code == 200, f"Client login failed: {resp.text}"
    return session


@pytest.fixture(scope="module")
def reseller_id(reseller_session):
    """Get reseller's user ID"""
    resp = reseller_session.get(f"{BASE_URL}/api/auth/me")
    assert resp.status_code == 200
    return resp.json()["user"]["id"]


@pytest.fixture(scope="module")
def client_id(client_session):
    """Get client's user ID"""
    resp = client_session.get(f"{BASE_URL}/api/auth/me")
    assert resp.status_code == 200
    return resp.json()["user"]["id"]


# ============================================================
# RESELLER PRICING ENDPOINT TESTS
# ============================================================
class TestResellerPricingEndpoint:
    """Tests for GET /api/reseller/pricing (read-only commission view)"""
    
    def test_reseller_pricing_get_returns_commission_structure(self, reseller_session):
        """GET /api/reseller/pricing returns {default_commission_rate, overrides:[]}"""
        resp = reseller_session.get(f"{BASE_URL}/api/reseller/pricing")
        assert resp.status_code == 200
        data = resp.json()
        
        # Verify structure
        assert "default_commission_rate" in data, "Missing default_commission_rate"
        assert "overrides" in data, "Missing overrides array"
        assert isinstance(data["overrides"], list), "overrides should be a list"
        
        # Verify default_commission_rate is a valid float 0-1
        rate = data["default_commission_rate"]
        assert isinstance(rate, (int, float)), "default_commission_rate should be numeric"
        assert 0 <= rate <= 1, f"default_commission_rate {rate} should be 0-1"
        print(f"✓ GET /reseller/pricing returns commission structure: rate={rate}, overrides={len(data['overrides'])}")
    
    def test_reseller_pricing_post_returns_405(self, reseller_session):
        """POST /api/reseller/pricing should return 405 Method Not Allowed"""
        resp = reseller_session.post(f"{BASE_URL}/api/reseller/pricing", json={
            "country": "TZ", "channel": "sms", "commission_rate": 0.20
        })
        # FastAPI returns 405 for undefined methods on existing routes
        assert resp.status_code == 405, f"Expected 405, got {resp.status_code}: {resp.text}"
        print("✓ POST /reseller/pricing returns 405 (read-only)")
    
    def test_reseller_pricing_no_markup_language(self, reseller_session):
        """Verify no 'markup' field in response (commission model only)"""
        resp = reseller_session.get(f"{BASE_URL}/api/reseller/pricing")
        assert resp.status_code == 200
        data = resp.json()
        
        # Check no markup fields
        assert "markup" not in data, "Should not have 'markup' field"
        assert "markup_multiplier" not in data, "Should not have 'markup_multiplier' field"
        for override in data.get("overrides", []):
            assert "markup" not in override, f"Override should not have 'markup': {override}"
        print("✓ No markup language in reseller pricing response")


# ============================================================
# ADMIN COMMISSION MANAGEMENT TESTS
# ============================================================
class TestAdminCommissionEndpoints:
    """Tests for admin reseller commission management endpoints"""
    
    def test_admin_get_reseller_commissions(self, admin_session, reseller_id):
        """GET /api/admin/resellers/{id}/commissions returns reseller info + overrides"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/resellers/{reseller_id}/commissions")
        assert resp.status_code == 200
        data = resp.json()
        
        assert "reseller" in data, "Missing reseller info"
        assert "overrides" in data, "Missing overrides"
        assert data["reseller"]["id"] == reseller_id
        assert "default_commission_rate" in data["reseller"]
        print(f"✓ Admin GET commissions: reseller={data['reseller']['email']}, overrides={len(data['overrides'])}")
    
    def test_admin_set_commission_override(self, admin_session, reseller_id):
        """POST /api/admin/resellers/{id}/commissions sets country+channel commission"""
        # Set TZ/sms commission to 0.20 (20%)
        resp = admin_session.post(f"{BASE_URL}/api/admin/resellers/{reseller_id}/commissions", json={
            "country": "TZ",
            "channel": "sms",
            "commission_rate": 0.20
        })
        assert resp.status_code == 200, f"Failed to set commission: {resp.text}"
        data = resp.json()
        assert "ok" in data or "id" in data, f"Unexpected response: {data}"
        print("✓ Admin POST commission override: TZ/sms = 20%")
    
    def test_admin_set_commission_validates_rate_range(self, admin_session, reseller_id):
        """Commission rate must be 0-1 (0-100%)"""
        # Test rate > 1 should fail validation
        resp = admin_session.post(f"{BASE_URL}/api/admin/resellers/{reseller_id}/commissions", json={
            "country": "KE",
            "channel": "sms",
            "commission_rate": 1.5  # Invalid: > 1
        })
        assert resp.status_code == 422, f"Expected 422 for rate > 1, got {resp.status_code}"
        
        # Test negative rate should fail
        resp = admin_session.post(f"{BASE_URL}/api/admin/resellers/{reseller_id}/commissions", json={
            "country": "KE",
            "channel": "sms",
            "commission_rate": -0.1  # Invalid: < 0
        })
        assert resp.status_code == 422, f"Expected 422 for negative rate, got {resp.status_code}"
        print("✓ Commission rate validation: rejects values outside 0-1")
    
    def test_admin_update_default_commission(self, admin_session, reseller_id):
        """PUT /api/admin/resellers/{id}/default-commission updates user's default rate"""
        resp = admin_session.put(f"{BASE_URL}/api/admin/resellers/{reseller_id}/default-commission", json={
            "commission_rate": 0.15
        })
        assert resp.status_code == 200
        data = resp.json()
        assert data.get("ok") == True
        assert data.get("commission_rate") == 0.15
        print("✓ Admin PUT default-commission: set to 15%")
    
    def test_admin_delete_commission_override(self, admin_session, reseller_id):
        """DELETE /api/admin/resellers/{id}/commissions/{rid} removes override"""
        # First create an override to delete
        create_resp = admin_session.post(f"{BASE_URL}/api/admin/resellers/{reseller_id}/commissions", json={
            "country": "UG",
            "channel": "sms",
            "commission_rate": 0.25
        })
        assert create_resp.status_code == 200
        
        # Get the override ID
        list_resp = admin_session.get(f"{BASE_URL}/api/admin/resellers/{reseller_id}/commissions")
        overrides = list_resp.json().get("overrides", [])
        ug_override = next((o for o in overrides if o.get("country") == "UG"), None)
        
        if ug_override:
            rid = ug_override["id"]
            del_resp = admin_session.delete(f"{BASE_URL}/api/admin/resellers/{reseller_id}/commissions/{rid}")
            assert del_resp.status_code == 200
            assert del_resp.json().get("ok") == True
            print(f"✓ Admin DELETE commission override: removed UG/sms")
        else:
            print("⚠ Could not find UG override to delete (may have been cleaned up)")


# ============================================================
# SETTINGS HUB - COMMISSION DEFAULT
# ============================================================
class TestSettingsCommissionDefault:
    """Tests for pricing.reseller_commission_default setting"""
    
    def test_commission_default_setting_exists(self, admin_session):
        """Setting pricing.reseller_commission_default should exist"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/settings")
        assert resp.status_code == 200
        settings = resp.json()
        
        commission_setting = next(
            (s for s in settings if s["key"] == "pricing.reseller_commission_default"),
            None
        )
        assert commission_setting is not None, "pricing.reseller_commission_default not found"
        assert commission_setting["value"] == 0.15, f"Expected 0.15, got {commission_setting['value']}"
        print(f"✓ pricing.reseller_commission_default exists: {commission_setting['value']}")
    
    def test_commission_default_editable(self, admin_session):
        """Admin can update pricing.reseller_commission_default via settings"""
        # Update to 0.12
        resp = admin_session.put(f"{BASE_URL}/api/admin/settings", json={
            "key": "pricing.reseller_commission_default",
            "value": 0.12,
            "category": "pricing"
        })
        assert resp.status_code == 200
        
        # Verify change
        get_resp = admin_session.get(f"{BASE_URL}/api/admin/settings")
        settings = get_resp.json()
        setting = next((s for s in settings if s["key"] == "pricing.reseller_commission_default"), None)
        assert setting["value"] == 0.12
        
        # Restore to 0.15
        admin_session.put(f"{BASE_URL}/api/admin/settings", json={
            "key": "pricing.reseller_commission_default",
            "value": 0.15,
            "category": "pricing"
        })
        print("✓ pricing.reseller_commission_default is editable via settings")


# ============================================================
# END-TO-END COMMISSION FLOW TEST
# ============================================================
class TestCommissionE2E:
    """End-to-end test: client send charges base rate, reseller earns commission"""
    
    def test_commission_flow_client_pays_base_rate(self, admin_session, reseller_session, client_session, reseller_id, client_id):
        """
        E2E: Client bulk-send N msgs to TZ charges client exactly base_rate * N credits.
        Reseller wallet increases by int(round(base_rate * N * commission)).
        """
        # Step 1: Set TZ/sms commission to 0.20 (20%) for demo reseller
        admin_session.post(f"{BASE_URL}/api/admin/resellers/{reseller_id}/commissions", json={
            "country": "TZ",
            "channel": "sms",
            "commission_rate": 0.20
        })
        
        # Step 2: Get client's initial wallet balance
        client_me = client_session.get(f"{BASE_URL}/api/auth/me").json()
        client_initial_balance = client_me["wallet"]["balance"]
        print(f"Client initial balance: {client_initial_balance}")
        
        # Step 3: Get reseller's initial wallet balance
        reseller_me = reseller_session.get(f"{BASE_URL}/api/auth/me").json()
        reseller_initial_balance = reseller_me["wallet"]["balance"]
        print(f"Reseller initial balance: {reseller_initial_balance}")
        
        # Step 4: Client sends N=10 messages to TZ
        N = 10
        recipients = [{"phone": f"+25571200010{i}"} for i in range(N)]
        
        send_resp = client_session.post(f"{BASE_URL}/api/messaging/bulk-send", json={
            "channel": "sms",
            "sender_id": "SUNRISE",
            "name": "Commission Test Campaign",
            "recipients": recipients,
            "template": "Hi"
        })
        assert send_resp.status_code == 200, f"Bulk send failed: {send_resp.text}"
        campaign_id = send_resp.json()["campaign_id"]
        print(f"Campaign created: {campaign_id}")
        
        # Step 5: Wait for campaign to complete
        time.sleep(3)
        
        # Step 6: Verify client was charged base_rate * N (TZ = 1 credit/msg)
        client_me_after = client_session.get(f"{BASE_URL}/api/auth/me").json()
        client_final_balance = client_me_after["wallet"]["balance"]
        client_delta = client_initial_balance - client_final_balance
        
        # TZ base rate = 1 credit per SMS
        expected_client_charge = N * 1  # 10 credits
        assert client_delta == expected_client_charge, \
            f"Client should be charged {expected_client_charge}, but delta was {client_delta}"
        print(f"✓ Client charged exactly {client_delta} credits (base rate * N)")
        
        # Step 7: Verify reseller earned commission
        reseller_me_after = reseller_session.get(f"{BASE_URL}/api/auth/me").json()
        reseller_final_balance = reseller_me_after["wallet"]["balance"]
        reseller_delta = reseller_final_balance - reseller_initial_balance
        
        # Expected: int(round(N * 1 * 0.20)) = int(round(10 * 0.20)) = 2
        expected_commission = int(round(N * 1 * 0.20))
        assert reseller_delta == expected_commission, \
            f"Reseller should earn {expected_commission}, but delta was {reseller_delta}"
        print(f"✓ Reseller earned {reseller_delta} credits commission (20% of {N})")


# ============================================================
# REGRESSION TESTS - CORE ENDPOINTS
# ============================================================
class TestRegressionCoreEndpoints:
    """Verify existing 134 endpoints still work (sample of critical routes)"""
    
    def test_auth_me(self, admin_session):
        """GET /api/auth/me works"""
        resp = admin_session.get(f"{BASE_URL}/api/auth/me")
        assert resp.status_code == 200
        assert "user" in resp.json()
        print("✓ /api/auth/me works")
    
    def test_wallet_me(self, client_session):
        """GET /api/wallet/me works"""
        resp = client_session.get(f"{BASE_URL}/api/wallet/me")
        assert resp.status_code == 200
        assert "balance" in resp.json()
        print("✓ /api/wallet/me works")
    
    def test_campaigns_list(self, client_session):
        """GET /api/messaging/campaigns works"""
        resp = client_session.get(f"{BASE_URL}/api/messaging/campaigns")
        assert resp.status_code == 200
        assert isinstance(resp.json(), list)
        print("✓ /api/messaging/campaigns works")
    
    def test_admin_overview(self, admin_session):
        """GET /api/admin/overview works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/overview")
        assert resp.status_code == 200
        assert "kpi" in resp.json()
        print("✓ /api/admin/overview works")
    
    def test_admin_settings_get(self, admin_session):
        """GET /api/admin/settings works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/settings")
        assert resp.status_code == 200
        assert isinstance(resp.json(), list)
        print("✓ /api/admin/settings works")
    
    def test_reseller_clients(self, reseller_session):
        """GET /api/reseller/clients works"""
        resp = reseller_session.get(f"{BASE_URL}/api/reseller/clients")
        assert resp.status_code == 200
        assert isinstance(resp.json(), list)
        print("✓ /api/reseller/clients works")
    
    def test_reseller_earnings(self, reseller_session):
        """GET /api/reseller/earnings works"""
        resp = reseller_session.get(f"{BASE_URL}/api/reseller/earnings")
        assert resp.status_code == 200
        assert "commission_rate" in resp.json()
        print("✓ /api/reseller/earnings works")


if __name__ == "__main__":
    pytest.main([__file__, "-v", "--tb=short"])
