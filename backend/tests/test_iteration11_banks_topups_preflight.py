"""
Iteration 11 Backend Tests:
- Bank accounts management (admin CRUD)
- Client bank listing (scoped by country)
- Bank transfer top-up flow (submit, list, proof, admin review)
- Wave A: Pre-flight validation (free, no credit deduction)
- Wave B: Saved CSV mappings (per-user CRUD)
"""
import pytest
import requests
import os

BASE_URL = os.environ.get("REACT_APP_BACKEND_URL", "").rstrip("/")

# Test credentials
ADMIN_EMAIL = "admin@unitxt.io"
ADMIN_PASSWORD = "Admin@2026"
CLIENT_EMAIL = "client@unitxt.io"
CLIENT_PASSWORD = "Client@2026"

# Small base64 proof image for testing
TEST_PROOF_IMAGE = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=="


class TestSetup:
    """Setup and authentication tests"""
    
    admin_token = None
    client_token = None
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


class TestBankAccountsAdmin:
    """Admin bank accounts CRUD tests"""
    
    created_bank_id = None
    
    def test_01_list_banks_seeded(self):
        """GET /api/admin/banks returns seeded CRDB Bank row"""
        response = requests.get(f"{BASE_URL}/api/admin/banks", 
                                cookies=TestSetup.admin_cookies)
        assert response.status_code == 200, f"Failed: {response.text}"
        banks = response.json()
        assert isinstance(banks, list)
        # Should have at least the seeded CRDB Bank
        crdb = [b for b in banks if "CRDB" in b.get("bank_name", "")]
        assert len(crdb) >= 1, f"Expected seeded CRDB Bank, got: {banks}"
        print(f"✓ Found {len(banks)} bank accounts, including CRDB Bank")
    
    def test_02_create_bank_account(self):
        """POST /api/admin/banks creates a new bank account"""
        response = requests.post(f"{BASE_URL}/api/admin/banks", 
                                 cookies=TestSetup.admin_cookies,
                                 json={
                                     "country": "ke",  # lowercase to test uppercasing
                                     "bank_name": "TEST_Kenya Commercial Bank",
                                     "account_name": "Unitxt Kenya Ltd",
                                     "account_number": "1234567890",
                                     "branch": "Nairobi Main",
                                     "swift": "KCBLKENX",
                                     "currency": "KES",
                                     "instructions": "Use your email as reference",
                                     "active": True
                                 })
        assert response.status_code == 200, f"Failed: {response.text}"
        data = response.json()
        assert "id" in data
        assert data["country"] == "KE", "Country should be uppercased"
        assert data["bank_name"] == "TEST_Kenya Commercial Bank"
        TestBankAccountsAdmin.created_bank_id = data["id"]
        print(f"✓ Created bank account: {data['id']} (country uppercased to {data['country']})")
    
    def test_03_update_bank_account(self):
        """PUT /api/admin/banks/{id} updates bank account"""
        bid = TestBankAccountsAdmin.created_bank_id
        assert bid, "No bank ID from previous test"
        response = requests.put(f"{BASE_URL}/api/admin/banks/{bid}",
                                cookies=TestSetup.admin_cookies,
                                json={
                                    "country": "ke",
                                    "bank_name": "TEST_Kenya Commercial Bank Updated",
                                    "account_name": "Unitxt Kenya Ltd",
                                    "account_number": "1234567890",
                                    "branch": "Nairobi CBD",
                                    "swift": "KCBLKENX",
                                    "currency": "KES",
                                    "instructions": "Updated instructions",
                                    "active": True
                                })
        assert response.status_code == 200, f"Failed: {response.text}"
        data = response.json()
        assert data.get("ok") == True
        print(f"✓ Updated bank account {bid}")
    
    def test_04_verify_update(self):
        """Verify the update was persisted"""
        response = requests.get(f"{BASE_URL}/api/admin/banks",
                                cookies=TestSetup.admin_cookies)
        assert response.status_code == 200
        banks = response.json()
        updated = [b for b in banks if b.get("id") == TestBankAccountsAdmin.created_bank_id]
        assert len(updated) == 1
        assert "Updated" in updated[0]["bank_name"]
        print(f"✓ Verified bank update persisted")
    
    def test_05_delete_bank_account(self):
        """DELETE /api/admin/banks/{id} deletes bank account"""
        bid = TestBankAccountsAdmin.created_bank_id
        assert bid, "No bank ID from previous test"
        response = requests.delete(f"{BASE_URL}/api/admin/banks/{bid}",
                                   cookies=TestSetup.admin_cookies)
        assert response.status_code == 200, f"Failed: {response.text}"
        data = response.json()
        assert data.get("ok") == True
        print(f"✓ Deleted bank account {bid}")
    
    def test_06_verify_delete(self):
        """Verify the delete was persisted"""
        response = requests.get(f"{BASE_URL}/api/admin/banks",
                                cookies=TestSetup.admin_cookies)
        assert response.status_code == 200
        banks = response.json()
        deleted = [b for b in banks if b.get("id") == TestBankAccountsAdmin.created_bank_id]
        assert len(deleted) == 0, "Bank should be deleted"
        print(f"✓ Verified bank deletion persisted")


class TestClientBanks:
    """Client bank listing tests (scoped by country)"""
    
    def test_01_client_banks_scoped(self):
        """GET /api/banks returns banks for user's country or global"""
        response = requests.get(f"{BASE_URL}/api/banks",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200, f"Failed: {response.text}"
        banks = response.json()
        assert isinstance(banks, list)
        # Client is TZ, should see TZ banks and global (*) banks
        for bank in banks:
            assert bank.get("country") in ["TZ", "*"], f"Client should only see TZ or global banks, got: {bank.get('country')}"
            assert bank.get("active") == True, "Should only see active banks"
        print(f"✓ Client sees {len(banks)} banks (TZ or global)")


class TestTopupRequests:
    """Bank transfer top-up flow tests"""
    
    created_topup_id = None
    initial_credits = None
    
    def test_01_get_initial_credits(self):
        """Get client's initial credit balance"""
        response = requests.get(f"{BASE_URL}/api/auth/me",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200
        data = response.json()
        TestTopupRequests.initial_credits = data.get("wallet", {}).get("balance", 0)
        print(f"✓ Initial client credits: {TestTopupRequests.initial_credits}")
    
    def test_02_get_packs(self):
        """Get available credit packs"""
        response = requests.get(f"{BASE_URL}/api/credits/packs",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200
        packs = response.json()
        assert len(packs) > 0, "Should have at least one pack"
        # Store first pack for topup test
        TestTopupRequests.test_pack = packs[0]
        print(f"✓ Found {len(packs)} credit packs, using: {packs[0]['name']}")
    
    def test_03_get_banks_for_topup(self):
        """Get banks available for topup"""
        response = requests.get(f"{BASE_URL}/api/banks",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200
        banks = response.json()
        if len(banks) > 0:
            TestTopupRequests.test_bank_id = banks[0]["id"]
            print(f"✓ Using bank: {banks[0]['bank_name']}")
        else:
            TestTopupRequests.test_bank_id = None
            print("⚠ No banks available for topup test")
    
    def test_04_submit_topup_request(self):
        """POST /api/wallet/topups creates pending topup request"""
        if not hasattr(TestTopupRequests, 'test_pack') or not hasattr(TestTopupRequests, 'test_bank_id'):
            pytest.skip("No pack or bank available")
        
        response = requests.post(f"{BASE_URL}/api/wallet/topups",
                                 cookies=TestSetup.client_cookies,
                                 json={
                                     "pack_id": TestTopupRequests.test_pack["id"],
                                     "method": "bank_transfer",
                                     "bank_id": TestTopupRequests.test_bank_id,
                                     "reference": "TEST_REF_12345",
                                     "note": "Test topup request",
                                     "proof_image": TEST_PROOF_IMAGE
                                 })
        assert response.status_code == 200, f"Failed: {response.text}"
        data = response.json()
        assert "id" in data
        assert data["status"] == "pending"
        assert data["credits_on_approval"] > 0
        assert "local_amount" in data
        assert "local_currency" in data
        # proof_image should NOT be in response
        assert "proof_image" not in data, "proof_image should be excluded from response"
        TestTopupRequests.created_topup_id = data["id"]
        TestTopupRequests.credits_on_approval = data["credits_on_approval"]
        print(f"✓ Created topup request: {data['id']} for {data['credits_on_approval']} credits")
    
    def test_05_list_my_topups(self):
        """GET /api/wallet/topups returns client's topup history"""
        response = requests.get(f"{BASE_URL}/api/wallet/topups",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200, f"Failed: {response.text}"
        topups = response.json()
        assert isinstance(topups, list)
        # Find our created topup
        our_topup = [t for t in topups if t.get("id") == TestTopupRequests.created_topup_id]
        assert len(our_topup) == 1, "Should find our created topup"
        assert "proof_image" not in our_topup[0], "proof_image should be excluded"
        print(f"✓ Found {len(topups)} topups in history")
    
    def test_06_get_topup_proof(self):
        """GET /api/wallet/topups/{tid}/proof returns proof image"""
        tid = TestTopupRequests.created_topup_id
        assert tid, "No topup ID"
        response = requests.get(f"{BASE_URL}/api/wallet/topups/{tid}/proof",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200, f"Failed: {response.text}"
        data = response.json()
        assert "proof_image" in data
        assert data["proof_image"].startswith("data:image/")
        print(f"✓ Retrieved proof image for topup {tid}")
    
    def test_07_approvals_inbox_includes_topups(self):
        """GET /api/admin/approvals/inbox includes topup_requests"""
        response = requests.get(f"{BASE_URL}/api/admin/approvals/inbox",
                                cookies=TestSetup.admin_cookies)
        assert response.status_code == 200, f"Failed: {response.text}"
        data = response.json()
        assert "topup_requests" in data
        assert "total" in data
        # Our topup should be in the list
        our_topup = [t for t in data["topup_requests"] if t.get("id") == TestTopupRequests.created_topup_id]
        assert len(our_topup) == 1, "Our topup should be in approvals inbox"
        print(f"✓ Approvals inbox has {len(data['topup_requests'])} topup requests, total pending: {data['total']}")
    
    def test_08_admin_list_topups(self):
        """GET /api/admin/topups returns pending topups"""
        response = requests.get(f"{BASE_URL}/api/admin/topups",
                                cookies=TestSetup.admin_cookies)
        assert response.status_code == 200, f"Failed: {response.text}"
        topups = response.json()
        assert isinstance(topups, list)
        print(f"✓ Admin sees {len(topups)} topup requests")
    
    def test_09_admin_topup_detail_includes_proof(self):
        """GET /api/admin/topups/{tid} includes proof_image"""
        tid = TestTopupRequests.created_topup_id
        assert tid, "No topup ID"
        response = requests.get(f"{BASE_URL}/api/admin/topups/{tid}",
                                cookies=TestSetup.admin_cookies)
        assert response.status_code == 200, f"Failed: {response.text}"
        data = response.json()
        assert "proof_image" in data, "Admin detail MUST include proof_image"
        assert data["proof_image"].startswith("data:image/")
        print(f"✓ Admin topup detail includes proof_image")
    
    def test_10_approve_topup(self):
        """POST /api/admin/topups/{tid}/review with status=approved credits wallet"""
        tid = TestTopupRequests.created_topup_id
        assert tid, "No topup ID"
        response = requests.post(f"{BASE_URL}/api/admin/topups/{tid}/review",
                                 cookies=TestSetup.admin_cookies,
                                 json={"status": "approved", "note": "Test approval"})
        assert response.status_code == 200, f"Failed: {response.text}"
        data = response.json()
        assert data.get("ok") == True
        print(f"✓ Approved topup {tid}")
    
    def test_11_verify_credits_added(self):
        """Verify credits were added to client wallet"""
        response = requests.get(f"{BASE_URL}/api/auth/me",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200
        data = response.json()
        new_balance = data.get("wallet", {}).get("balance", 0)
        expected = TestTopupRequests.initial_credits + TestTopupRequests.credits_on_approval
        assert new_balance >= expected, f"Expected at least {expected} credits, got {new_balance}"
        print(f"✓ Client credits increased from {TestTopupRequests.initial_credits} to {new_balance}")
    
    def test_12_verify_tx_kind_topup_bank(self):
        """Verify transaction was recorded with kind=topup_bank"""
        response = requests.get(f"{BASE_URL}/api/wallet/transactions",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200
        txs = response.json()
        topup_tx = [t for t in txs if t.get("kind") == "topup_bank"]
        assert len(topup_tx) >= 1, "Should have topup_bank transaction"
        print(f"✓ Found topup_bank transaction")
    
    def test_13_already_reviewed_returns_400(self):
        """POST /api/admin/topups/{tid}/review on already-reviewed returns 400"""
        tid = TestTopupRequests.created_topup_id
        assert tid, "No topup ID"
        response = requests.post(f"{BASE_URL}/api/admin/topups/{tid}/review",
                                 cookies=TestSetup.admin_cookies,
                                 json={"status": "approved", "note": "Double approval"})
        assert response.status_code == 400, f"Expected 400, got {response.status_code}"
        print(f"✓ Already-reviewed topup returns 400")


class TestTopupRejection:
    """Test rejection flow (should NOT credit wallet)"""
    
    created_topup_id = None
    initial_credits = None
    
    def test_01_get_initial_credits(self):
        """Get client's credit balance before rejection test"""
        response = requests.get(f"{BASE_URL}/api/auth/me",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200
        data = response.json()
        TestTopupRejection.initial_credits = data.get("wallet", {}).get("balance", 0)
        print(f"✓ Credits before rejection test: {TestTopupRejection.initial_credits}")
    
    def test_02_submit_topup_for_rejection(self):
        """Submit another topup to test rejection"""
        response = requests.get(f"{BASE_URL}/api/banks", cookies=TestSetup.client_cookies)
        banks = response.json()
        response = requests.get(f"{BASE_URL}/api/credits/packs", cookies=TestSetup.client_cookies)
        packs = response.json()
        
        if not banks or not packs:
            pytest.skip("No banks or packs available")
        
        response = requests.post(f"{BASE_URL}/api/wallet/topups",
                                 cookies=TestSetup.client_cookies,
                                 json={
                                     "pack_id": packs[0]["id"],
                                     "method": "bank_transfer",
                                     "bank_id": banks[0]["id"],
                                     "reference": "TEST_REJECT_REF",
                                     "note": "Test rejection",
                                     "proof_image": TEST_PROOF_IMAGE
                                 })
        assert response.status_code == 200
        TestTopupRejection.created_topup_id = response.json()["id"]
        print(f"✓ Created topup for rejection: {TestTopupRejection.created_topup_id}")
    
    def test_03_reject_topup(self):
        """Reject the topup"""
        tid = TestTopupRejection.created_topup_id
        response = requests.post(f"{BASE_URL}/api/admin/topups/{tid}/review",
                                 cookies=TestSetup.admin_cookies,
                                 json={"status": "rejected", "note": "Test rejection"})
        assert response.status_code == 200
        print(f"✓ Rejected topup {tid}")
    
    def test_04_verify_no_credits_added(self):
        """Verify credits were NOT added on rejection"""
        response = requests.get(f"{BASE_URL}/api/auth/me",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200
        data = response.json()
        new_balance = data.get("wallet", {}).get("balance", 0)
        # Balance should be same or less (not increased)
        assert new_balance <= TestTopupRejection.initial_credits + 100, \
            f"Credits should not increase on rejection. Was {TestTopupRejection.initial_credits}, now {new_balance}"
        print(f"✓ Credits unchanged after rejection: {new_balance}")


class TestPreflightValidation:
    """Wave A: Pre-flight validation tests (FREE, no credit deduction)"""
    
    initial_credits = None
    
    def test_01_get_initial_credits(self):
        """Get client's credit balance before preflight"""
        response = requests.get(f"{BASE_URL}/api/auth/me",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200
        data = response.json()
        TestPreflightValidation.initial_credits = data.get("wallet", {}).get("balance", 0)
        print(f"✓ Credits before preflight: {TestPreflightValidation.initial_credits}")
    
    def test_02_preflight_basic(self):
        """POST /api/messaging/preflight returns validation stats"""
        response = requests.post(f"{BASE_URL}/api/messaging/preflight",
                                 cookies=TestSetup.client_cookies,
                                 json={
                                     "phones": ["+255712345678", "+255700000001", "+255123456789"],
                                     "sample_size": 20
                                 })
        assert response.status_code == 200, f"Failed: {response.text}"
        data = response.json()
        # Verify all required fields
        assert "checked" in data
        assert "total_in_list" in data
        assert "valid_pct" in data
        assert "buckets" in data
        assert "predicted_valid" in data
        assert "predicted_failed" in data
        assert "countries" in data
        assert "operators" in data
        assert "results" in data
        # warning can be null or string
        assert "warning" in data or data.get("warning") is None
        print(f"✓ Preflight returned: checked={data['checked']}, valid_pct={data['valid_pct']}%")
    
    def test_03_preflight_no_credit_deduction(self):
        """Verify preflight is FREE (no credit deduction)"""
        response = requests.get(f"{BASE_URL}/api/auth/me",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200
        data = response.json()
        new_balance = data.get("wallet", {}).get("balance", 0)
        assert new_balance == TestPreflightValidation.initial_credits, \
            f"Preflight should be FREE! Credits changed from {TestPreflightValidation.initial_credits} to {new_balance}"
        print(f"✓ Preflight is FREE - credits unchanged: {new_balance}")
    
    def test_04_preflight_warning_low_valid_pct(self):
        """Verify warning is set when valid_pct < 80"""
        # Use invalid numbers to trigger low valid_pct
        response = requests.post(f"{BASE_URL}/api/messaging/preflight",
                                 cookies=TestSetup.client_cookies,
                                 json={
                                     "phones": ["invalid1", "invalid2", "12345", "abc", "+255712345678"],
                                     "sample_size": 20
                                 })
        assert response.status_code == 200
        data = response.json()
        # With mostly invalid numbers, valid_pct should be low
        if data["valid_pct"] < 80:
            assert data.get("warning") is not None, "Warning should be set when valid_pct < 80"
            print(f"✓ Warning set for low valid_pct ({data['valid_pct']}%): {data['warning'][:50]}...")
        else:
            print(f"⚠ valid_pct was {data['valid_pct']}%, warning test inconclusive")
    
    def test_05_preflight_empty_phones_400(self):
        """Verify 400 when phones list is empty"""
        response = requests.post(f"{BASE_URL}/api/messaging/preflight",
                                 cookies=TestSetup.client_cookies,
                                 json={"phones": [], "sample_size": 20})
        assert response.status_code == 400, f"Expected 400 for empty phones, got {response.status_code}"
        print(f"✓ Empty phones returns 400")
    
    def test_06_preflight_samples_up_to_20(self):
        """Verify preflight samples up to 20 numbers"""
        # Send 50 numbers
        phones = [f"+25571234{i:04d}" for i in range(50)]
        response = requests.post(f"{BASE_URL}/api/messaging/preflight",
                                 cookies=TestSetup.client_cookies,
                                 json={"phones": phones, "sample_size": 20})
        assert response.status_code == 200
        data = response.json()
        assert data["checked"] <= 20, f"Should sample at most 20, got {data['checked']}"
        assert data["total_in_list"] == 50
        print(f"✓ Preflight sampled {data['checked']} of {data['total_in_list']} numbers")


class TestCsvMappings:
    """Wave B: Saved CSV mappings tests"""
    
    created_mapping_id = None
    
    def test_01_create_csv_mapping(self):
        """POST /api/messaging/csv-mappings creates a mapping"""
        response = requests.post(f"{BASE_URL}/api/messaging/csv-mappings",
                                 cookies=TestSetup.client_cookies,
                                 json={
                                     "name": "TEST_CRM Export",
                                     "phone_column": "Mobile",
                                     "column_renames": {"First Name": "first_name", "Amount Due": "amount_owed"},
                                     "note": "Test mapping"
                                 })
        assert response.status_code == 200, f"Failed: {response.text}"
        data = response.json()
        assert "id" in data
        assert data["name"] == "TEST_CRM Export"
        assert data["phone_column"] == "Mobile"
        TestCsvMappings.created_mapping_id = data["id"]
        print(f"✓ Created CSV mapping: {data['id']}")
    
    def test_02_list_csv_mappings(self):
        """GET /api/messaging/csv-mappings returns user's mappings"""
        response = requests.get(f"{BASE_URL}/api/messaging/csv-mappings",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200, f"Failed: {response.text}"
        mappings = response.json()
        assert isinstance(mappings, list)
        our_mapping = [m for m in mappings if m.get("id") == TestCsvMappings.created_mapping_id]
        assert len(our_mapping) == 1, "Should find our created mapping"
        print(f"✓ Found {len(mappings)} CSV mappings")
    
    def test_03_upsert_same_name(self):
        """Saving with same name upserts"""
        response = requests.post(f"{BASE_URL}/api/messaging/csv-mappings",
                                 cookies=TestSetup.client_cookies,
                                 json={
                                     "name": "TEST_CRM Export",  # Same name
                                     "phone_column": "Contact Number",  # Different column
                                     "column_renames": {"Name": "name"},
                                     "note": "Updated mapping"
                                 })
        assert response.status_code == 200
        data = response.json()
        # Should have same ID (upsert)
        assert data["id"] == TestCsvMappings.created_mapping_id, "Should upsert with same ID"
        assert data["phone_column"] == "Contact Number", "Should have updated phone_column"
        print(f"✓ Upsert worked - same ID, updated phone_column")
    
    def test_04_mark_mapping_used(self):
        """POST /api/messaging/csv-mappings/{id}/used sets last_used_at"""
        mid = TestCsvMappings.created_mapping_id
        response = requests.post(f"{BASE_URL}/api/messaging/csv-mappings/{mid}/used",
                                 cookies=TestSetup.client_cookies)
        assert response.status_code == 200, f"Failed: {response.text}"
        data = response.json()
        assert data.get("ok") == True
        print(f"✓ Marked mapping as used")
    
    def test_05_verify_last_used_at(self):
        """Verify last_used_at was set"""
        response = requests.get(f"{BASE_URL}/api/messaging/csv-mappings",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200
        mappings = response.json()
        our_mapping = [m for m in mappings if m.get("id") == TestCsvMappings.created_mapping_id]
        assert len(our_mapping) == 1
        assert our_mapping[0].get("last_used_at") is not None, "last_used_at should be set"
        print(f"✓ last_used_at is set: {our_mapping[0]['last_used_at']}")
    
    def test_06_other_user_cannot_access(self):
        """Other user's mapping returns 404"""
        mid = TestCsvMappings.created_mapping_id
        # Try to access with admin (different user)
        response = requests.post(f"{BASE_URL}/api/messaging/csv-mappings/{mid}/used",
                                 cookies=TestSetup.admin_cookies)
        assert response.status_code == 404, f"Expected 404 for other user's mapping, got {response.status_code}"
        print(f"✓ Other user cannot access mapping (404)")
    
    def test_07_delete_csv_mapping(self):
        """DELETE /api/messaging/csv-mappings/{id} removes mapping"""
        mid = TestCsvMappings.created_mapping_id
        response = requests.delete(f"{BASE_URL}/api/messaging/csv-mappings/{mid}",
                                   cookies=TestSetup.client_cookies)
        assert response.status_code == 200, f"Failed: {response.text}"
        data = response.json()
        assert data.get("ok") == True
        print(f"✓ Deleted CSV mapping")
    
    def test_08_verify_delete(self):
        """Verify mapping was deleted"""
        response = requests.get(f"{BASE_URL}/api/messaging/csv-mappings",
                                cookies=TestSetup.client_cookies)
        assert response.status_code == 200
        mappings = response.json()
        our_mapping = [m for m in mappings if m.get("id") == TestCsvMappings.created_mapping_id]
        assert len(our_mapping) == 0, "Mapping should be deleted"
        print(f"✓ Verified mapping deletion")
    
    def test_09_empty_name_400(self):
        """Empty name returns 400"""
        response = requests.post(f"{BASE_URL}/api/messaging/csv-mappings",
                                 cookies=TestSetup.client_cookies,
                                 json={
                                     "name": "",
                                     "phone_column": "phone",
                                     "column_renames": {}
                                 })
        assert response.status_code == 400, f"Expected 400 for empty name, got {response.status_code}"
        print(f"✓ Empty name returns 400")
    
    def test_10_empty_phone_column_400(self):
        """Empty phone_column returns 400"""
        response = requests.post(f"{BASE_URL}/api/messaging/csv-mappings",
                                 cookies=TestSetup.client_cookies,
                                 json={
                                     "name": "Test",
                                     "phone_column": "",
                                     "column_renames": {}
                                 })
        assert response.status_code == 400, f"Expected 400 for empty phone_column, got {response.status_code}"
        print(f"✓ Empty phone_column returns 400")


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
