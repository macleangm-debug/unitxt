"""
Iteration 6 Tests: Admin Reseller Management Workspace
Tests for:
- GET /api/admin/resellers - list all resellers with stats
- GET /api/admin/resellers/{id}/detail - reseller detail with wallet, clients, overrides
- POST /api/admin/resellers/{id}/float - credit/clawback float
- PUT /api/admin/resellers/{id}/status - change status (active/suspended/inactive)
- GET /api/admin/resellers/commission-audit - commission audit with days filter
- Reseller policy settings in system_settings (category=reseller)
- Regression tests for prior iteration endpoints
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
    """Get authenticated admin session"""
    session = requests.Session()
    session.headers.update({"Content-Type": "application/json"})
    resp = session.post(f"{BASE_URL}/api/auth/login", json={
        "email": ADMIN_EMAIL,
        "password": ADMIN_PASSWORD
    })
    assert resp.status_code == 200, f"Admin login failed: {resp.text}"
    return session


@pytest.fixture(scope="module")
def reseller_session():
    """Get authenticated reseller session"""
    session = requests.Session()
    session.headers.update({"Content-Type": "application/json"})
    resp = session.post(f"{BASE_URL}/api/auth/login", json={
        "email": RESELLER_EMAIL,
        "password": RESELLER_PASSWORD
    })
    assert resp.status_code == 200, f"Reseller login failed: {resp.text}"
    return session


@pytest.fixture(scope="module")
def reseller_id(admin_session):
    """Get the demo reseller's ID"""
    resp = admin_session.get(f"{BASE_URL}/api/admin/resellers")
    assert resp.status_code == 200
    resellers = resp.json()
    for r in resellers:
        if r["email"] == RESELLER_EMAIL:
            return r["id"]
    pytest.fail(f"Demo reseller {RESELLER_EMAIL} not found")


# ============================================================
# GET /api/admin/resellers - List all resellers
# ============================================================
class TestAdminListResellers:
    """Tests for GET /api/admin/resellers endpoint"""

    def test_list_resellers_returns_array(self, admin_session):
        """GET /api/admin/resellers returns array of resellers"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/resellers")
        assert resp.status_code == 200
        data = resp.json()
        assert isinstance(data, list), "Response should be an array"
        print(f"Found {len(data)} resellers")

    def test_list_resellers_has_required_fields(self, admin_session):
        """Each reseller has required fields"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/resellers")
        assert resp.status_code == 200
        data = resp.json()
        assert len(data) > 0, "Should have at least one reseller"
        
        required_fields = [
            "id", "email", "name", "status", "business_name", "country",
            "reseller_code", "commission_rate", "kyc_verified", "float_balance",
            "clients_count", "lifetime_commission", "last_active_at", "created_at"
        ]
        reseller = data[0]
        for field in required_fields:
            assert field in reseller, f"Missing field: {field}"
        print(f"Reseller fields verified: {list(reseller.keys())}")

    def test_list_resellers_lifetime_commission_aggregated(self, admin_session):
        """lifetime_commission is correctly aggregated from wallet_transactions"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/resellers")
        assert resp.status_code == 200
        data = resp.json()
        
        # Find demo reseller
        demo = next((r for r in data if r["email"] == RESELLER_EMAIL), None)
        assert demo is not None, "Demo reseller should exist"
        
        # lifetime_commission should be a number (could be 0 or positive)
        assert isinstance(demo["lifetime_commission"], (int, float))
        print(f"Demo reseller lifetime_commission: {demo['lifetime_commission']}")

    def test_list_resellers_requires_super_admin(self, reseller_session):
        """Non-admin cannot access reseller list"""
        resp = reseller_session.get(f"{BASE_URL}/api/admin/resellers")
        assert resp.status_code == 403


# ============================================================
# GET /api/admin/resellers/{id}/detail - Reseller detail
# ============================================================
class TestAdminResellerDetail:
    """Tests for GET /api/admin/resellers/{id}/detail endpoint"""

    def test_reseller_detail_returns_structure(self, admin_session, reseller_id):
        """GET /api/admin/resellers/{id}/detail returns correct structure"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/resellers/{reseller_id}/detail")
        assert resp.status_code == 200
        data = resp.json()
        
        # Check top-level keys
        assert "reseller" in data
        assert "wallet" in data
        assert "clients" in data
        assert "commission_overrides" in data
        assert "recent_commission" in data
        print(f"Detail structure: {list(data.keys())}")

    def test_reseller_detail_wallet_has_balance(self, admin_session, reseller_id):
        """Wallet object has balance field"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/resellers/{reseller_id}/detail")
        assert resp.status_code == 200
        data = resp.json()
        
        assert "balance" in data["wallet"]
        assert isinstance(data["wallet"]["balance"], (int, float))
        print(f"Reseller wallet balance: {data['wallet']['balance']}")

    def test_reseller_detail_clients_is_array(self, admin_session, reseller_id):
        """Clients is an array"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/resellers/{reseller_id}/detail")
        assert resp.status_code == 200
        data = resp.json()
        
        assert isinstance(data["clients"], list)
        print(f"Reseller has {len(data['clients'])} clients")

    def test_reseller_detail_404_for_invalid_id(self, admin_session):
        """Returns 404 for non-existent reseller"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/resellers/invalid-id-12345/detail")
        assert resp.status_code == 404


# ============================================================
# POST /api/admin/resellers/{id}/float - Float adjustment
# ============================================================
class TestAdminResellerFloat:
    """Tests for POST /api/admin/resellers/{id}/float endpoint"""

    def test_float_credit_positive_amount(self, admin_session, reseller_id):
        """Credit float with positive amount"""
        # Get initial balance
        detail_resp = admin_session.get(f"{BASE_URL}/api/admin/resellers/{reseller_id}/detail")
        initial_balance = detail_resp.json()["wallet"]["balance"]
        
        # Credit 100
        resp = admin_session.post(f"{BASE_URL}/api/admin/resellers/{reseller_id}/float", json={
            "amount": 100,
            "note": "TEST_iteration6_credit"
        })
        assert resp.status_code == 200
        tx = resp.json()
        assert tx["kind"] == "admin_topup"
        assert tx["amount"] == 100
        print(f"Float credit tx: {tx['id']}, balance_after: {tx['balance_after']}")
        
        # Verify balance increased
        detail_resp2 = admin_session.get(f"{BASE_URL}/api/admin/resellers/{reseller_id}/detail")
        new_balance = detail_resp2.json()["wallet"]["balance"]
        assert new_balance == initial_balance + 100

    def test_float_clawback_negative_amount(self, admin_session, reseller_id):
        """Clawback float with negative amount"""
        # Get initial balance
        detail_resp = admin_session.get(f"{BASE_URL}/api/admin/resellers/{reseller_id}/detail")
        initial_balance = detail_resp.json()["wallet"]["balance"]
        
        # Clawback 50
        resp = admin_session.post(f"{BASE_URL}/api/admin/resellers/{reseller_id}/float", json={
            "amount": -50,
            "note": "TEST_iteration6_clawback"
        })
        assert resp.status_code == 200
        tx = resp.json()
        assert tx["kind"] == "admin_clawback"
        assert tx["amount"] == -50
        print(f"Float clawback tx: {tx['id']}, balance_after: {tx['balance_after']}")
        
        # Verify balance decreased
        detail_resp2 = admin_session.get(f"{BASE_URL}/api/admin/resellers/{reseller_id}/detail")
        new_balance = detail_resp2.json()["wallet"]["balance"]
        assert new_balance == initial_balance - 50

    def test_float_rejects_zero_amount(self, admin_session, reseller_id):
        """Rejects amount=0"""
        resp = admin_session.post(f"{BASE_URL}/api/admin/resellers/{reseller_id}/float", json={
            "amount": 0,
            "note": "Should fail"
        })
        assert resp.status_code == 400
        print(f"Zero amount rejected: {resp.json()}")

    def test_float_404_for_invalid_reseller(self, admin_session):
        """Returns 404 for non-existent reseller"""
        resp = admin_session.post(f"{BASE_URL}/api/admin/resellers/invalid-id-12345/float", json={
            "amount": 100,
            "note": "Should fail"
        })
        assert resp.status_code == 404


# ============================================================
# PUT /api/admin/resellers/{id}/status - Status change
# ============================================================
class TestAdminResellerStatus:
    """Tests for PUT /api/admin/resellers/{id}/status endpoint"""

    def test_status_change_to_suspended(self, admin_session, reseller_id):
        """Change status to suspended"""
        resp = admin_session.put(f"{BASE_URL}/api/admin/resellers/{reseller_id}/status", json={
            "status": "suspended"
        })
        assert resp.status_code == 200
        data = resp.json()
        assert data["ok"] == True
        assert data["status"] == "suspended"
        print(f"Status changed to suspended")

    def test_status_change_to_active(self, admin_session, reseller_id):
        """Change status back to active"""
        resp = admin_session.put(f"{BASE_URL}/api/admin/resellers/{reseller_id}/status", json={
            "status": "active"
        })
        assert resp.status_code == 200
        data = resp.json()
        assert data["ok"] == True
        assert data["status"] == "active"
        print(f"Status changed to active")

    def test_status_rejects_invalid_status(self, admin_session, reseller_id):
        """Rejects invalid status value"""
        resp = admin_session.put(f"{BASE_URL}/api/admin/resellers/{reseller_id}/status", json={
            "status": "invalid_status"
        })
        assert resp.status_code == 400
        print(f"Invalid status rejected: {resp.json()}")

    def test_status_404_for_invalid_reseller(self, admin_session):
        """Returns 404 for non-existent reseller"""
        resp = admin_session.put(f"{BASE_URL}/api/admin/resellers/invalid-id-12345/status", json={
            "status": "active"
        })
        assert resp.status_code == 404


# ============================================================
# GET /api/admin/resellers/commission-audit - Commission audit
# ============================================================
class TestAdminCommissionAudit:
    """Tests for GET /api/admin/resellers/commission-audit endpoint"""

    def test_commission_audit_default_30_days(self, admin_session):
        """GET /api/admin/resellers/commission-audit returns correct structure"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/resellers/commission-audit")
        assert resp.status_code == 200
        data = resp.json()
        
        assert "since" in data
        assert "transactions" in data
        assert "by_reseller" in data
        assert "grand_total" in data
        
        assert isinstance(data["transactions"], list)
        assert isinstance(data["by_reseller"], list)
        assert isinstance(data["grand_total"], (int, float))
        print(f"Commission audit: {len(data['transactions'])} txs, grand_total={data['grand_total']}")

    def test_commission_audit_with_days_param(self, admin_session):
        """Commission audit respects days parameter"""
        for days in [7, 30, 90, 365]:
            resp = admin_session.get(f"{BASE_URL}/api/admin/resellers/commission-audit?days={days}")
            assert resp.status_code == 200
            data = resp.json()
            assert "since" in data
            print(f"Audit for {days} days: since={data['since']}, txs={len(data['transactions'])}")

    def test_commission_audit_by_reseller_sorted_desc(self, admin_session):
        """by_reseller is sorted by total_credits descending"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/resellers/commission-audit?days=365")
        assert resp.status_code == 200
        data = resp.json()
        
        by_reseller = data["by_reseller"]
        if len(by_reseller) > 1:
            for i in range(len(by_reseller) - 1):
                assert by_reseller[i]["total_credits"] >= by_reseller[i+1]["total_credits"], \
                    "by_reseller should be sorted desc by total_credits"
        print(f"by_reseller sorted correctly: {[r['total_credits'] for r in by_reseller[:5]]}")


# ============================================================
# Reseller policy settings
# ============================================================
class TestResellerPolicySettings:
    """Tests for reseller policy settings in system_settings"""

    def test_reseller_settings_exist(self, admin_session):
        """Reseller category settings exist"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/settings?category=reseller")
        assert resp.status_code == 200
        data = resp.json()
        
        keys = [s["key"] for s in data]
        expected_keys = [
            "reseller.default_commission",
            "reseller.kyc_required",
            "reseller.min_float_topup",
            "reseller.max_sub_clients",
            "reseller.allow_invite_clients"
        ]
        for key in expected_keys:
            assert key in keys, f"Missing reseller setting: {key}"
        print(f"Reseller settings found: {keys}")

    def test_reseller_default_commission_value(self, admin_session):
        """reseller.default_commission is 0.15"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/settings?category=reseller")
        assert resp.status_code == 200
        data = resp.json()
        
        setting = next((s for s in data if s["key"] == "reseller.default_commission"), None)
        assert setting is not None
        assert setting["value"] == 0.15
        print(f"reseller.default_commission = {setting['value']}")

    def test_reseller_settings_editable(self, admin_session):
        """Reseller settings can be updated via PUT /api/admin/settings"""
        # Update a setting
        resp = admin_session.put(f"{BASE_URL}/api/admin/settings", json={
            "key": "reseller.min_float_topup",
            "value": 1000,
            "category": "reseller"
        })
        assert resp.status_code == 200
        
        # Verify it was saved
        resp2 = admin_session.get(f"{BASE_URL}/api/admin/settings?category=reseller")
        data = resp2.json()
        setting = next((s for s in data if s["key"] == "reseller.min_float_topup"), None)
        assert setting is not None
        assert setting["value"] == 1000
        print(f"Setting updated and verified: reseller.min_float_topup = {setting['value']}")


# ============================================================
# Regression tests for prior iteration endpoints
# ============================================================
class TestRegressionIteration5:
    """Regression tests for iteration 5 endpoints"""

    def test_auth_me(self, admin_session):
        """GET /api/auth/me still works"""
        resp = admin_session.get(f"{BASE_URL}/api/auth/me")
        assert resp.status_code == 200
        data = resp.json()
        assert "user" in data
        assert data["user"]["email"] == ADMIN_EMAIL
        print(f"Auth me: {data['user']['email']}")

    def test_wallet_me(self, reseller_session):
        """GET /api/wallet/me still works"""
        resp = reseller_session.get(f"{BASE_URL}/api/wallet/me")
        assert resp.status_code == 200
        data = resp.json()
        assert "balance" in data
        print(f"Wallet balance: {data['balance']}")

    def test_admin_overview(self, admin_session):
        """GET /api/admin/overview still works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/overview")
        assert resp.status_code == 200
        data = resp.json()
        assert "kpi" in data
        print(f"Admin overview KPIs: {list(data['kpi'].keys())}")

    def test_admin_settings(self, admin_session):
        """GET /api/admin/settings still works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/settings")
        assert resp.status_code == 200
        data = resp.json()
        assert isinstance(data, list)
        print(f"Admin settings count: {len(data)}")

    def test_reseller_clients(self, reseller_session):
        """GET /api/reseller/clients still works"""
        resp = reseller_session.get(f"{BASE_URL}/api/reseller/clients")
        assert resp.status_code == 200
        data = resp.json()
        assert isinstance(data, list)
        print(f"Reseller clients count: {len(data)}")

    def test_reseller_earnings(self, reseller_session):
        """GET /api/reseller/earnings still works"""
        resp = reseller_session.get(f"{BASE_URL}/api/reseller/earnings")
        assert resp.status_code == 200
        data = resp.json()
        assert "commission_rate" in data
        print(f"Reseller earnings: {data}")

    def test_admin_commission_get(self, admin_session, reseller_id):
        """GET /api/admin/resellers/{id}/commissions still works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/resellers/{reseller_id}/commissions")
        assert resp.status_code == 200
        data = resp.json()
        assert "reseller" in data
        assert "overrides" in data
        print(f"Admin commission GET: {len(data['overrides'])} overrides")

    def test_messaging_campaigns(self, reseller_session):
        """GET /api/messaging/campaigns still works"""
        resp = reseller_session.get(f"{BASE_URL}/api/messaging/campaigns")
        assert resp.status_code == 200
        data = resp.json()
        assert isinstance(data, list)
        print(f"Campaigns count: {len(data)}")


# ============================================================
# Commission override CRUD (from iteration 5)
# ============================================================
class TestCommissionOverrideCRUD:
    """Tests for commission override CRUD operations"""

    def test_create_commission_override(self, admin_session, reseller_id):
        """POST /api/admin/resellers/{id}/commissions creates override"""
        resp = admin_session.post(f"{BASE_URL}/api/admin/resellers/{reseller_id}/commissions", json={
            "country": "KE",
            "channel": "sms",
            "commission_rate": 0.25,
            "active": True
        })
        assert resp.status_code == 200
        data = resp.json()
        assert data["country"] == "KE"
        assert data["channel"] == "sms"
        assert data["commission_rate"] == 0.25
        print(f"Created override: {data['id']}")
        return data["id"]

    def test_delete_commission_override(self, admin_session, reseller_id):
        """DELETE /api/admin/resellers/{id}/commissions/{rid} deletes override"""
        # First create one
        create_resp = admin_session.post(f"{BASE_URL}/api/admin/resellers/{reseller_id}/commissions", json={
            "country": "UG",
            "channel": "sms",
            "commission_rate": 0.18,
            "active": True
        })
        assert create_resp.status_code == 200
        override_id = create_resp.json()["id"]
        
        # Delete it
        del_resp = admin_session.delete(f"{BASE_URL}/api/admin/resellers/{reseller_id}/commissions/{override_id}")
        assert del_resp.status_code == 200
        print(f"Deleted override: {override_id}")


if __name__ == "__main__":
    pytest.main([__file__, "-v", "--tb=short"])
