"""
Iteration 12 Backend Tests:
- REGRESSION: Verify all migrated routers (banks, topups, messaging_extras) still work
- NEW: Settings Hub seeds new tunable keys (messaging.preflight_*, topups.max_proof_kb)
- NEW: Tunable behavior tests (amber threshold, max_proof_kb enforcement)
- REGRESSION: Other core endpoints (auth, credits/packs, admin/approvals/inbox)
"""
import pytest
import requests
import os
import base64

BASE_URL = os.environ.get("REACT_APP_BACKEND_URL", "").rstrip("/")

# Test credentials
ADMIN_EMAIL = "admin@unitxt.io"
ADMIN_PASSWORD = "Admin@2026"
CLIENT_EMAIL = "client@unitxt.io"
CLIENT_PASSWORD = "Client@2026"

# Small base64 proof image for testing (~100 bytes)
SMALL_PROOF_IMAGE = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=="


class TestSetup:
    """Setup and authentication tests"""
    
    admin_cookies = None
    client_cookies = None
    
    def test_01_admin_login(self):
        """Admin login and get cookies"""
        response = requests.post(f"{BASE_URL}/api/auth/login", json={
            "email": ADMIN_EMAIL,
            "password": ADMIN_PASSWORD
        })
        assert response.status_code == 200, f"Admin login failed: {response.text}"
        TestSetup.admin_cookies = response.cookies
        data = response.json()
        assert "user" in data
        assert data["user"]["role"] == "super_admin"
        print(f"✓ Admin login successful: {data['user']['email']}")
    
    def test_02_client_login(self):
        """Client login and get cookies"""
        response = requests.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL,
            "password": CLIENT_PASSWORD
        })
        assert response.status_code == 200, f"Client login failed: {response.text}"
        TestSetup.client_cookies = response.cookies
        data = response.json()
        assert "user" in data
        assert data["user"]["role"] == "client"
        assert data["user"]["country"] == "TZ"
        print(f"✓ Client login successful: {data['user']['email']} (country: {data['user']['country']})")


class TestNewTunableSettings:
    """Test that new tunable keys are seeded in Settings Hub"""
    
    def test_01_get_all_settings(self):
        """GET /api/admin/settings returns all settings"""
        response = requests.get(f"{BASE_URL}/api/admin/settings",
                                cookies=TestSetup.admin_cookies)
        assert response.status_code == 200, f"Failed: {response.text}"
        settings = response.json()
        assert isinstance(settings, list)
        TestNewTunableSettings.all_settings = {s["key"]: s for s in settings}
        print(f"✓ Found {len(settings)} settings")
    
    def test_02_preflight_free_sample_seeded(self):
        """messaging.preflight_free_sample is seeded with default 20"""
        key = "messaging.preflight_free_sample"
        assert key in TestNewTunableSettings.all_settings, f"Missing setting: {key}"
        setting = TestNewTunableSettings.all_settings[key]
        assert setting["value"] == 20, f"Expected default 20, got {setting['value']}"
        print(f"✓ {key} = {setting['value']} (default 20)")
    
    def test_03_preflight_red_threshold_seeded(self):
        """messaging.preflight_red_threshold is seeded with default 50"""
        key = "messaging.preflight_red_threshold"
        assert key in TestNewTunableSettings.all_settings, f"Missing setting: {key}"
        setting = TestNewTunableSettings.all_settings[key]
        assert setting["value"] == 50, f"Expected default 50, got {setting['value']}"
        print(f"✓ {key} = {setting['value']} (default 50)")
    
    def test_04_preflight_amber_threshold_seeded(self):
        """messaging.preflight_amber_threshold is seeded with default 80"""
        key = "messaging.preflight_amber_threshold"
        assert key in TestNewTunableSettings.all_settings, f"Missing setting: {key}"
        setting = TestNewTunableSettings.all_settings[key]
        assert setting["value"] == 80, f"Expected default 80, got {setting['value']}"
        print(f"✓ {key} = {setting['value']} (default 80)")
    
    def test_05_max_proof_kb_seeded(self):
        """topups.max_proof_kb is seeded with default 4096"""
        key = "topups.max_proof_kb"
        assert key in TestNewTunableSettings.all_settings, f"Missing setting: {key}"
        setting = TestNewTunableSettings.all_settings[key]
        assert setting["value"] == 4096, f"Expected default 4096, got {setting['value']}"
        print(f"✓ {key} = {setting['value']} (default 4096)")
    
    def test_06_update_amber_threshold(self):
        """PUT /api/admin/settings can update messaging.preflight_amber_threshold"""
        response = requests.put(f"{BASE_URL}/api/admin/settings",
                                cookies=TestSetup.admin_cookies,
                                json={
                                    "key": "messaging.preflight_amber_threshold",
                                    "value": 100,
                                    "category": "queue"
                                })
        assert response.status_code == 200, f"Failed: {response.text}"
        print(f"✓ Updated messaging.preflight_amber_threshold to 100")
    
    def test_07_verify_amber_threshold_updated(self):
        """Verify the amber threshold was updated"""
        response = requests.get(f"{BASE_URL}/api/admin/settings",
                                cookies=TestSetup.admin_cookies)
        assert response.status_code == 200
        settings = {s["key"]: s for s in response.json()}
        assert settings["messaging.preflight_amber_threshold"]["value"] == 100
        print(f"✓ Verified amber threshold is now 100")


class TestTunableBehavior:
    """Test that tunable settings affect runtime behavior"""
    
    def test_01_preflight_with_high_amber_threshold(self):
        """With amber_threshold=100, mostly-valid sample should show warning"""
        # First, run preflight with mostly valid numbers
        response = requests.post(f"{BASE_URL}/api/messaging/preflight",
                                 cookies=TestSetup.client_cookies,
                                 json={
                                     "phones": ["+255712345678", "+255712345679", "+255712345680",
                                                "+255712345681", "+255712345682"],
                                     "sample_size": 20
                                 })
        assert response.status_code == 200, f"Failed: {response.text}"
        data = response.json()
        # With amber_threshold=100, even 80-99% valid should trigger warning
        # Since our numbers are mostly valid (TZ format), valid_pct should be high
        print(f"✓ Preflight result: valid_pct={data['valid_pct']}%, warning={data.get('warning') is not None}")
        # If valid_pct < 100, warning should be set (since amber_threshold is 100)
        if data["valid_pct"] < 100:
            assert data.get("warning") is not None, \
                f"With amber_threshold=100, valid_pct={data['valid_pct']}% should trigger warning"
            print(f"✓ Warning triggered as expected: {data['warning'][:60]}...")
    
    def test_02_reset_amber_threshold(self):
        """Reset amber threshold back to 80"""
        response = requests.put(f"{BASE_URL}/api/admin/settings",
                                cookies=TestSetup.admin_cookies,
                                json={
                                    "key": "messaging.preflight_amber_threshold",
                                    "value": 80,
                                    "category": "queue"
                                })
        assert response.status_code == 200
        print(f"✓ Reset amber threshold to 80")
    
    def test_03_update_max_proof_kb_to_small(self):
        """Update topups.max_proof_kb to 0.1 (100 bytes) for testing"""
        response = requests.put(f"{BASE_URL}/api/admin/settings",
                                cookies=TestSetup.admin_cookies,
                                json={
                                    "key": "topups.max_proof_kb",
                                    "value": 0.1,  # 100 bytes
                                    "category": "compliance"
                                })
        assert response.status_code == 200
        print(f"✓ Updated max_proof_kb to 0.1 KB (100 bytes)")
    
    def test_04_topup_with_large_proof_rejected(self):
        """Submitting proof larger than max_proof_kb returns 400"""
        # Get a pack and bank first
        response = requests.get(f"{BASE_URL}/api/credits/packs", cookies=TestSetup.client_cookies)
        packs = response.json()
        response = requests.get(f"{BASE_URL}/api/banks", cookies=TestSetup.client_cookies)
        banks = response.json()
        
        if not packs or not banks:
            pytest.skip("No packs or banks available")
        
        # Create a proof image larger than 100 bytes
        # Our SMALL_PROOF_IMAGE is ~100 bytes, let's make it bigger
        large_proof = "data:image/png;base64," + base64.b64encode(b"x" * 200).decode()
        
        response = requests.post(f"{BASE_URL}/api/wallet/topups",
                                 cookies=TestSetup.client_cookies,
                                 json={
                                     "pack_id": packs[0]["id"],
                                     "method": "bank_transfer",
                                     "bank_id": banks[0]["id"],
                                     "reference": "TEST_LARGE_PROOF",
                                     "proof_image": large_proof
                                 })
        assert response.status_code == 400, f"Expected 400 for large proof, got {response.status_code}"
        # Error message should mention the configured limit
        error_msg = response.json().get("detail", "")
        assert "too big" in error_msg.lower() or "max" in error_msg.lower(), \
            f"Error should mention size limit: {error_msg}"
        print(f"✓ Large proof rejected with 400: {error_msg}")
    
    def test_05_reset_max_proof_kb(self):
        """Reset max_proof_kb back to 4096"""
        response = requests.put(f"{BASE_URL}/api/admin/settings",
                                cookies=TestSetup.admin_cookies,
                                json={
                                    "key": "topups.max_proof_kb",
                                    "value": 4096,
                                    "category": "compliance"
                                })
        assert response.status_code == 200
        print(f"✓ Reset max_proof_kb to 4096")


class TestRegressionOtherEndpoints:
    """Regression tests for other core endpoints"""
    
    def test_01_auth_me(self):
        """GET /api/auth/me returns user and wallet"""
        response = requests.get(f"{BASE_URL}/api/auth/me",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200, f"Failed: {response.text}"
        data = response.json()
        assert "user" in data
        assert "wallet" in data
        assert data["user"]["email"] == CLIENT_EMAIL
        print(f"✓ /api/auth/me works: {data['user']['email']}")
    
    def test_02_credits_packs_local_currency(self):
        """GET /api/credits/packs returns packs with local currency for TZ client"""
        response = requests.get(f"{BASE_URL}/api/credits/packs",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200, f"Failed: {response.text}"
        packs = response.json()
        assert len(packs) > 0, "Should have at least one pack"
        # TZ client should see TZS prices
        for pack in packs:
            if pack.get("local_price"):
                assert pack.get("local_currency") == "TZS", \
                    f"TZ client should see TZS, got {pack.get('local_currency')}"
        print(f"✓ /api/credits/packs returns {len(packs)} packs with local currency")
    
    def test_03_admin_approvals_inbox(self):
        """GET /api/admin/approvals/inbox includes topup_requests with count in total"""
        response = requests.get(f"{BASE_URL}/api/admin/approvals/inbox",
                                cookies=TestSetup.admin_cookies)
        assert response.status_code == 200, f"Failed: {response.text}"
        data = response.json()
        assert "topup_requests" in data, "Should include topup_requests"
        assert "total" in data, "Should include total count"
        print(f"✓ /api/admin/approvals/inbox: {len(data['topup_requests'])} topup requests, total={data['total']}")


class TestRegressionMigratedRouters:
    """Regression tests for migrated routers (banks, topups, messaging_extras)"""
    
    def test_01_admin_banks_crud(self):
        """GET/POST/PUT/DELETE /api/admin/banks still work"""
        # GET
        response = requests.get(f"{BASE_URL}/api/admin/banks",
                                cookies=TestSetup.admin_cookies)
        assert response.status_code == 200
        banks = response.json()
        print(f"✓ GET /api/admin/banks: {len(banks)} banks")
        
        # POST
        response = requests.post(f"{BASE_URL}/api/admin/banks",
                                 cookies=TestSetup.admin_cookies,
                                 json={
                                     "country": "ug",
                                     "bank_name": "TEST_Iteration12_Bank",
                                     "account_name": "Test Account",
                                     "account_number": "9999999999",
                                     "active": True
                                 })
        assert response.status_code == 200
        bank_id = response.json()["id"]
        assert response.json()["country"] == "UG"  # uppercased
        print(f"✓ POST /api/admin/banks: created {bank_id}")
        
        # PUT
        response = requests.put(f"{BASE_URL}/api/admin/banks/{bank_id}",
                                cookies=TestSetup.admin_cookies,
                                json={
                                    "country": "ug",
                                    "bank_name": "TEST_Iteration12_Bank_Updated",
                                    "account_name": "Test Account",
                                    "account_number": "9999999999",
                                    "active": True
                                })
        assert response.status_code == 200
        print(f"✓ PUT /api/admin/banks/{bank_id}: updated")
        
        # DELETE
        response = requests.delete(f"{BASE_URL}/api/admin/banks/{bank_id}",
                                   cookies=TestSetup.admin_cookies)
        assert response.status_code == 200
        print(f"✓ DELETE /api/admin/banks/{bank_id}: deleted")
    
    def test_02_client_banks(self):
        """GET /api/banks (client) returns scoped banks"""
        response = requests.get(f"{BASE_URL}/api/banks",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200
        banks = response.json()
        for bank in banks:
            assert bank.get("country") in ["TZ", "*"]
            assert bank.get("active") == True
        print(f"✓ GET /api/banks: {len(banks)} banks for TZ client")
    
    def test_03_topup_flow(self):
        """POST/GET /api/wallet/topups still work"""
        # Get pack and bank
        response = requests.get(f"{BASE_URL}/api/credits/packs", cookies=TestSetup.client_cookies)
        packs = response.json()
        response = requests.get(f"{BASE_URL}/api/banks", cookies=TestSetup.client_cookies)
        banks = response.json()
        
        if not packs or not banks:
            pytest.skip("No packs or banks")
        
        # POST topup
        response = requests.post(f"{BASE_URL}/api/wallet/topups",
                                 cookies=TestSetup.client_cookies,
                                 json={
                                     "pack_id": packs[0]["id"],
                                     "method": "bank_transfer",
                                     "bank_id": banks[0]["id"],
                                     "reference": "TEST_ITER12_REF",
                                     "proof_image": SMALL_PROOF_IMAGE
                                 })
        assert response.status_code == 200
        topup_id = response.json()["id"]
        print(f"✓ POST /api/wallet/topups: created {topup_id}")
        
        # GET topups
        response = requests.get(f"{BASE_URL}/api/wallet/topups",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200
        topups = response.json()
        assert any(t["id"] == topup_id for t in topups)
        print(f"✓ GET /api/wallet/topups: {len(topups)} topups")
        
        # GET proof
        response = requests.get(f"{BASE_URL}/api/wallet/topups/{topup_id}/proof",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200
        assert "proof_image" in response.json()
        print(f"✓ GET /api/wallet/topups/{topup_id}/proof: retrieved")
        
        # Store for admin tests
        TestRegressionMigratedRouters.test_topup_id = topup_id
    
    def test_04_admin_topups(self):
        """GET /api/admin/topups and detail still work"""
        # List
        response = requests.get(f"{BASE_URL}/api/admin/topups",
                                cookies=TestSetup.admin_cookies)
        assert response.status_code == 200
        topups = response.json()
        print(f"✓ GET /api/admin/topups: {len(topups)} topups")
        
        # Detail with proof
        tid = TestRegressionMigratedRouters.test_topup_id
        response = requests.get(f"{BASE_URL}/api/admin/topups/{tid}",
                                cookies=TestSetup.admin_cookies)
        assert response.status_code == 200
        assert "proof_image" in response.json()
        print(f"✓ GET /api/admin/topups/{tid}: includes proof_image")
    
    def test_05_admin_topup_review(self):
        """POST /api/admin/topups/{id}/review still works"""
        tid = TestRegressionMigratedRouters.test_topup_id
        response = requests.post(f"{BASE_URL}/api/admin/topups/{tid}/review",
                                 cookies=TestSetup.admin_cookies,
                                 json={"status": "rejected", "note": "Test rejection iter12"})
        assert response.status_code == 200
        print(f"✓ POST /api/admin/topups/{tid}/review: rejected")
    
    def test_06_preflight(self):
        """POST /api/messaging/preflight still works (FREE)"""
        response = requests.post(f"{BASE_URL}/api/messaging/preflight",
                                 cookies=TestSetup.client_cookies,
                                 json={
                                     "phones": ["+255712345678", "+255712345679"],
                                     "sample_size": 20
                                 })
        assert response.status_code == 200
        data = response.json()
        assert "checked" in data
        assert "valid_pct" in data
        assert "buckets" in data
        assert data.get("free") == True
        print(f"✓ POST /api/messaging/preflight: checked={data['checked']}, free={data['free']}")
    
    def test_07_csv_mappings_crud(self):
        """GET/POST/DELETE /api/messaging/csv-mappings still work"""
        # POST
        response = requests.post(f"{BASE_URL}/api/messaging/csv-mappings",
                                 cookies=TestSetup.client_cookies,
                                 json={
                                     "name": "TEST_Iter12_Mapping",
                                     "phone_column": "Phone",
                                     "column_renames": {"Name": "name"}
                                 })
        assert response.status_code == 200
        mapping_id = response.json()["id"]
        print(f"✓ POST /api/messaging/csv-mappings: created {mapping_id}")
        
        # GET
        response = requests.get(f"{BASE_URL}/api/messaging/csv-mappings",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200
        mappings = response.json()
        assert any(m["id"] == mapping_id for m in mappings)
        print(f"✓ GET /api/messaging/csv-mappings: {len(mappings)} mappings")
        
        # POST used
        response = requests.post(f"{BASE_URL}/api/messaging/csv-mappings/{mapping_id}/used",
                                 cookies=TestSetup.client_cookies)
        assert response.status_code == 200
        print(f"✓ POST /api/messaging/csv-mappings/{mapping_id}/used: marked")
        
        # DELETE
        response = requests.delete(f"{BASE_URL}/api/messaging/csv-mappings/{mapping_id}",
                                   cookies=TestSetup.client_cookies)
        assert response.status_code == 200
        print(f"✓ DELETE /api/messaging/csv-mappings/{mapping_id}: deleted")


class TestCleanup:
    """Cleanup test data"""
    
    def test_cleanup_test_banks(self):
        """Remove any TEST_ prefixed banks"""
        response = requests.get(f"{BASE_URL}/api/admin/banks",
                                cookies=TestSetup.admin_cookies)
        if response.status_code == 200:
            banks = response.json()
            for bank in banks:
                if "TEST_" in bank.get("bank_name", ""):
                    requests.delete(f"{BASE_URL}/api/admin/banks/{bank['id']}",
                                    cookies=TestSetup.admin_cookies)
                    print(f"  Cleaned up bank: {bank['bank_name']}")
        print("✓ Cleanup complete")


if __name__ == "__main__":
    pytest.main([__file__, "-v", "--tb=short"])
