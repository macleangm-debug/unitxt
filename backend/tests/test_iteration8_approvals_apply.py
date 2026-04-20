"""
Iteration 8 Backend Tests - Unified Approvals, Public Apply, Country Economics
Tests for:
- GET /api/admin/approvals/inbox
- POST /api/public/apply/reseller
- POST /api/public/apply/institution
- GET /api/admin/applications/resellers + POST review
- GET /api/admin/applications/institutions + POST review
- GET /api/admin/country-hub/{code}/economics
- PUT /api/admin/country-hub/{code}/rate
- POST /api/admin/country-hub/{code}/prefixes + DELETE
- economy.usd_per_credit setting
- Regression tests for iteration 5/6/7 endpoints
"""
import pytest
import requests
import os
import uuid

BASE_URL = os.environ.get("REACT_APP_BACKEND_URL", "").rstrip("/")
if not BASE_URL:
    BASE_URL = "https://unitxt-global.preview.emergentagent.com"

# Test credentials
ADMIN_EMAIL = "admin@unitxt.io"
ADMIN_PASSWORD = "Admin@2026"
RESELLER_EMAIL = "reseller@unitxt.io"
RESELLER_PASSWORD = "Reseller@2026"
CLIENT_EMAIL = "client@unitxt.io"
CLIENT_PASSWORD = "Client@2026"


@pytest.fixture(scope="module")
def admin_session():
    """Get admin session with auth cookies"""
    session = requests.Session()
    session.headers.update({"Content-Type": "application/json"})
    resp = session.post(f"{BASE_URL}/api/auth/login", json={
        "email": ADMIN_EMAIL, "password": ADMIN_PASSWORD
    })
    assert resp.status_code == 200, f"Admin login failed: {resp.text}"
    return session


@pytest.fixture(scope="module")
def public_session():
    """Public session without auth"""
    session = requests.Session()
    session.headers.update({"Content-Type": "application/json"})
    return session


class TestUnifiedApprovalsInbox:
    """Tests for GET /api/admin/approvals/inbox"""
    
    def test_approvals_inbox_returns_all_queues(self, admin_session):
        """Inbox returns sender_ids, wa_templates, reseller_applications, institution_applications, total"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/approvals/inbox")
        assert resp.status_code == 200
        data = resp.json()
        
        # Verify structure
        assert "sender_ids" in data
        assert "wa_templates" in data
        assert "reseller_applications" in data
        assert "institution_applications" in data
        assert "total" in data
        
        # Verify total is sum of all queues
        expected_total = (len(data["sender_ids"]) + len(data["wa_templates"]) +
                         len(data["reseller_applications"]) + len(data["institution_applications"]))
        assert data["total"] == expected_total
        print(f"✓ Approvals inbox: {data['total']} total pending items")
    
    def test_approvals_inbox_enriches_with_requester_info(self, admin_session):
        """Sender IDs and WA templates should have requester_email and requester_name"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/approvals/inbox")
        assert resp.status_code == 200
        data = resp.json()
        
        # Check sender_ids enrichment
        for sid in data["sender_ids"]:
            assert "requester_email" in sid
            assert "requester_name" in sid
        
        # Check wa_templates enrichment
        for tpl in data["wa_templates"]:
            assert "requester_email" in tpl
            assert "requester_name" in tpl
        
        print(f"✓ Approvals inbox enriches with requester info")


class TestPublicApplyReseller:
    """Tests for POST /api/public/apply/reseller"""
    
    def test_apply_reseller_success(self, public_session):
        """Valid reseller application is accepted"""
        unique_email = f"test_reseller_{uuid.uuid4().hex[:8]}@example.com"
        resp = public_session.post(f"{BASE_URL}/api/public/apply/reseller", json={
            "company_name": "Test Reseller Co",
            "contact_name": "John Doe",
            "email": unique_email,
            "phone": "+255712345678",
            "country": "TZ",
            "website": "https://example.com",
            "expected_monthly_volume": 100000,
            "pitch": "We want to resell SMS services",
            "agree_terms": True
        })
        assert resp.status_code == 200
        data = resp.json()
        assert data["ok"] is True
        assert "id" in data
        print(f"✓ Reseller application created: {data['id'][:8]}")
        return data["id"]
    
    def test_apply_reseller_rejects_without_terms(self, public_session):
        """Application rejected if agree_terms=false"""
        unique_email = f"test_reseller_{uuid.uuid4().hex[:8]}@example.com"
        resp = public_session.post(f"{BASE_URL}/api/public/apply/reseller", json={
            "company_name": "Test Co",
            "contact_name": "Jane Doe",
            "email": unique_email,
            "phone": "+255712345678",
            "country": "TZ",
            "agree_terms": False
        })
        assert resp.status_code == 400
        assert "terms" in resp.text.lower()
        print("✓ Reseller application rejected without terms agreement")
    
    def test_apply_reseller_dedupes_on_email(self, public_session):
        """Duplicate pending application for same email is rejected"""
        unique_email = f"test_reseller_{uuid.uuid4().hex[:8]}@example.com"
        
        # First application
        resp1 = public_session.post(f"{BASE_URL}/api/public/apply/reseller", json={
            "company_name": "First Co",
            "contact_name": "First Person",
            "email": unique_email,
            "country": "TZ",
            "agree_terms": True
        })
        assert resp1.status_code == 200
        
        # Second application with same email
        resp2 = public_session.post(f"{BASE_URL}/api/public/apply/reseller", json={
            "company_name": "Second Co",
            "contact_name": "Second Person",
            "email": unique_email,
            "country": "KE",
            "agree_terms": True
        })
        assert resp2.status_code == 400
        assert "pending" in resp2.text.lower()
        print("✓ Reseller application dedupes on email+pending")


class TestPublicApplyInstitution:
    """Tests for POST /api/public/apply/institution"""
    
    def test_apply_institution_success(self, public_session):
        """Valid institution application is accepted"""
        unique_email = f"test_inst_{uuid.uuid4().hex[:8]}@example.com"
        resp = public_session.post(f"{BASE_URL}/api/public/apply/institution", json={
            "institution_name": "Test Bank Ltd",
            "institution_type": "bank",
            "contact_name": "Jane Smith",
            "email": unique_email,
            "phone": "+255712345678",
            "country": "TZ",
            "use_cases": "OTP, fraud alerts, collections",
            "expected_monthly_volume": 500000,
            "api_integration_needed": True
        })
        assert resp.status_code == 200
        data = resp.json()
        assert data["ok"] is True
        assert "id" in data
        print(f"✓ Institution application created: {data['id'][:8]}")
        return data["id"]
    
    def test_apply_institution_dedupes_on_email(self, public_session):
        """Duplicate pending application for same email is rejected"""
        unique_email = f"test_inst_{uuid.uuid4().hex[:8]}@example.com"
        
        # First application
        resp1 = public_session.post(f"{BASE_URL}/api/public/apply/institution", json={
            "institution_name": "First Bank",
            "institution_type": "bank",
            "contact_name": "First Person",
            "email": unique_email,
            "country": "TZ"
        })
        assert resp1.status_code == 200
        
        # Second application with same email
        resp2 = public_session.post(f"{BASE_URL}/api/public/apply/institution", json={
            "institution_name": "Second Bank",
            "institution_type": "fintech",
            "contact_name": "Second Person",
            "email": unique_email,
            "country": "KE"
        })
        assert resp2.status_code == 400
        assert "pending" in resp2.text.lower()
        print("✓ Institution application dedupes on email+pending")


class TestAdminApplicationsResellers:
    """Tests for GET/POST /api/admin/applications/resellers"""
    
    def test_list_reseller_applications(self, admin_session):
        """Admin can list reseller applications"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/applications/resellers")
        assert resp.status_code == 200
        data = resp.json()
        assert isinstance(data, list)
        print(f"✓ Listed {len(data)} reseller applications")
    
    def test_list_reseller_applications_filter_by_status(self, admin_session):
        """Admin can filter by status"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/applications/resellers?status=pending")
        assert resp.status_code == 200
        data = resp.json()
        for app in data:
            assert app["status"] == "pending"
        print(f"✓ Filtered {len(data)} pending reseller applications")
    
    def test_approve_reseller_creates_user(self, admin_session, public_session):
        """Approving reseller application creates a reseller user with temp password"""
        # Create a new application
        unique_email = f"test_approve_{uuid.uuid4().hex[:8]}@example.com"
        resp = public_session.post(f"{BASE_URL}/api/public/apply/reseller", json={
            "company_name": "Approved Reseller Co",
            "contact_name": "Approved Person",
            "email": unique_email,
            "country": "TZ",
            "agree_terms": True
        })
        assert resp.status_code == 200
        app_id = resp.json()["id"]
        
        # Approve it
        resp = admin_session.post(f"{BASE_URL}/api/admin/applications/resellers/{app_id}/review", json={
            "status": "approved",
            "note": "Test approval"
        })
        assert resp.status_code == 200
        assert resp.json()["ok"] is True
        
        # Verify application was updated
        resp = admin_session.get(f"{BASE_URL}/api/admin/applications/resellers?status=approved")
        assert resp.status_code == 200
        approved = [a for a in resp.json() if a["id"] == app_id]
        assert len(approved) == 1
        assert "provisioned_user_id" in approved[0]
        assert "temp_password" in approved[0]
        print(f"✓ Approved reseller application creates user with temp password")
    
    def test_reject_reseller_application(self, admin_session, public_session):
        """Rejecting reseller application updates status"""
        # Create a new application
        unique_email = f"test_reject_{uuid.uuid4().hex[:8]}@example.com"
        resp = public_session.post(f"{BASE_URL}/api/public/apply/reseller", json={
            "company_name": "Rejected Reseller Co",
            "contact_name": "Rejected Person",
            "email": unique_email,
            "country": "TZ",
            "agree_terms": True
        })
        assert resp.status_code == 200
        app_id = resp.json()["id"]
        
        # Reject it
        resp = admin_session.post(f"{BASE_URL}/api/admin/applications/resellers/{app_id}/review", json={
            "status": "rejected",
            "note": "Test rejection"
        })
        assert resp.status_code == 200
        assert resp.json()["ok"] is True
        print("✓ Rejected reseller application")


class TestAdminApplicationsInstitutions:
    """Tests for GET/POST /api/admin/applications/institutions"""
    
    def test_list_institution_applications(self, admin_session):
        """Admin can list institution applications"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/applications/institutions")
        assert resp.status_code == 200
        data = resp.json()
        assert isinstance(data, list)
        print(f"✓ Listed {len(data)} institution applications")
    
    def test_approve_institution_creates_institution(self, admin_session, public_session):
        """Approving institution application promotes to institutions collection"""
        # Create a new application
        unique_email = f"test_inst_approve_{uuid.uuid4().hex[:8]}@example.com"
        resp = public_session.post(f"{BASE_URL}/api/public/apply/institution", json={
            "institution_name": "Approved Bank Ltd",
            "institution_type": "bank",
            "contact_name": "Bank Manager",
            "email": unique_email,
            "country": "TZ"
        })
        assert resp.status_code == 200
        app_id = resp.json()["id"]
        
        # Approve it
        resp = admin_session.post(f"{BASE_URL}/api/admin/applications/institutions/{app_id}/review", json={
            "status": "approved",
            "note": "Test approval"
        })
        assert resp.status_code == 200
        assert resp.json()["ok"] is True
        
        # Verify institution was created
        resp = admin_session.get(f"{BASE_URL}/api/admin/institutions")
        assert resp.status_code == 200
        institutions = resp.json()
        approved_inst = [i for i in institutions if i.get("from_application_id") == app_id]
        assert len(approved_inst) == 1
        assert approved_inst[0]["name"] == "Approved Bank Ltd"
        print("✓ Approved institution application creates institution")


class TestCountryEconomics:
    """Tests for GET /api/admin/country-hub/{code}/economics"""
    
    def test_economics_returns_unit_economics(self, admin_session):
        """Economics endpoint returns unit economics structure"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-hub/TZ/economics")
        assert resp.status_code == 200
        data = resp.json()
        
        assert "country" in data
        assert data["country"]["code"] == "TZ"
        
        assert "unit_economics" in data
        ue = data["unit_economics"]
        assert "usd_per_credit" in ue
        assert "credits_per_sms" in ue
        assert "retail_usd_per_sms" in ue
        assert "cost_usd_per_sms_min" in ue
        assert "cost_usd_per_sms_max" in ue
        assert "cost_usd_per_sms_avg" in ue
        assert "margin_usd_per_sms" in ue
        assert "margin_pct" in ue
        assert "providers_count" in ue
        
        print(f"✓ Economics for TZ: {ue['credits_per_sms']} cr/SMS, ${ue['usd_per_credit']}/credit")
    
    def test_economics_returns_pnl_windows(self, admin_session):
        """Economics endpoint returns P&L windows for 1/7/30/90 days"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-hub/TZ/economics")
        assert resp.status_code == 200
        data = resp.json()
        
        assert "windows" in data
        windows = data["windows"]
        assert len(windows) == 4
        
        days_expected = [1, 7, 30, 90]
        for i, w in enumerate(windows):
            assert w["days"] == days_expected[i]
            assert "sends" in w
            assert "credits" in w
            assert "revenue_usd" in w
            assert "cost_usd" in w
            assert "margin_usd" in w
            assert "margin_pct" in w
        
        print(f"✓ Economics returns P&L windows: {[w['days'] for w in windows]} days")


class TestCountryRate:
    """Tests for PUT /api/admin/country-hub/{code}/rate"""
    
    def test_update_country_rate(self, admin_session):
        """Admin can update credits per SMS for a country"""
        # Get current rate
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-hub/TZ/economics")
        assert resp.status_code == 200
        original_rate = resp.json()["unit_economics"]["credits_per_sms"]
        
        # Update rate
        new_rate = original_rate + 1 if original_rate < 10 else original_rate - 1
        resp = admin_session.put(f"{BASE_URL}/api/admin/country-hub/TZ/rate", json={
            "credits_per_sms": new_rate
        })
        assert resp.status_code == 200
        assert resp.json()["ok"] is True
        assert resp.json()["credits_per_sms"] == new_rate
        
        # Verify change
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-hub/TZ/economics")
        assert resp.status_code == 200
        assert resp.json()["unit_economics"]["credits_per_sms"] == new_rate
        
        # Restore original
        admin_session.put(f"{BASE_URL}/api/admin/country-hub/TZ/rate", json={
            "credits_per_sms": original_rate
        })
        print(f"✓ Updated country rate: {original_rate} → {new_rate} → {original_rate}")


class TestCountryPrefixes:
    """Tests for POST/DELETE /api/admin/country-hub/{code}/prefixes"""
    
    def test_add_prefix(self, admin_session):
        """Admin can add a mobile prefix"""
        resp = admin_session.post(f"{BASE_URL}/api/admin/country-hub/TZ/prefixes", json={
            "prefix": "+25579",
            "operator": "TestOperator",
            "active": True
        })
        assert resp.status_code == 200
        data = resp.json()
        assert "id" in data
        assert data["prefix"] == "+25579"
        assert data["operator"] == "TestOperator"
        print(f"✓ Added prefix: {data['prefix']} ({data['operator']})")
        return data["id"]
    
    def test_delete_prefix(self, admin_session):
        """Admin can delete a mobile prefix"""
        # First add a prefix
        resp = admin_session.post(f"{BASE_URL}/api/admin/country-hub/TZ/prefixes", json={
            "prefix": "+25578",
            "operator": "ToDelete",
            "active": True
        })
        assert resp.status_code == 200
        prefix_id = resp.json()["id"]
        
        # Delete it
        resp = admin_session.delete(f"{BASE_URL}/api/admin/country-hub/TZ/prefixes/{prefix_id}")
        assert resp.status_code == 200
        assert resp.json()["ok"] is True
        print("✓ Deleted prefix")


class TestEconomyUsdPerCredit:
    """Tests for economy.usd_per_credit setting"""
    
    def test_usd_per_credit_exists(self, admin_session):
        """economy.usd_per_credit setting exists"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/settings?category=economy")
        assert resp.status_code == 200
        settings = resp.json()
        usd_setting = [s for s in settings if s["key"] == "economy.usd_per_credit"]
        assert len(usd_setting) == 1
        assert usd_setting[0]["value"] == 0.01  # default
        print(f"✓ economy.usd_per_credit exists: {usd_setting[0]['value']}")
    
    def test_usd_per_credit_editable(self, admin_session):
        """economy.usd_per_credit can be edited via PUT /admin/settings"""
        # Update
        resp = admin_session.put(f"{BASE_URL}/api/admin/settings", json={
            "key": "economy.usd_per_credit",
            "value": 0.02,
            "category": "economy"
        })
        assert resp.status_code == 200
        
        # Verify
        resp = admin_session.get(f"{BASE_URL}/api/admin/settings?category=economy")
        settings = resp.json()
        usd_setting = [s for s in settings if s["key"] == "economy.usd_per_credit"]
        assert usd_setting[0]["value"] == 0.02
        
        # Restore
        admin_session.put(f"{BASE_URL}/api/admin/settings", json={
            "key": "economy.usd_per_credit",
            "value": 0.01,
            "category": "economy"
        })
        print("✓ economy.usd_per_credit is editable")


class TestRegressionIteration567:
    """Regression tests for iteration 5/6/7 endpoints"""
    
    def test_auth_me(self, admin_session):
        """GET /api/auth/me still works"""
        resp = admin_session.get(f"{BASE_URL}/api/auth/me")
        assert resp.status_code == 200
        assert "user" in resp.json()
        print("✓ Regression: /api/auth/me")
    
    def test_admin_overview(self, admin_session):
        """GET /api/admin/overview still works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/overview")
        assert resp.status_code == 200
        assert "kpi" in resp.json()
        print("✓ Regression: /api/admin/overview")
    
    def test_admin_users(self, admin_session):
        """GET /api/admin/users still works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/users")
        assert resp.status_code == 200
        assert isinstance(resp.json(), list)
        print("✓ Regression: /api/admin/users")
    
    def test_admin_resellers(self, admin_session):
        """GET /api/admin/resellers still works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/resellers")
        assert resp.status_code == 200
        print("✓ Regression: /api/admin/resellers")
    
    def test_admin_country_hub(self, admin_session):
        """GET /api/admin/country-hub still works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-hub")
        assert resp.status_code == 200
        assert isinstance(resp.json(), list)
        print("✓ Regression: /api/admin/country-hub")
    
    def test_admin_country_detail(self, admin_session):
        """GET /api/admin/country-hub/TZ still works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-hub/TZ")
        assert resp.status_code == 200
        data = resp.json()
        assert "country" in data
        assert "routes" in data
        print("✓ Regression: /api/admin/country-hub/TZ")
    
    def test_admin_integrations_health(self, admin_session):
        """GET /api/admin/integrations/health still works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/integrations/health")
        assert resp.status_code == 200
        assert isinstance(resp.json(), list)
        print("✓ Regression: /api/admin/integrations/health")
    
    def test_admin_settings(self, admin_session):
        """GET /api/admin/settings still works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/settings")
        assert resp.status_code == 200
        assert isinstance(resp.json(), list)
        print("✓ Regression: /api/admin/settings")


if __name__ == "__main__":
    pytest.main([__file__, "-v", "--tb=short"])
