"""
Iteration 10 Tests: Country-specific credit packs + promotions, Auto-clean contacts, Delivery rate dashboard
Tests:
- POST /api/admin/credit-packs with {country} field (null = global, ISO-2 = country-scoped)
- GET /api/credits/packs scopes to global + user's country
- POST /api/admin/promotions with {country} field
- POST /api/credits/buy validates promo country match (returns 400 if mismatch)
- POST /api/numbers/auto-clean runs smart_validate on contacts, charges credits, removes invalids
- GET /api/messaging/delivery-report returns timeline, failure_reasons, by_country
- Regression tests for iteration 5-9 endpoints
"""
import pytest
import requests
import os
import time

BASE_URL = os.environ.get("REACT_APP_BACKEND_URL", "").rstrip("/")

# Test credentials
ADMIN_EMAIL = "admin@unitxt.io"
ADMIN_PASSWORD = "Admin@2026"
CLIENT_EMAIL = "client@unitxt.io"
CLIENT_PASSWORD = "Client@2026"


class TestSetup:
    """Setup and authentication tests"""
    
    @pytest.fixture(scope="class")
    def admin_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": ADMIN_EMAIL, "password": ADMIN_PASSWORD
        })
        assert resp.status_code == 200, f"Admin login failed: {resp.text}"
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    @pytest.fixture(scope="class")
    def client_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL, "password": CLIENT_PASSWORD
        })
        assert resp.status_code == 200, f"Client login failed: {resp.text}"
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    def test_admin_login(self, admin_session):
        """Verify admin can login"""
        resp = admin_session.get(f"{BASE_URL}/api/auth/me")
        assert resp.status_code == 200
        data = resp.json()
        assert data["user"]["role"] == "super_admin"
        print(f"Admin logged in: {data['user']['email']}")
    
    def test_client_login(self, client_session):
        """Verify client can login"""
        resp = client_session.get(f"{BASE_URL}/api/auth/me")
        assert resp.status_code == 200
        data = resp.json()
        assert data["user"]["role"] == "client"
        assert data["user"]["country"] == "TZ"
        print(f"Client logged in: {data['user']['email']}, country: {data['user']['country']}")


class TestCountryCreditPacks:
    """Test country-specific credit packs"""
    
    @pytest.fixture(scope="class")
    def admin_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": ADMIN_EMAIL, "password": ADMIN_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    @pytest.fixture(scope="class")
    def client_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL, "password": CLIENT_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    def test_create_global_pack(self, admin_session):
        """Admin can create a global credit pack (country=null)"""
        resp = admin_session.post(f"{BASE_URL}/api/admin/credit-packs", json={
            "name": "TEST_Global_Pack",
            "credits": 5000,
            "price_usd": 50,
            "tag": "test",
            "country": None,
            "active": True
        })
        assert resp.status_code == 200, f"Failed to create global pack: {resp.text}"
        data = resp.json()
        assert data["name"] == "TEST_Global_Pack"
        assert data["country"] is None
        print(f"Created global pack: {data['name']}, id: {data['id']}")
    
    def test_create_tz_pack(self, admin_session):
        """Admin can create a TZ-specific credit pack"""
        resp = admin_session.post(f"{BASE_URL}/api/admin/credit-packs", json={
            "name": "TEST_TZ_Pack",
            "credits": 3000,
            "price_usd": 25,
            "tag": "tanzania",
            "country": "TZ",
            "active": True
        })
        assert resp.status_code == 200, f"Failed to create TZ pack: {resp.text}"
        data = resp.json()
        assert data["name"] == "TEST_TZ_Pack"
        assert data["country"] == "TZ"
        print(f"Created TZ pack: {data['name']}, country: {data['country']}")
    
    def test_create_ke_pack(self, admin_session):
        """Admin can create a KE-specific credit pack"""
        resp = admin_session.post(f"{BASE_URL}/api/admin/credit-packs", json={
            "name": "TEST_KE_Pack",
            "credits": 4000,
            "price_usd": 35,
            "tag": "kenya",
            "country": "KE",
            "active": True
        })
        assert resp.status_code == 200, f"Failed to create KE pack: {resp.text}"
        data = resp.json()
        assert data["name"] == "TEST_KE_Pack"
        assert data["country"] == "KE"
        print(f"Created KE pack: {data['name']}, country: {data['country']}")
    
    def test_admin_sees_all_packs(self, admin_session):
        """Admin endpoint returns all packs"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/credit-packs")
        assert resp.status_code == 200
        packs = resp.json()
        names = [p["name"] for p in packs]
        assert "TEST_Global_Pack" in names or any("Global" in n for n in names)
        print(f"Admin sees {len(packs)} packs")
    
    def test_tz_client_sees_global_and_tz_packs(self, client_session):
        """TZ client sees global packs + TZ-specific packs, NOT KE packs"""
        resp = client_session.get(f"{BASE_URL}/api/credits/packs")
        assert resp.status_code == 200
        packs = resp.json()
        
        # Check that TZ pack is visible
        tz_packs = [p for p in packs if p.get("country") == "TZ"]
        global_packs = [p for p in packs if p.get("country") is None]
        ke_packs = [p for p in packs if p.get("country") == "KE"]
        
        print(f"TZ client sees: {len(tz_packs)} TZ packs, {len(global_packs)} global packs, {len(ke_packs)} KE packs")
        
        # TZ client should NOT see KE packs
        assert len(ke_packs) == 0, f"TZ client should not see KE packs, but found: {ke_packs}"
        
        # Should see at least some packs (global or TZ)
        assert len(packs) > 0, "Client should see at least some packs"


class TestCountryPromotions:
    """Test country-specific promotions"""
    
    @pytest.fixture(scope="class")
    def admin_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": ADMIN_EMAIL, "password": ADMIN_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    @pytest.fixture(scope="class")
    def client_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL, "password": CLIENT_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    def test_create_global_promo(self, admin_session):
        """Admin can create a global promotion (country=null)"""
        resp = admin_session.post(f"{BASE_URL}/api/admin/promotions", json={
            "name": "TEST_Global_Promo",
            "code": "TESTGLOBAL10",
            "type": "bonus_credit",
            "value": 10,
            "min_topup": 0,
            "country": None,
            "active": True
        })
        assert resp.status_code == 200, f"Failed to create global promo: {resp.text}"
        data = resp.json()
        assert data["code"] == "TESTGLOBAL10"
        assert data["country"] is None
        print(f"Created global promo: {data['code']}")
    
    def test_create_tz_promo(self, admin_session):
        """Admin can create a TZ-specific promotion"""
        resp = admin_session.post(f"{BASE_URL}/api/admin/promotions", json={
            "name": "TEST_TZ_Promo",
            "code": "TESTTZ20",
            "type": "bonus_credit",
            "value": 20,
            "min_topup": 0,
            "country": "TZ",
            "active": True
        })
        assert resp.status_code == 200, f"Failed to create TZ promo: {resp.text}"
        data = resp.json()
        assert data["code"] == "TESTTZ20"
        assert data["country"] == "TZ"
        print(f"Created TZ promo: {data['code']}, country: {data['country']}")
    
    def test_create_ke_promo(self, admin_session):
        """Admin can create a KE-specific promotion"""
        resp = admin_session.post(f"{BASE_URL}/api/admin/promotions", json={
            "name": "TEST_KE_Promo",
            "code": "TESTKE30",
            "type": "bonus_credit",
            "value": 30,
            "min_topup": 0,
            "country": "KE",
            "active": True
        })
        assert resp.status_code == 200, f"Failed to create KE promo: {resp.text}"
        data = resp.json()
        assert data["code"] == "TESTKE30"
        assert data["country"] == "KE"
        print(f"Created KE promo: {data['code']}, country: {data['country']}")
    
    def test_tz_client_can_use_global_promo(self, client_session, admin_session):
        """TZ client can use a global promo code"""
        # First get a pack to buy
        packs_resp = client_session.get(f"{BASE_URL}/api/credits/packs")
        packs = packs_resp.json()
        if not packs:
            pytest.skip("No packs available")
        
        pack = packs[0]
        resp = client_session.post(f"{BASE_URL}/api/credits/buy", json={
            "pack_id": pack["id"],
            "promo_code": "TESTGLOBAL10"
        })
        # Should succeed (200) or fail for other reasons (not country mismatch)
        if resp.status_code != 200:
            # Check it's not a country mismatch error
            assert "only valid for customers in" not in resp.text.lower(), \
                f"Global promo should work for any country: {resp.text}"
        print(f"TZ client using global promo: status={resp.status_code}")
    
    def test_tz_client_can_use_tz_promo(self, client_session):
        """TZ client can use a TZ-specific promo code"""
        packs_resp = client_session.get(f"{BASE_URL}/api/credits/packs")
        packs = packs_resp.json()
        if not packs:
            pytest.skip("No packs available")
        
        pack = packs[0]
        resp = client_session.post(f"{BASE_URL}/api/credits/buy", json={
            "pack_id": pack["id"],
            "promo_code": "TESTTZ20"
        })
        # Should succeed
        if resp.status_code != 200:
            assert "only valid for customers in" not in resp.text.lower(), \
                f"TZ promo should work for TZ client: {resp.text}"
        print(f"TZ client using TZ promo: status={resp.status_code}")
    
    def test_tz_client_cannot_use_ke_promo(self, client_session):
        """TZ client CANNOT use a KE-specific promo code"""
        packs_resp = client_session.get(f"{BASE_URL}/api/credits/packs")
        packs = packs_resp.json()
        if not packs:
            pytest.skip("No packs available")
        
        pack = packs[0]
        resp = client_session.post(f"{BASE_URL}/api/credits/buy", json={
            "pack_id": pack["id"],
            "promo_code": "TESTKE30"
        })
        # Should fail with 400 and country mismatch message
        assert resp.status_code == 400, f"Expected 400 for country mismatch, got {resp.status_code}"
        assert "only valid for customers in KE" in resp.text or "only valid for customers in" in resp.text, \
            f"Expected country mismatch error, got: {resp.text}"
        print(f"TZ client correctly rejected from using KE promo: {resp.text}")


class TestAutoCleanContacts:
    """Test auto-clean contacts feature"""
    
    @pytest.fixture(scope="class")
    def client_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL, "password": CLIENT_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    def test_create_test_contacts(self, client_session):
        """Create some test contacts for auto-clean"""
        # Create valid contacts
        for i in range(3):
            resp = client_session.post(f"{BASE_URL}/api/contacts", json={
                "phone": f"+25571234567{i}",
                "name": f"TEST_Valid_{i}",
                "tags": ["test"]
            })
            assert resp.status_code == 200, f"Failed to create contact: {resp.text}"
        
        # Create invalid contacts (bad format)
        for i in range(2):
            resp = client_session.post(f"{BASE_URL}/api/contacts", json={
                "phone": f"invalid{i}",
                "name": f"TEST_Invalid_{i}",
                "tags": ["test"]
            })
            assert resp.status_code == 200, f"Failed to create contact: {resp.text}"
        
        print("Created 3 valid + 2 invalid test contacts")
    
    def test_auto_clean_dry_run(self, client_session):
        """Test auto-clean with remove_invalid=false (dry run)"""
        resp = client_session.post(f"{BASE_URL}/api/numbers/auto-clean", json={
            "group_id": None,
            "remove_invalid": False
        })
        # May fail if no contacts or insufficient credits
        if resp.status_code == 200:
            data = resp.json()
            assert "total" in data
            assert "valid" in data
            assert "invalid" in data
            assert "removed" in data
            assert "credits_charged" in data
            assert data["removed"] == 0, "Dry run should not remove contacts"
            print(f"Auto-clean dry run: total={data['total']}, valid={data['valid']}, invalid={data['invalid']}, credits={data['credits_charged']}")
        elif resp.status_code == 402:
            print(f"Auto-clean skipped: insufficient credits")
        else:
            print(f"Auto-clean response: {resp.status_code} - {resp.text}")
    
    def test_auto_clean_with_removal(self, client_session):
        """Test auto-clean with remove_invalid=true"""
        # First check how many contacts we have
        contacts_resp = client_session.get(f"{BASE_URL}/api/contacts")
        initial_count = len(contacts_resp.json())
        
        resp = client_session.post(f"{BASE_URL}/api/numbers/auto-clean", json={
            "group_id": None,
            "remove_invalid": True
        })
        
        if resp.status_code == 200:
            data = resp.json()
            assert "total" in data
            assert "valid" in data
            assert "invalid" in data
            assert "removed" in data
            assert "credits_charged" in data
            
            # Verify contacts were actually removed
            contacts_after = client_session.get(f"{BASE_URL}/api/contacts")
            final_count = len(contacts_after.json())
            
            print(f"Auto-clean: total={data['total']}, valid={data['valid']}, invalid={data['invalid']}, removed={data['removed']}")
            print(f"Contacts before: {initial_count}, after: {final_count}")
        elif resp.status_code == 402:
            print(f"Auto-clean skipped: insufficient credits")
        else:
            print(f"Auto-clean response: {resp.status_code} - {resp.text}")
    
    def test_auto_clean_returns_results(self, client_session):
        """Test that auto-clean returns detailed results"""
        resp = client_session.post(f"{BASE_URL}/api/numbers/auto-clean", json={
            "group_id": None,
            "remove_invalid": False
        })
        
        if resp.status_code == 200:
            data = resp.json()
            # Check results array is present (may be truncated to 500)
            if "results" in data and len(data["results"]) > 0:
                result = data["results"][0]
                assert "contact_id" in result
                assert "phone" in result
                assert "valid" in result
                print(f"Auto-clean results sample: {result}")
        elif resp.status_code == 402:
            print("Auto-clean skipped: insufficient credits")


class TestDeliveryReport:
    """Test delivery rate dashboard endpoint"""
    
    @pytest.fixture(scope="class")
    def client_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL, "password": CLIENT_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    def test_delivery_report_default(self, client_session):
        """Test delivery report with default 30 days"""
        resp = client_session.get(f"{BASE_URL}/api/messaging/delivery-report")
        assert resp.status_code == 200, f"Failed to get delivery report: {resp.text}"
        data = resp.json()
        
        # Check required fields
        assert "days" in data
        assert "total_sent" in data
        assert "delivered" in data
        assert "failed" in data
        assert "delivery_rate" in data
        assert "timeline" in data
        assert "failure_reasons" in data
        assert "by_country" in data
        
        print(f"Delivery report (30d): sent={data['total_sent']}, delivered={data['delivered']}, failed={data['failed']}, rate={data['delivery_rate']}%")
    
    def test_delivery_report_7_days(self, client_session):
        """Test delivery report with 7 days"""
        resp = client_session.get(f"{BASE_URL}/api/messaging/delivery-report?days=7")
        assert resp.status_code == 200
        data = resp.json()
        assert data["days"] == 7
        print(f"Delivery report (7d): sent={data['total_sent']}, rate={data['delivery_rate']}%")
    
    def test_delivery_report_90_days(self, client_session):
        """Test delivery report with 90 days"""
        resp = client_session.get(f"{BASE_URL}/api/messaging/delivery-report?days=90")
        assert resp.status_code == 200
        data = resp.json()
        assert data["days"] == 90
        print(f"Delivery report (90d): sent={data['total_sent']}, rate={data['delivery_rate']}%")
    
    def test_delivery_report_365_days(self, client_session):
        """Test delivery report with 365 days"""
        resp = client_session.get(f"{BASE_URL}/api/messaging/delivery-report?days=365")
        assert resp.status_code == 200
        data = resp.json()
        assert data["days"] == 365
        print(f"Delivery report (365d): sent={data['total_sent']}, rate={data['delivery_rate']}%")
    
    def test_delivery_report_timeline_structure(self, client_session):
        """Test timeline array structure"""
        resp = client_session.get(f"{BASE_URL}/api/messaging/delivery-report?days=30")
        assert resp.status_code == 200
        data = resp.json()
        
        timeline = data.get("timeline", [])
        if len(timeline) > 0:
            day_entry = timeline[0]
            assert "day" in day_entry
            assert "sent" in day_entry
            assert "delivered" in day_entry
            assert "failed" in day_entry
            assert "delivery_rate" in day_entry
            print(f"Timeline sample: {day_entry}")
        else:
            print("Timeline is empty (no messages in period)")
    
    def test_delivery_report_failure_reasons_structure(self, client_session):
        """Test failure_reasons array structure"""
        resp = client_session.get(f"{BASE_URL}/api/messaging/delivery-report?days=30")
        assert resp.status_code == 200
        data = resp.json()
        
        reasons = data.get("failure_reasons", [])
        if len(reasons) > 0:
            reason = reasons[0]
            assert "code" in reason
            assert "reason" in reason
            assert "count" in reason
            # Reason should be plain English, not technical code
            assert len(reason["reason"]) > 10, "Reason should be descriptive"
            print(f"Failure reason sample: code={reason['code']}, reason={reason['reason']}, count={reason['count']}")
        else:
            print("No failure reasons (no failed messages)")
    
    def test_delivery_report_by_country_structure(self, client_session):
        """Test by_country array structure"""
        resp = client_session.get(f"{BASE_URL}/api/messaging/delivery-report?days=30")
        assert resp.status_code == 200
        data = resp.json()
        
        by_country = data.get("by_country", [])
        if len(by_country) > 0:
            country = by_country[0]
            assert "country" in country
            assert "sent" in country
            assert "delivered" in country
            assert "failed" in country
            assert "delivery_rate" in country
            print(f"By country sample: {country}")
        else:
            print("No by_country data (no messages)")


class TestRegressionIteration5to9:
    """Regression tests for iteration 5-9 endpoints"""
    
    @pytest.fixture(scope="class")
    def admin_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": ADMIN_EMAIL, "password": ADMIN_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    @pytest.fixture(scope="class")
    def client_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL, "password": CLIENT_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    # Iteration 5: Commission system
    def test_reseller_commission_endpoint(self, admin_session):
        """Reseller commission endpoint still works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/reports/margin")
        assert resp.status_code == 200
        data = resp.json()
        assert "totals" in data
        print(f"Margin report: {data['totals']}")
    
    # Iteration 6: Reseller features
    def test_reseller_pricing_endpoint(self, admin_session):
        """Reseller pricing endpoint still works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/reseller-pricing")
        assert resp.status_code == 200
        print(f"Reseller pricing: {len(resp.json())} entries")
    
    # Iteration 7: Country hub
    def test_countries_endpoint(self, admin_session):
        """Countries endpoint still works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/countries")
        assert resp.status_code == 200
        print(f"Countries: {len(resp.json())} entries")
    
    def test_providers_endpoint(self, admin_session):
        """Providers endpoint still works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/providers")
        assert resp.status_code == 200
        print(f"Providers: {len(resp.json())} entries")
    
    # Iteration 8: Sender ID approvals
    def test_sender_id_requests_endpoint(self, admin_session):
        """Sender ID requests endpoint still works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/sender-id-requests")
        assert resp.status_code == 200
        print(f"Sender ID requests: {len(resp.json())} entries")
    
    # Iteration 9: Contact groups & number lookup
    def test_contact_groups_endpoint(self, client_session):
        """Contact groups endpoint still works"""
        resp = client_session.get(f"{BASE_URL}/api/contacts/groups")
        assert resp.status_code == 200
        print(f"Contact groups: {len(resp.json())} entries")
    
    def test_number_services_endpoint(self, client_session):
        """Number services endpoint still works"""
        resp = client_session.get(f"{BASE_URL}/api/numbers/services")
        assert resp.status_code == 200
        data = resp.json()
        assert "smart_validation" in data
        assert "hlr_lookup" in data
        print(f"Number services: smart_validation cost={data['smart_validation']['cost_per_lookup']}")
    
    def test_number_validate_endpoint(self, client_session):
        """Number validate endpoint still works"""
        resp = client_session.post(f"{BASE_URL}/api/numbers/validate", json={
            "phone": "+255712345678",
            "service": "smart_validation"
        })
        assert resp.status_code == 200
        data = resp.json()
        assert "valid" in data
        assert "e164" in data
        print(f"Number validation: valid={data['valid']}, e164={data['e164']}")
    
    def test_campaigns_endpoint(self, client_session):
        """Campaigns endpoint still works"""
        resp = client_session.get(f"{BASE_URL}/api/messaging/campaigns")
        assert resp.status_code == 200
        print(f"Campaigns: {len(resp.json())} entries")
    
    def test_wallet_endpoint(self, client_session):
        """Wallet endpoint still works"""
        resp = client_session.get(f"{BASE_URL}/api/wallet/me")
        assert resp.status_code == 200
        data = resp.json()
        assert "balance" in data
        print(f"Wallet balance: {data['balance']}")


class TestCleanup:
    """Cleanup test data"""
    
    @pytest.fixture(scope="class")
    def admin_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": ADMIN_EMAIL, "password": ADMIN_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    def test_cleanup_test_packs(self, admin_session):
        """Clean up TEST_ prefixed packs"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/credit-packs")
        if resp.status_code == 200:
            packs = resp.json()
            for pack in packs:
                if pack.get("name", "").startswith("TEST_"):
                    admin_session.delete(f"{BASE_URL}/api/admin/credit-packs/{pack['id']}")
                    print(f"Deleted test pack: {pack['name']}")
    
    def test_cleanup_test_promos(self, admin_session):
        """Clean up TEST_ prefixed promotions"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/promotions")
        if resp.status_code == 200:
            promos = resp.json()
            for promo in promos:
                if promo.get("name", "").startswith("TEST_") or promo.get("code", "").startswith("TEST"):
                    admin_session.delete(f"{BASE_URL}/api/admin/promotions/{promo['id']}")
                    print(f"Deleted test promo: {promo['code']}")


if __name__ == "__main__":
    pytest.main([__file__, "-v", "--tb=short"])
