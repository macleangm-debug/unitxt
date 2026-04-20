"""
Iteration 9 Tests: Contact Groups, Number Lookup, Campaign Failure Breakdown
Tests for:
- Contact groups CRUD (create, rename, delete)
- Contact group assignment (single + bulk)
- Contact extras for personalization
- Number lookup services endpoint
- Smart validation (single + bulk)
- HLR lookup (should fail with plain English message when not enabled)
- Lookup history
- Campaign detail with failure_breakdown
- Messages with failure_code/failure_reason
- Settings seeded: numbers.smart_validation_cost, numbers.hlr_lookup_cost, numbers.hlr_enabled_countries
"""
import pytest
import requests
import os
import uuid

BASE_URL = os.environ.get('REACT_APP_BACKEND_URL', '').rstrip('/')

class TestNumberLookupServices:
    """Number lookup services endpoint tests"""
    
    @pytest.fixture(autouse=True)
    def setup(self):
        self.session = requests.Session()
        # Login as client
        r = self.session.post(f"{BASE_URL}/api/auth/login", json={
            "email": "client@unitxt.io",
            "password": "Client@2026"
        })
        assert r.status_code == 200, f"Login failed: {r.text}"
        self.token = r.json()["access_token"]
        self.session.headers.update({"Authorization": f"Bearer {self.token}"})
    
    def test_get_number_services(self):
        """GET /api/numbers/services returns smart_validation and hlr_lookup info"""
        r = self.session.get(f"{BASE_URL}/api/numbers/services")
        assert r.status_code == 200
        data = r.json()
        
        # Verify smart_validation
        assert "smart_validation" in data
        sv = data["smart_validation"]
        assert sv["name"] == "Smart number validation"
        assert sv["available_everywhere"] == True
        assert "cost_per_lookup" in sv
        assert sv["cost_per_lookup"] >= 1
        
        # Verify hlr_lookup
        assert "hlr_lookup" in data
        hlr = data["hlr_lookup"]
        assert hlr["name"] == "HLR number lookup"
        assert hlr["available_everywhere"] == False
        assert "cost_per_lookup" in hlr
        assert hlr["cost_per_lookup"] >= 1
        assert "status" in hlr
        assert hlr["status"] in ["coming_soon", "live"]
        assert "available_countries" in hlr
        
    def test_smart_validation_single(self):
        """POST /api/numbers/validate with smart_validation returns validation result"""
        r = self.session.post(f"{BASE_URL}/api/numbers/validate", json={
            "phone": "+255712000001",
            "service": "smart_validation"
        })
        assert r.status_code == 200
        data = r.json()
        
        # Verify response structure
        assert data["input"] == "+255712000001"
        assert data["e164"] == "+255712000001"
        assert data["valid"] == True
        assert data["country"] == "TZ"
        assert data["operator"] == "Tigo"
        assert data["is_mobile"] == True
        assert data["service"] == "smart_validation"
        assert "credits_charged" in data
        assert data["credits_charged"] >= 1
        assert "risk_flags" in data
        assert isinstance(data["risk_flags"], list)
        
    def test_smart_validation_invalid_number(self):
        """POST /api/numbers/validate with invalid number returns valid=false with risk_flags"""
        r = self.session.post(f"{BASE_URL}/api/numbers/validate", json={
            "phone": "invalid",
            "service": "smart_validation"
        })
        assert r.status_code == 200
        data = r.json()
        
        assert data["valid"] == False
        assert len(data["risk_flags"]) > 0
        
    def test_hlr_lookup_not_live_returns_400(self):
        """POST /api/numbers/validate with hlr_lookup when not enabled returns 400 with plain English"""
        r = self.session.post(f"{BASE_URL}/api/numbers/validate", json={
            "phone": "+255712000001",
            "service": "hlr_lookup"
        })
        # Should return 400 when HLR is not live
        assert r.status_code == 400
        data = r.json()
        assert "detail" in data
        # Verify plain English message
        assert "HLR lookup is not live yet" in data["detail"]
        assert "Smart validation" in data["detail"] or "account manager" in data["detail"]
        
    def test_bulk_validation(self):
        """POST /api/numbers/validate/bulk validates multiple numbers"""
        r = self.session.post(f"{BASE_URL}/api/numbers/validate/bulk", json={
            "phones": ["+255712000001", "+254712345678", "invalid"],
            "service": "smart_validation"
        })
        assert r.status_code == 200
        data = r.json()
        
        assert data["total"] == 3
        assert data["valid"] == 2
        assert data["invalid"] == 1
        assert "credits_charged" in data
        assert data["credits_charged"] >= 3
        assert "results" in data
        assert len(data["results"]) == 3
        
    def test_bulk_validation_max_5000(self):
        """POST /api/numbers/validate/bulk rejects >5000 numbers"""
        # Create list of 5001 numbers
        phones = [f"+25571200{str(i).zfill(4)}" for i in range(5001)]
        r = self.session.post(f"{BASE_URL}/api/numbers/validate/bulk", json={
            "phones": phones,
            "service": "smart_validation"
        })
        assert r.status_code == 400
        assert "5,000" in r.json()["detail"] or "5000" in r.json()["detail"]
        
    def test_lookup_history(self):
        """GET /api/numbers/history returns last 100 lookups"""
        r = self.session.get(f"{BASE_URL}/api/numbers/history")
        assert r.status_code == 200
        data = r.json()
        
        assert isinstance(data, list)
        assert len(data) <= 100
        if len(data) > 0:
            item = data[0]
            assert "id" in item
            assert "user_id" in item
            assert "service" in item
            assert "credits" in item
            assert "created_at" in item


class TestContactGroups:
    """Contact groups CRUD and assignment tests"""
    
    @pytest.fixture(autouse=True)
    def setup(self):
        self.session = requests.Session()
        # Login as client
        r = self.session.post(f"{BASE_URL}/api/auth/login", json={
            "email": "client@unitxt.io",
            "password": "Client@2026"
        })
        assert r.status_code == 200
        self.token = r.json()["access_token"]
        self.session.headers.update({"Authorization": f"Bearer {self.token}"})
        self.test_group_id = None
        self.test_contact_id = None
        
    def teardown_method(self, method):
        """Cleanup test data"""
        if self.test_group_id:
            try:
                self.session.delete(f"{BASE_URL}/api/contacts/groups/{self.test_group_id}")
            except:
                pass
        if self.test_contact_id:
            try:
                self.session.delete(f"{BASE_URL}/api/contacts/{self.test_contact_id}")
            except:
                pass
    
    def test_create_group(self):
        """POST /api/contacts/groups creates a new group"""
        r = self.session.post(f"{BASE_URL}/api/contacts/groups", json={
            "name": f"TEST_Group_{uuid.uuid4().hex[:8]}",
            "description": "Test group description"
        })
        assert r.status_code == 200
        data = r.json()
        
        assert "id" in data
        assert "name" in data
        assert "description" in data
        assert data["description"] == "Test group description"
        self.test_group_id = data["id"]
        
    def test_list_groups(self):
        """GET /api/contacts/groups returns user's groups"""
        r = self.session.get(f"{BASE_URL}/api/contacts/groups")
        assert r.status_code == 200
        data = r.json()
        
        assert isinstance(data, list)
        
    def test_rename_group(self):
        """PUT /api/contacts/groups/{gid} renames a group"""
        # Create group first
        r = self.session.post(f"{BASE_URL}/api/contacts/groups", json={
            "name": f"TEST_ToRename_{uuid.uuid4().hex[:8]}",
            "description": "Original"
        })
        assert r.status_code == 200
        group_id = r.json()["id"]
        self.test_group_id = group_id
        
        # Rename it
        r = self.session.put(f"{BASE_URL}/api/contacts/groups/{group_id}", json={
            "name": "TEST_Renamed",
            "description": "Updated description"
        })
        assert r.status_code == 200
        assert r.json()["ok"] == True
        
    def test_delete_group_removes_from_contacts(self):
        """DELETE /api/contacts/groups/{gid} removes group and pulls from contacts"""
        # Create group
        r = self.session.post(f"{BASE_URL}/api/contacts/groups", json={
            "name": f"TEST_ToDelete_{uuid.uuid4().hex[:8]}",
            "description": "Will be deleted"
        })
        assert r.status_code == 200
        group_id = r.json()["id"]
        
        # Create contact with this group
        r = self.session.post(f"{BASE_URL}/api/contacts", json={
            "phone": f"+25571299{uuid.uuid4().hex[:4]}",
            "name": "TEST_Contact",
            "group_ids": [group_id]
        })
        assert r.status_code == 200
        contact_id = r.json()["id"]
        self.test_contact_id = contact_id
        
        # Delete group
        r = self.session.delete(f"{BASE_URL}/api/contacts/groups/{group_id}")
        assert r.status_code == 200
        
        # Verify contact no longer has this group
        r = self.session.get(f"{BASE_URL}/api/contacts")
        contacts = r.json()
        contact = next((c for c in contacts if c["id"] == contact_id), None)
        if contact and "group_ids" in contact:
            assert group_id not in contact["group_ids"]
            
    def test_assign_contact_to_groups(self):
        """POST /api/contacts/{cid}/groups replaces contact's groups"""
        # Create group
        r = self.session.post(f"{BASE_URL}/api/contacts/groups", json={
            "name": f"TEST_Assign_{uuid.uuid4().hex[:8]}"
        })
        assert r.status_code == 200
        group_id = r.json()["id"]
        self.test_group_id = group_id
        
        # Create contact
        r = self.session.post(f"{BASE_URL}/api/contacts", json={
            "phone": f"+25571288{uuid.uuid4().hex[:4]}",
            "name": "TEST_AssignContact"
        })
        assert r.status_code == 200
        contact_id = r.json()["id"]
        self.test_contact_id = contact_id
        
        # Assign to group
        r = self.session.post(f"{BASE_URL}/api/contacts/{contact_id}/groups", json={
            "group_ids": [group_id]
        })
        assert r.status_code == 200
        assert r.json()["ok"] == True
        assert group_id in r.json()["group_ids"]
        
    def test_assign_validates_group_ownership(self):
        """POST /api/contacts/{cid}/groups rejects groups not owned by user"""
        # Get a contact
        r = self.session.get(f"{BASE_URL}/api/contacts")
        contacts = r.json()
        if not contacts:
            pytest.skip("No contacts to test with")
        contact_id = contacts[0]["id"]
        
        # Try to assign a fake group ID
        r = self.session.post(f"{BASE_URL}/api/contacts/{contact_id}/groups", json={
            "group_ids": ["fake-group-id-12345"]
        })
        assert r.status_code == 400
        assert "not found" in r.json()["detail"].lower()
        
    def test_bulk_group_add(self):
        """POST /api/contacts/groups/{gid}/bulk adds group to multiple contacts"""
        # Create group
        r = self.session.post(f"{BASE_URL}/api/contacts/groups", json={
            "name": f"TEST_BulkAdd_{uuid.uuid4().hex[:8]}"
        })
        assert r.status_code == 200
        group_id = r.json()["id"]
        self.test_group_id = group_id
        
        # Get contacts
        r = self.session.get(f"{BASE_URL}/api/contacts")
        contacts = r.json()
        if len(contacts) < 2:
            pytest.skip("Need at least 2 contacts")
        contact_ids = [c["id"] for c in contacts[:2]]
        
        # Bulk add
        r = self.session.post(f"{BASE_URL}/api/contacts/groups/{group_id}/bulk", json={
            "contact_ids": contact_ids,
            "action": "add"
        })
        assert r.status_code == 200
        data = r.json()
        assert data["ok"] == True
        assert data["action"] == "add"
        assert data["group_id"] == group_id
        
    def test_bulk_group_remove(self):
        """POST /api/contacts/groups/{gid}/bulk removes group from contacts"""
        # Create group
        r = self.session.post(f"{BASE_URL}/api/contacts/groups", json={
            "name": f"TEST_BulkRemove_{uuid.uuid4().hex[:8]}"
        })
        assert r.status_code == 200
        group_id = r.json()["id"]
        self.test_group_id = group_id
        
        # Create contact with group
        r = self.session.post(f"{BASE_URL}/api/contacts", json={
            "phone": f"+25571277{uuid.uuid4().hex[:4]}",
            "name": "TEST_BulkRemoveContact",
            "group_ids": [group_id]
        })
        assert r.status_code == 200
        contact_id = r.json()["id"]
        self.test_contact_id = contact_id
        
        # Bulk remove
        r = self.session.post(f"{BASE_URL}/api/contacts/groups/{group_id}/bulk", json={
            "contact_ids": [contact_id],
            "action": "remove"
        })
        assert r.status_code == 200
        data = r.json()
        assert data["ok"] == True
        assert data["action"] == "remove"


class TestContactExtras:
    """Contact extras (custom fields) for personalization"""
    
    @pytest.fixture(autouse=True)
    def setup(self):
        self.session = requests.Session()
        r = self.session.post(f"{BASE_URL}/api/auth/login", json={
            "email": "client@unitxt.io",
            "password": "Client@2026"
        })
        assert r.status_code == 200
        self.token = r.json()["access_token"]
        self.session.headers.update({"Authorization": f"Bearer {self.token}"})
        self.test_contact_id = None
        
    def teardown_method(self, method):
        if self.test_contact_id:
            try:
                self.session.delete(f"{BASE_URL}/api/contacts/{self.test_contact_id}")
            except:
                pass
    
    def test_create_contact_with_extras(self):
        """POST /api/contacts accepts extras dict for personalization"""
        r = self.session.post(f"{BASE_URL}/api/contacts", json={
            "phone": f"+25571266{uuid.uuid4().hex[:4]}",
            "name": "TEST_ExtrasContact",
            "extras": {
                "amount_owed": "15000",
                "due_date": "2026-05-01",
                "account_number": "12345"
            }
        })
        assert r.status_code == 200
        data = r.json()
        self.test_contact_id = data["id"]
        
        assert "extras" in data
        assert data["extras"]["amount_owed"] == "15000"
        assert data["extras"]["due_date"] == "2026-05-01"
        assert data["extras"]["account_number"] == "12345"


class TestCampaignFailureBreakdown:
    """Campaign detail with failure breakdown and plain English reasons"""
    
    @pytest.fixture(autouse=True)
    def setup(self):
        self.session = requests.Session()
        r = self.session.post(f"{BASE_URL}/api/auth/login", json={
            "email": "client@unitxt.io",
            "password": "Client@2026"
        })
        assert r.status_code == 200
        self.token = r.json()["access_token"]
        self.session.headers.update({"Authorization": f"Bearer {self.token}"})
    
    def test_campaign_detail_has_failure_breakdown(self):
        """GET /api/messaging/campaigns/{cid} returns failure_breakdown array"""
        # Get campaigns
        r = self.session.get(f"{BASE_URL}/api/messaging/campaigns")
        assert r.status_code == 200
        campaigns = r.json()
        
        if not campaigns:
            pytest.skip("No campaigns to test")
            
        campaign_id = campaigns[0]["id"]
        
        # Get campaign detail
        r = self.session.get(f"{BASE_URL}/api/messaging/campaigns/{campaign_id}")
        assert r.status_code == 200
        data = r.json()
        
        assert "campaign" in data
        assert "messages" in data
        assert "failure_breakdown" in data
        assert isinstance(data["failure_breakdown"], list)
        
        # If there are failures, verify structure
        if data["failure_breakdown"]:
            fb = data["failure_breakdown"][0]
            assert "code" in fb
            assert "reason" in fb
            assert "count" in fb
            
    def test_messages_have_failure_code_reason(self):
        """GET /api/messaging/messages returns failure_code/failure_reason for failed messages"""
        r = self.session.get(f"{BASE_URL}/api/messaging/messages")
        assert r.status_code == 200
        messages = r.json()
        
        # Check structure - failed messages should have failure_code/failure_reason
        for msg in messages:
            if msg.get("status") in ("failed", "undelivered"):
                assert "failure_code" in msg
                assert "failure_reason" in msg
                # Verify plain English (not technical codes)
                assert len(msg["failure_reason"]) > 10  # Should be a sentence


class TestSettingsSeeded:
    """Verify number lookup settings are seeded"""
    
    @pytest.fixture(autouse=True)
    def setup(self):
        self.session = requests.Session()
        # Login as admin to check settings
        r = self.session.post(f"{BASE_URL}/api/auth/login", json={
            "email": "admin@unitxt.io",
            "password": "Admin@2026"
        })
        assert r.status_code == 200
        self.token = r.json()["access_token"]
        self.session.headers.update({"Authorization": f"Bearer {self.token}"})
    
    def test_smart_validation_cost_seeded(self):
        """numbers.smart_validation_cost setting exists with default 1"""
        r = self.session.get(f"{BASE_URL}/api/admin/settings")
        assert r.status_code == 200
        settings = r.json()
        
        # Find the setting
        sv_cost = next((s for s in settings if s["key"] == "numbers.smart_validation_cost"), None)
        assert sv_cost is not None, "numbers.smart_validation_cost not found in settings"
        assert sv_cost["value"] == 1
        
    def test_hlr_lookup_cost_seeded(self):
        """numbers.hlr_lookup_cost setting exists with default 5"""
        r = self.session.get(f"{BASE_URL}/api/admin/settings")
        assert r.status_code == 200
        settings = r.json()
        
        hlr_cost = next((s for s in settings if s["key"] == "numbers.hlr_lookup_cost"), None)
        assert hlr_cost is not None, "numbers.hlr_lookup_cost not found in settings"
        assert hlr_cost["value"] == 5
        
    def test_hlr_enabled_countries_seeded_empty(self):
        """numbers.hlr_enabled_countries setting exists with empty list (not live)"""
        r = self.session.get(f"{BASE_URL}/api/admin/settings")
        assert r.status_code == 200
        settings = r.json()
        
        hlr_countries = next((s for s in settings if s["key"] == "numbers.hlr_enabled_countries"), None)
        assert hlr_countries is not None, "numbers.hlr_enabled_countries not found in settings"
        assert hlr_countries["value"] == []


class TestRegressionIteration8:
    """Regression tests for iteration 5-8 endpoints"""
    
    @pytest.fixture(autouse=True)
    def setup(self):
        self.session = requests.Session()
        
    def test_reseller_clients_endpoint(self):
        """GET /api/reseller/clients works for reseller"""
        r = self.session.post(f"{BASE_URL}/api/auth/login", json={
            "email": "reseller@unitxt.io",
            "password": "Reseller@2026"
        })
        assert r.status_code == 200
        token = r.json()["access_token"]
        
        r = self.session.get(f"{BASE_URL}/api/reseller/clients",
                            headers={"Authorization": f"Bearer {token}"})
        assert r.status_code == 200
        
    def test_admin_approvals_inbox(self):
        """GET /api/admin/approvals/inbox works for admin"""
        r = self.session.post(f"{BASE_URL}/api/auth/login", json={
            "email": "admin@unitxt.io",
            "password": "Admin@2026"
        })
        assert r.status_code == 200
        token = r.json()["access_token"]
        
        r = self.session.get(f"{BASE_URL}/api/admin/approvals/inbox",
                            headers={"Authorization": f"Bearer {token}"})
        assert r.status_code == 200
        data = r.json()
        assert "sender_ids" in data
        assert "total" in data
        
    def test_country_hub_list(self):
        """GET /api/admin/country-hub works for admin"""
        r = self.session.post(f"{BASE_URL}/api/auth/login", json={
            "email": "admin@unitxt.io",
            "password": "Admin@2026"
        })
        assert r.status_code == 200
        token = r.json()["access_token"]
        
        r = self.session.get(f"{BASE_URL}/api/admin/country-hub",
                            headers={"Authorization": f"Bearer {token}"})
        assert r.status_code == 200
        
    def test_country_economics(self):
        """GET /api/admin/country-hub/{code}/economics works"""
        r = self.session.post(f"{BASE_URL}/api/auth/login", json={
            "email": "admin@unitxt.io",
            "password": "Admin@2026"
        })
        assert r.status_code == 200
        token = r.json()["access_token"]
        
        r = self.session.get(f"{BASE_URL}/api/admin/country-hub/TZ/economics",
                            headers={"Authorization": f"Bearer {token}"})
        assert r.status_code == 200
        data = r.json()
        assert "unit_economics" in data
        assert "windows" in data


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
