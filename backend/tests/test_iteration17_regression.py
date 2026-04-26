"""
Iteration 17 Regression Tests - Phase-2 Routes Split + New Features
=====================================================================
Tests:
1. Phase-2 extracted routes: /api/notifications, /api/api-keys, /api/prefixes
2. Per-Contact-Group stats: GET /api/contacts/groups/{gid}/stats
3. Affiliate local-currency endpoints (GET /api/affiliate/me returns local_currency fields)
4. Smoke tests for all 3 roles (admin, reseller, client)
5. Regression: top-ups, banks, affiliate program config, country economics
"""
import os
import pytest
import requests

BASE_URL = os.environ.get("REACT_APP_BACKEND_URL", "").rstrip("/")

# Test credentials from /app/memory/test_credentials.md
ADMIN_EMAIL = "admin@unitxt.io"
ADMIN_PASS = "Admin@2026"
RESELLER_EMAIL = "reseller@unitxt.io"
RESELLER_PASS = "Reseller@2026"
CLIENT_EMAIL = "client@unitxt.io"
CLIENT_PASS = "Client@2026"


@pytest.fixture(scope="module")
def admin_session():
    """Login as super_admin and return session with cookies."""
    s = requests.Session()
    r = s.post(f"{BASE_URL}/api/auth/login", json={"email": ADMIN_EMAIL, "password": ADMIN_PASS})
    assert r.status_code == 200, f"Admin login failed: {r.text}"
    return s


@pytest.fixture(scope="module")
def reseller_session():
    """Login as reseller and return session with cookies."""
    s = requests.Session()
    r = s.post(f"{BASE_URL}/api/auth/login", json={"email": RESELLER_EMAIL, "password": RESELLER_PASS})
    assert r.status_code == 200, f"Reseller login failed: {r.text}"
    return s


@pytest.fixture(scope="module")
def client_session():
    """Login as client and return session with cookies."""
    s = requests.Session()
    r = s.post(f"{BASE_URL}/api/auth/login", json={"email": CLIENT_EMAIL, "password": CLIENT_PASS})
    assert r.status_code == 200, f"Client login failed: {r.text}"
    return s


# ============================================================
# PHASE-2 ROUTES SPLIT REGRESSION TESTS
# ============================================================

class TestNotificationsRoute:
    """Test /api/notifications endpoints (extracted to routes/notifications.py)"""
    
    def test_get_notifications(self, client_session):
        """GET /api/notifications returns list with unread count"""
        r = client_session.get(f"{BASE_URL}/api/notifications")
        assert r.status_code == 200, f"GET /api/notifications failed: {r.text}"
        data = r.json()
        assert "items" in data, "Response missing 'items' key"
        assert "unread" in data, "Response missing 'unread' key"
        assert isinstance(data["items"], list), "'items' should be a list"
        assert isinstance(data["unread"], int), "'unread' should be an integer"
        print(f"PASS: GET /api/notifications - {len(data['items'])} items, {data['unread']} unread")
    
    def test_mark_all_read(self, client_session):
        """POST /api/notifications/read-all marks all as read"""
        r = client_session.post(f"{BASE_URL}/api/notifications/read-all")
        assert r.status_code == 200, f"POST /api/notifications/read-all failed: {r.text}"
        data = r.json()
        assert data.get("ok") is True, "Expected {ok: true}"
        print("PASS: POST /api/notifications/read-all")
    
    def test_notifications_requires_auth(self):
        """GET /api/notifications without auth returns 401"""
        r = requests.get(f"{BASE_URL}/api/notifications")
        assert r.status_code == 401, f"Expected 401, got {r.status_code}"
        print("PASS: GET /api/notifications requires auth (401)")


class TestApiKeysRoute:
    """Test /api/api-keys endpoints (extracted to routes/api_keys.py)"""
    
    def test_list_api_keys(self, client_session):
        """GET /api/api-keys returns list of user's API keys"""
        r = client_session.get(f"{BASE_URL}/api/api-keys")
        assert r.status_code == 200, f"GET /api/api-keys failed: {r.text}"
        data = r.json()
        assert isinstance(data, list), "Response should be a list"
        print(f"PASS: GET /api/api-keys - {len(data)} keys")
    
    def test_create_and_delete_api_key(self, client_session):
        """POST /api/api-keys creates key, DELETE removes it"""
        # Create
        r = client_session.post(f"{BASE_URL}/api/api-keys", json={"name": "TEST_iter17_key"})
        assert r.status_code == 200, f"POST /api/api-keys failed: {r.text}"
        data = r.json()
        assert "id" in data, "Response missing 'id'"
        assert "key" in data, "Response missing 'key'"
        assert data["key"].startswith("uxk_"), f"Key should start with 'uxk_', got {data['key'][:10]}"
        key_id = data["id"]
        print(f"PASS: POST /api/api-keys - created key {key_id}")
        
        # Delete
        r = client_session.delete(f"{BASE_URL}/api/api-keys/{key_id}")
        assert r.status_code == 200, f"DELETE /api/api-keys/{key_id} failed: {r.text}"
        assert r.json().get("ok") is True
        print(f"PASS: DELETE /api/api-keys/{key_id}")
    
    def test_api_keys_requires_auth(self):
        """GET /api/api-keys without auth returns 401"""
        r = requests.get(f"{BASE_URL}/api/api-keys")
        assert r.status_code == 401, f"Expected 401, got {r.status_code}"
        print("PASS: GET /api/api-keys requires auth (401)")


class TestPrefixesRoute:
    """Test /api/prefixes endpoints (extracted to routes/prefixes.py)"""
    
    def test_list_prefixes(self, client_session):
        """GET /api/prefixes returns list of mobile prefixes"""
        r = client_session.get(f"{BASE_URL}/api/prefixes")
        assert r.status_code == 200, f"GET /api/prefixes failed: {r.text}"
        data = r.json()
        assert isinstance(data, list), "Response should be a list"
        assert len(data) > 0, "Should have seeded prefixes"
        # Check structure
        if data:
            p = data[0]
            assert "country" in p, "Prefix missing 'country'"
            assert "operator" in p, "Prefix missing 'operator'"
            assert "prefix" in p, "Prefix missing 'prefix'"
        print(f"PASS: GET /api/prefixes - {len(data)} prefixes")
    
    def test_list_prefixes_by_country(self, client_session):
        """GET /api/prefixes?country=TZ filters by country"""
        r = client_session.get(f"{BASE_URL}/api/prefixes?country=TZ")
        assert r.status_code == 200, f"GET /api/prefixes?country=TZ failed: {r.text}"
        data = r.json()
        assert isinstance(data, list), "Response should be a list"
        for p in data:
            assert p["country"] == "TZ", f"Expected country=TZ, got {p['country']}"
        print(f"PASS: GET /api/prefixes?country=TZ - {len(data)} TZ prefixes")
    
    def test_prefix_lookup(self, client_session):
        """GET /api/prefixes/lookup?phone=+255712345678 returns operator match"""
        r = client_session.get(f"{BASE_URL}/api/prefixes/lookup?phone=+255712345678")
        assert r.status_code == 200, f"GET /api/prefixes/lookup failed: {r.text}"
        data = r.json()
        assert "match" in data, "Response missing 'match'"
        if data["match"]:
            assert data["match"]["country"] == "TZ", "Expected TZ country"
        print(f"PASS: GET /api/prefixes/lookup - match: {data.get('match')}")
    
    def test_create_prefix_requires_admin(self, client_session):
        """POST /api/prefixes requires super_admin role"""
        r = client_session.post(f"{BASE_URL}/api/prefixes", json={
            "country": "XX", "operator": "Test", "prefix": "+999"
        })
        assert r.status_code == 403, f"Expected 403 for non-admin, got {r.status_code}"
        print("PASS: POST /api/prefixes requires admin (403)")
    
    def test_admin_can_create_prefix(self, admin_session):
        """Admin can create and delete prefix"""
        # Create
        r = admin_session.post(f"{BASE_URL}/api/prefixes", json={
            "country": "XX", "operator": "TEST_iter17", "prefix": "+99917"
        })
        assert r.status_code == 200, f"POST /api/prefixes failed: {r.text}"
        data = r.json()
        assert "id" in data
        pid = data["id"]
        print(f"PASS: Admin POST /api/prefixes - created {pid}")
        
        # Delete
        r = admin_session.delete(f"{BASE_URL}/api/prefixes/{pid}")
        assert r.status_code == 200, f"DELETE /api/prefixes/{pid} failed: {r.text}"
        print(f"PASS: Admin DELETE /api/prefixes/{pid}")


# ============================================================
# PER-CONTACT-GROUP STATS
# ============================================================

class TestContactGroupStats:
    """Test GET /api/contacts/groups/{gid}/stats endpoint"""
    
    def test_group_stats_endpoint(self, client_session):
        """Create group, get stats, verify structure"""
        # First create a group
        r = client_session.post(f"{BASE_URL}/api/contacts/groups", json={
            "name": "TEST_iter17_stats_group", "description": "For stats testing"
        })
        assert r.status_code == 200, f"POST /api/contacts/groups failed: {r.text}"
        gid = r.json()["id"]
        print(f"Created test group: {gid}")
        
        # Get stats
        r = client_session.get(f"{BASE_URL}/api/contacts/groups/{gid}/stats")
        assert r.status_code == 200, f"GET /api/contacts/groups/{gid}/stats failed: {r.text}"
        data = r.json()
        
        # Verify structure
        assert data["group_id"] == gid, "group_id mismatch"
        assert "group_name" in data, "Missing group_name"
        assert "contact_count" in data, "Missing contact_count"
        assert "lifetime_sends" in data, "Missing lifetime_sends"
        assert "delivered" in data, "Missing delivered"
        assert "failed" in data, "Missing failed"
        assert "delivery_rate" in data, "Missing delivery_rate"
        assert "top_failure_reasons" in data, "Missing top_failure_reasons"
        assert "last_send_at" in data, "Missing last_send_at"
        
        print(f"PASS: GET /api/contacts/groups/{gid}/stats - structure verified")
        print(f"  contact_count={data['contact_count']}, lifetime_sends={data['lifetime_sends']}, delivery_rate={data['delivery_rate']}%")
        
        # Cleanup
        r = client_session.delete(f"{BASE_URL}/api/contacts/groups/{gid}")
        assert r.status_code == 200
        print(f"Cleaned up test group {gid}")
    
    def test_group_stats_404_for_nonexistent(self, client_session):
        """GET /api/contacts/groups/{bad_id}/stats returns 404"""
        r = client_session.get(f"{BASE_URL}/api/contacts/groups/nonexistent-id-12345/stats")
        assert r.status_code == 404, f"Expected 404, got {r.status_code}"
        print("PASS: GET /api/contacts/groups/{bad_id}/stats returns 404")


# ============================================================
# AFFILIATE LOCAL-CURRENCY DISPLAY
# ============================================================

class TestAffiliateLocalCurrency:
    """Test that affiliate endpoints return local-currency fields"""
    
    def test_affiliate_me_has_local_currency_fields(self, reseller_session):
        """GET /api/affiliate/me returns local_currency, local_fx_rate, and local amounts"""
        r = reseller_session.get(f"{BASE_URL}/api/affiliate/me")
        assert r.status_code == 200, f"GET /api/affiliate/me failed: {r.text}"
        data = r.json()
        
        # Check for local currency fields
        assert "local_currency" in data, "Missing local_currency field"
        assert "local_fx_rate" in data, "Missing local_fx_rate field"
        assert "earned_local" in data, "Missing earned_local field"
        assert "requested_local" in data, "Missing requested_local field"
        assert "paid_local" in data, "Missing paid_local field"
        
        print(f"PASS: GET /api/affiliate/me has local currency fields")
        print(f"  local_currency={data.get('local_currency')}, local_fx_rate={data.get('local_fx_rate')}")
        print(f"  earned_usd={data.get('earned_usd')}, earned_local={data.get('earned_local')}")


# ============================================================
# SMOKE TESTS - ALL 3 ROLES
# ============================================================

class TestSmokeAllRoles:
    """Smoke test: login as all 3 roles and verify dashboards render"""
    
    def test_admin_login_and_dashboard(self, admin_session):
        """Admin can login and access /api/auth/me"""
        r = admin_session.get(f"{BASE_URL}/api/auth/me")
        assert r.status_code == 200, f"Admin /api/auth/me failed: {r.text}"
        data = r.json()
        assert data["user"]["role"] == "super_admin", f"Expected super_admin, got {data['user']['role']}"
        print(f"PASS: Admin login - role={data['user']['role']}, email={data['user']['email']}")
    
    def test_reseller_login_and_dashboard(self, reseller_session):
        """Reseller can login and access affiliate endpoints"""
        r = reseller_session.get(f"{BASE_URL}/api/auth/me")
        assert r.status_code == 200, f"Reseller /api/auth/me failed: {r.text}"
        data = r.json()
        assert data["user"]["role"] == "reseller", f"Expected reseller, got {data['user']['role']}"
        
        # Check affiliate dashboard data
        r = reseller_session.get(f"{BASE_URL}/api/affiliate/me")
        assert r.status_code == 200, f"Reseller /api/affiliate/me failed: {r.text}"
        print(f"PASS: Reseller login - role={data['user']['role']}, email={data['user']['email']}")
    
    def test_client_login_and_dashboard(self, client_session):
        """Client can login and access wallet/messaging endpoints"""
        r = client_session.get(f"{BASE_URL}/api/auth/me")
        assert r.status_code == 200, f"Client /api/auth/me failed: {r.text}"
        data = r.json()
        assert data["user"]["role"] == "client", f"Expected client, got {data['user']['role']}"
        
        # Check wallet
        assert "wallet" in data, "Missing wallet in /api/auth/me response"
        print(f"PASS: Client login - role={data['user']['role']}, balance={data['wallet'].get('balance')}")


# ============================================================
# REGRESSION TESTS - EXISTING FLOWS
# ============================================================

class TestRegressionTopups:
    """Regression: top-up flows still work"""
    
    def test_wallet_topup(self, client_session):
        """POST /api/wallet/topup works"""
        r = client_session.post(f"{BASE_URL}/api/wallet/topup", json={
            "amount": 1.0, "method": "manual", "note": "TEST_iter17"
        })
        assert r.status_code == 200, f"POST /api/wallet/topup failed: {r.text}"
        data = r.json()
        assert data.get("ok") is True
        assert "tx" in data
        print(f"PASS: POST /api/wallet/topup - tx_id={data['tx']['id']}")


class TestRegressionBanks:
    """Regression: bank account endpoints still work"""
    
    def test_list_bank_accounts(self, client_session):
        """GET /api/banks returns list"""
        r = client_session.get(f"{BASE_URL}/api/banks")
        assert r.status_code == 200, f"GET /api/banks failed: {r.text}"
        data = r.json()
        assert isinstance(data, list), "Response should be a list"
        print(f"PASS: GET /api/banks - {len(data)} accounts")
    
    def test_admin_bank_accounts(self, admin_session):
        """GET /api/admin/banks returns list"""
        r = admin_session.get(f"{BASE_URL}/api/admin/banks")
        assert r.status_code == 200, f"GET /api/admin/banks failed: {r.text}"
        data = r.json()
        assert isinstance(data, list), "Response should be a list"
        print(f"PASS: GET /api/admin/banks - {len(data)} accounts")


class TestRegressionAffiliateConfig:
    """Regression: affiliate program config endpoints still work"""
    
    def test_admin_affiliate_overview(self, admin_session):
        """GET /api/admin/affiliate/overview returns config + stats"""
        r = admin_session.get(f"{BASE_URL}/api/admin/affiliate/overview")
        assert r.status_code == 200, f"GET /api/admin/affiliate/overview failed: {r.text}"
        data = r.json()
        assert "config" in data, "Missing config"
        assert "affiliates_count" in data, "Missing affiliates_count"
        print(f"PASS: GET /api/admin/affiliate/overview - {data.get('affiliates_count')} affiliates")


class TestRegressionCountryEconomics:
    """Regression: country economics editor still works"""
    
    def test_get_country_economics(self, admin_session):
        """GET /api/admin/country-economics/TZ returns economics data"""
        r = admin_session.get(f"{BASE_URL}/api/admin/country-economics/TZ")
        assert r.status_code == 200, f"GET /api/admin/country-economics/TZ failed: {r.text}"
        data = r.json()
        assert data.get("code") == "TZ", "Expected code=TZ"
        assert "currency" in data, "Missing currency"
        assert "fx_rate_to_usd" in data, "Missing fx_rate_to_usd"
        print(f"PASS: GET /api/admin/country-economics/TZ - currency={data.get('currency')}, fx={data.get('fx_rate_to_usd')}")


class TestRegressionContacts:
    """Regression: contacts CRUD still works"""
    
    def test_contacts_crud(self, client_session):
        """Create, list, delete contact"""
        # Create
        r = client_session.post(f"{BASE_URL}/api/contacts", json={
            "phone": "+255700000017", "name": "TEST_iter17_contact"
        })
        assert r.status_code == 200, f"POST /api/contacts failed: {r.text}"
        cid = r.json()["id"]
        print(f"Created contact: {cid}")
        
        # List
        r = client_session.get(f"{BASE_URL}/api/contacts")
        assert r.status_code == 200
        
        # Delete
        r = client_session.delete(f"{BASE_URL}/api/contacts/{cid}")
        assert r.status_code == 200
        print(f"PASS: Contacts CRUD - created and deleted {cid}")


class TestRegressionSenderIds:
    """Regression: sender ID endpoints still work"""
    
    def test_list_sender_ids(self, client_session):
        """GET /api/sender-ids returns list"""
        r = client_session.get(f"{BASE_URL}/api/sender-ids")
        assert r.status_code == 200, f"GET /api/sender-ids failed: {r.text}"
        data = r.json()
        assert isinstance(data, list), "Response should be a list"
        # Client should have SUNRISE sender ID
        approved = [s for s in data if s.get("status") == "approved"]
        print(f"PASS: GET /api/sender-ids - {len(data)} total, {len(approved)} approved")


class TestRegressionTemplates:
    """Regression: templates CRUD still works"""
    
    def test_templates_crud(self, client_session):
        """Create, list, delete template"""
        # Create
        r = client_session.post(f"{BASE_URL}/api/templates", json={
            "name": "TEST_iter17_template", "body": "Hello {name}!"
        })
        assert r.status_code == 200, f"POST /api/templates failed: {r.text}"
        tid = r.json()["id"]
        
        # List
        r = client_session.get(f"{BASE_URL}/api/templates")
        assert r.status_code == 200
        
        # Delete
        r = client_session.delete(f"{BASE_URL}/api/templates/{tid}")
        assert r.status_code == 200
        print(f"PASS: Templates CRUD - created and deleted {tid}")


if __name__ == "__main__":
    pytest.main([__file__, "-v", "--tb=short"])
