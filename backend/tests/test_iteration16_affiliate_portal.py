"""
Iteration 16 - Affiliate Self-Service Portal Tests
Tests for:
1. Primary code rename (one-shot enforcement)
2. Code uniqueness checks
3. Code validation (length)
4. Auto-mint on reseller registration
5. Public attribution (referral_code on registration)
6. Commission recording on credits/buy
"""
import pytest
import requests
import os
import secrets

BASE_URL = os.environ.get('REACT_APP_BACKEND_URL', '').rstrip('/')

# Test credentials
RESELLER_EMAIL = "reseller@unitxt.io"
RESELLER_PASSWORD = "Reseller@2026"
ADMIN_EMAIL = "admin@unitxt.io"
ADMIN_PASSWORD = "Admin@2026"
CLIENT_EMAIL = "client@unitxt.io"
CLIENT_PASSWORD = "Client@2026"


@pytest.fixture(scope="module")
def session():
    """Shared requests session"""
    s = requests.Session()
    s.headers.update({"Content-Type": "application/json"})
    return s


@pytest.fixture(scope="module")
def reseller_session(session):
    """Login as reseller and return session with cookies"""
    resp = session.post(f"{BASE_URL}/api/auth/login", json={
        "email": RESELLER_EMAIL,
        "password": RESELLER_PASSWORD
    })
    assert resp.status_code == 200, f"Reseller login failed: {resp.text}"
    return session


@pytest.fixture(scope="module")
def admin_session():
    """Login as admin and return session with cookies"""
    s = requests.Session()
    s.headers.update({"Content-Type": "application/json"})
    resp = s.post(f"{BASE_URL}/api/auth/login", json={
        "email": ADMIN_EMAIL,
        "password": ADMIN_PASSWORD
    })
    assert resp.status_code == 200, f"Admin login failed: {resp.text}"
    return s


class TestPrimaryCodeRename:
    """Test primary code rename functionality"""
    
    def test_reseller_already_renamed_cannot_rename_again(self, reseller_session):
        """Demo reseller already has primary_code_renamed=true, should get 400"""
        # First check current state
        me_resp = reseller_session.get(f"{BASE_URL}/api/affiliate/me")
        assert me_resp.status_code == 200
        me_data = me_resp.json()
        print(f"Reseller me data: primary_code={me_data.get('primary_code')}, renamed={me_data.get('primary_code_renamed')}")
        
        # Try to rename - should fail since already renamed
        rename_resp = reseller_session.post(f"{BASE_URL}/api/affiliate/primary-code", json={
            "code": "NEWCODE123"
        })
        assert rename_resp.status_code == 400, f"Expected 400, got {rename_resp.status_code}: {rename_resp.text}"
        error_msg = rename_resp.json().get("detail", "")
        assert "only change your primary promo code once" in error_msg.lower(), f"Expected 'only change' message, got: {error_msg}"
        print(f"PASS: Second rename attempt correctly rejected with: {error_msg}")


class TestCodeValidation:
    """Test code validation rules"""
    
    def test_code_too_short(self):
        """Code < 3 chars should return 400"""
        # Create a fresh reseller for this test
        s = requests.Session()
        s.headers.update({"Content-Type": "application/json"})
        
        # Register new reseller
        unique = secrets.token_hex(4)
        email = f"test_short_{unique}@test.io"
        reg_resp = s.post(f"{BASE_URL}/api/auth/register", json={
            "email": email,
            "password": "Test@2026",
            "name": "Test Short Code",
            "role": "reseller"
        })
        assert reg_resp.status_code == 200, f"Registration failed: {reg_resp.text}"
        
        # Try to rename with short code
        rename_resp = s.post(f"{BASE_URL}/api/affiliate/primary-code", json={
            "code": "AB"  # Only 2 chars
        })
        assert rename_resp.status_code == 422 or rename_resp.status_code == 400, f"Expected 400/422, got {rename_resp.status_code}: {rename_resp.text}"
        print(f"PASS: Short code correctly rejected")
    
    def test_code_too_long(self):
        """Code > 24 chars should return 400"""
        s = requests.Session()
        s.headers.update({"Content-Type": "application/json"})
        
        # Register new reseller
        unique = secrets.token_hex(4)
        email = f"test_long_{unique}@test.io"
        reg_resp = s.post(f"{BASE_URL}/api/auth/register", json={
            "email": email,
            "password": "Test@2026",
            "name": "Test Long Code",
            "role": "reseller"
        })
        assert reg_resp.status_code == 200, f"Registration failed: {reg_resp.text}"
        
        # Try to rename with long code
        rename_resp = s.post(f"{BASE_URL}/api/affiliate/primary-code", json={
            "code": "A" * 25  # 25 chars
        })
        assert rename_resp.status_code == 422 or rename_resp.status_code == 400, f"Expected 400/422, got {rename_resp.status_code}: {rename_resp.text}"
        print(f"PASS: Long code correctly rejected")


class TestCodeUniqueness:
    """Test code uniqueness across platform"""
    
    def test_rename_to_existing_code_fails(self):
        """Renaming to a code already taken by another affiliate should fail"""
        # Create two fresh resellers
        s1 = requests.Session()
        s1.headers.update({"Content-Type": "application/json"})
        s2 = requests.Session()
        s2.headers.update({"Content-Type": "application/json"})
        
        unique = secrets.token_hex(4)
        
        # Register first reseller
        email1 = f"test_uniq1_{unique}@test.io"
        reg1 = s1.post(f"{BASE_URL}/api/auth/register", json={
            "email": email1,
            "password": "Test@2026",
            "name": "Test Unique 1",
            "role": "reseller"
        })
        assert reg1.status_code == 200, f"Registration 1 failed: {reg1.text}"
        
        # First reseller renames to a unique code
        unique_code = f"UNIQ{unique[:6].upper()}"
        rename1 = s1.post(f"{BASE_URL}/api/affiliate/primary-code", json={
            "code": unique_code
        })
        assert rename1.status_code == 200, f"First rename failed: {rename1.text}"
        print(f"First reseller renamed to: {unique_code}")
        
        # Register second reseller
        email2 = f"test_uniq2_{unique}@test.io"
        reg2 = s2.post(f"{BASE_URL}/api/auth/register", json={
            "email": email2,
            "password": "Test@2026",
            "name": "Test Unique 2",
            "role": "reseller"
        })
        assert reg2.status_code == 200, f"Registration 2 failed: {reg2.text}"
        
        # Second reseller tries to rename to same code - should fail
        rename2 = s2.post(f"{BASE_URL}/api/affiliate/primary-code", json={
            "code": unique_code
        })
        assert rename2.status_code == 400, f"Expected 400, got {rename2.status_code}: {rename2.text}"
        error_msg = rename2.json().get("detail", "")
        assert "already taken" in error_msg.lower() or "clashes" in error_msg.lower(), f"Expected 'already taken' or 'clashes', got: {error_msg}"
        print(f"PASS: Duplicate code correctly rejected with: {error_msg}")


class TestAutoMintOnRegistration:
    """Test auto-minting of affiliate code on reseller registration"""
    
    def test_new_reseller_gets_primary_code(self):
        """New reseller should have auto-minted primary code matching referral_code"""
        s = requests.Session()
        s.headers.update({"Content-Type": "application/json"})
        
        unique = secrets.token_hex(4)
        email = f"test_automint_{unique}@test.io"
        
        # Register new reseller
        reg_resp = s.post(f"{BASE_URL}/api/auth/register", json={
            "email": email,
            "password": "Test@2026",
            "name": "Test Auto Mint",
            "role": "reseller"
        })
        assert reg_resp.status_code == 200, f"Registration failed: {reg_resp.text}"
        user_data = reg_resp.json().get("user", {})
        referral_code = user_data.get("referral_code")
        print(f"New reseller referral_code: {referral_code}")
        
        # Check affiliate/me
        me_resp = s.get(f"{BASE_URL}/api/affiliate/me")
        assert me_resp.status_code == 200, f"affiliate/me failed: {me_resp.text}"
        me_data = me_resp.json()
        
        # Verify primary_code matches referral_code
        assert me_data.get("primary_code") == referral_code, f"primary_code {me_data.get('primary_code')} != referral_code {referral_code}"
        assert me_data.get("primary_code_renamed") == False, f"Expected primary_code_renamed=false, got {me_data.get('primary_code_renamed')}"
        
        # Verify code is 7 chars and U-prefixed
        assert referral_code.startswith("U"), f"Expected U-prefixed code, got {referral_code}"
        assert len(referral_code) == 7, f"Expected 7-char code, got {len(referral_code)}"
        
        # Check codes list includes this code
        codes_resp = s.get(f"{BASE_URL}/api/affiliate/codes")
        assert codes_resp.status_code == 200, f"affiliate/codes failed: {codes_resp.text}"
        codes = codes_resp.json()
        code_values = [c.get("code") for c in codes]
        assert referral_code in code_values, f"Auto-minted code {referral_code} not in codes list: {code_values}"
        
        print(f"PASS: Auto-mint verified - primary_code={me_data.get('primary_code')}, renamed={me_data.get('primary_code_renamed')}")


class TestPrimaryCodeRenameSuccess:
    """Test successful primary code rename for fresh reseller"""
    
    def test_fresh_reseller_can_rename_once(self):
        """Fresh reseller should be able to rename primary code once"""
        s = requests.Session()
        s.headers.update({"Content-Type": "application/json"})
        
        unique = secrets.token_hex(4)
        email = f"test_rename_{unique}@test.io"
        
        # Register new reseller
        reg_resp = s.post(f"{BASE_URL}/api/auth/register", json={
            "email": email,
            "password": "Test@2026",
            "name": "Test Rename",
            "role": "reseller"
        })
        assert reg_resp.status_code == 200, f"Registration failed: {reg_resp.text}"
        
        # Check initial state
        me_resp = s.get(f"{BASE_URL}/api/affiliate/me")
        assert me_resp.status_code == 200
        me_data = me_resp.json()
        assert me_data.get("primary_code_renamed") == False, "Expected primary_code_renamed=false initially"
        
        # Rename primary code
        new_code = f"CUSTOM{unique[:4].upper()}"
        rename_resp = s.post(f"{BASE_URL}/api/affiliate/primary-code", json={
            "code": new_code
        })
        assert rename_resp.status_code == 200, f"Rename failed: {rename_resp.text}"
        rename_data = rename_resp.json()
        assert rename_data.get("ok") == True, f"Expected ok=true, got {rename_data}"
        assert rename_data.get("code") == new_code, f"Expected code={new_code}, got {rename_data.get('code')}"
        
        # Verify state after rename
        me_resp2 = s.get(f"{BASE_URL}/api/affiliate/me")
        assert me_resp2.status_code == 200
        me_data2 = me_resp2.json()
        assert me_data2.get("primary_code") == new_code, f"Expected primary_code={new_code}, got {me_data2.get('primary_code')}"
        assert me_data2.get("primary_code_renamed") == True, f"Expected primary_code_renamed=true, got {me_data2.get('primary_code_renamed')}"
        assert me_data2.get("default_code") == new_code, f"Expected default_code={new_code}, got {me_data2.get('default_code')}"
        
        print(f"PASS: Rename success - primary_code={new_code}, renamed=true")
        
        # Try to rename again - should fail
        rename_resp2 = s.post(f"{BASE_URL}/api/affiliate/primary-code", json={
            "code": "ANOTHER123"
        })
        assert rename_resp2.status_code == 400, f"Expected 400 on second rename, got {rename_resp2.status_code}"
        print(f"PASS: Second rename correctly rejected")


class TestPublicAttribution:
    """Test referral attribution on registration"""
    
    def test_register_with_referral_code_sets_referred_by(self):
        """Registering with a referral_code should set referred_by to the affiliate's user id"""
        # First get the demo reseller's primary code
        s_reseller = requests.Session()
        s_reseller.headers.update({"Content-Type": "application/json"})
        login_resp = s_reseller.post(f"{BASE_URL}/api/auth/login", json={
            "email": RESELLER_EMAIL,
            "password": RESELLER_PASSWORD
        })
        assert login_resp.status_code == 200
        reseller_user = login_resp.json().get("user", {})
        reseller_id = reseller_user.get("id")
        
        me_resp = s_reseller.get(f"{BASE_URL}/api/affiliate/me")
        assert me_resp.status_code == 200
        primary_code = me_resp.json().get("primary_code")
        print(f"Demo reseller primary_code: {primary_code}, id: {reseller_id}")
        
        # Register a new client with this referral code
        s_client = requests.Session()
        s_client.headers.update({"Content-Type": "application/json"})
        
        unique = secrets.token_hex(4)
        email = f"test_referred_{unique}@test.io"
        
        reg_resp = s_client.post(f"{BASE_URL}/api/auth/register", json={
            "email": email,
            "password": "Test@2026",
            "name": "Test Referred Client",
            "role": "client",
            "referral_code": primary_code
        })
        assert reg_resp.status_code == 200, f"Registration failed: {reg_resp.text}"
        new_user = reg_resp.json().get("user", {})
        
        # Verify referred_by is set to reseller's id
        assert new_user.get("referred_by") == reseller_id, f"Expected referred_by={reseller_id}, got {new_user.get('referred_by')}"
        print(f"PASS: New user referred_by={new_user.get('referred_by')} matches reseller id")
        
        return s_client, new_user


class TestCommissionOnTopup:
    """Test commission recording on credits/buy"""
    
    def test_commission_created_on_buy(self, admin_session):
        """When a referred user buys credits, affiliate_earnings should be created"""
        # First get the demo reseller's info
        s_reseller = requests.Session()
        s_reseller.headers.update({"Content-Type": "application/json"})
        login_resp = s_reseller.post(f"{BASE_URL}/api/auth/login", json={
            "email": RESELLER_EMAIL,
            "password": RESELLER_PASSWORD
        })
        assert login_resp.status_code == 200
        reseller_user = login_resp.json().get("user", {})
        reseller_id = reseller_user.get("id")
        
        me_resp = s_reseller.get(f"{BASE_URL}/api/affiliate/me")
        assert me_resp.status_code == 200
        primary_code = me_resp.json().get("primary_code")
        initial_earned = me_resp.json().get("earned_usd", 0)
        print(f"Reseller initial earned_usd: {initial_earned}")
        
        # Register a new client with this referral code
        s_client = requests.Session()
        s_client.headers.update({"Content-Type": "application/json"})
        
        unique = secrets.token_hex(4)
        email = f"test_commission_{unique}@test.io"
        
        reg_resp = s_client.post(f"{BASE_URL}/api/auth/register", json={
            "email": email,
            "password": "Test@2026",
            "name": "Test Commission Client",
            "role": "client",
            "referral_code": primary_code
        })
        assert reg_resp.status_code == 200, f"Registration failed: {reg_resp.text}"
        new_user = reg_resp.json().get("user", {})
        new_user_id = new_user.get("id")
        
        # Get available packs first
        packs_resp = s_client.get(f"{BASE_URL}/api/credits/packs")
        assert packs_resp.status_code == 200, f"Get packs failed: {packs_resp.text}"
        packs = packs_resp.json()
        # Find the Starter pack ($15)
        starter_pack = next((p for p in packs if p.get("name") == "Starter" and p.get("price_usd") == 15.0), None)
        if not starter_pack:
            # Fallback to any pack
            starter_pack = packs[0] if packs else None
        assert starter_pack, "No credit packs available"
        pack_price = starter_pack.get("price_usd", 15)
        
        # Buy credits (mock payment)
        buy_resp = s_client.post(f"{BASE_URL}/api/credits/buy", json={
            "pack_id": starter_pack["id"]
        })
        assert buy_resp.status_code == 200, f"Credits buy failed: {buy_resp.text}"
        print(f"Credits buy response: {buy_resp.json()}")
        
        # Check reseller's earnings increased
        me_resp2 = s_reseller.get(f"{BASE_URL}/api/affiliate/me")
        assert me_resp2.status_code == 200
        new_earned = me_resp2.json().get("earned_usd", 0)
        print(f"Reseller new earned_usd: {new_earned}")
        
        # Check affiliate_earnings has a new row
        earnings_resp = s_reseller.get(f"{BASE_URL}/api/affiliate/earnings")
        assert earnings_resp.status_code == 200
        earnings = earnings_resp.json()
        
        # Find the earning for this user
        user_earnings = [e for e in earnings if e.get("referred_user_id") == new_user_id]
        assert len(user_earnings) > 0, f"No earnings found for referred user {new_user_id}"
        
        earning = user_earnings[0]
        assert earning.get("kind") == "topup", f"Expected kind=topup, got {earning.get('kind')}"
        assert earning.get("model_used") == "time_window", f"Expected model_used=time_window, got {earning.get('model_used')}"
        assert earning.get("status") == "earned", f"Expected status=earned, got {earning.get('status')}"
        
        # Commission should be 10% of pack price
        expected_commission = pack_price * 0.10
        actual_commission = earning.get("amount_usd", 0)
        assert abs(actual_commission - expected_commission) < 0.01, f"Expected commission ~{expected_commission}, got {actual_commission}"
        
        print(f"PASS: Commission recorded - kind={earning.get('kind')}, model={earning.get('model_used')}, status={earning.get('status')}, amount=${actual_commission}")


class TestSecondaryCodes:
    """Test secondary code CRUD"""
    
    def test_create_secondary_code(self):
        """Create a secondary code"""
        s = requests.Session()
        s.headers.update({"Content-Type": "application/json"})
        
        unique = secrets.token_hex(4)
        email = f"test_secondary_{unique}@test.io"
        
        # Register new reseller
        reg_resp = s.post(f"{BASE_URL}/api/auth/register", json={
            "email": email,
            "password": "Test@2026",
            "name": "Test Secondary",
            "role": "reseller"
        })
        assert reg_resp.status_code == 200
        
        # Create secondary code
        code_name = f"SEC{unique[:4].upper()}"
        create_resp = s.post(f"{BASE_URL}/api/affiliate/codes", json={
            "code": code_name,
            "note": "Test secondary code"
        })
        assert create_resp.status_code == 200, f"Create code failed: {create_resp.text}"
        created = create_resp.json()
        assert created.get("code") == code_name
        code_id = created.get("id")
        print(f"PASS: Secondary code created: {code_name}, id={code_id}")
        
        # Delete the code
        del_resp = s.delete(f"{BASE_URL}/api/affiliate/codes/{code_id}")
        assert del_resp.status_code == 200, f"Delete code failed: {del_resp.text}"
        print(f"PASS: Secondary code deleted")
    
    def test_code_cap_enforced(self):
        """Cannot create more than 5 codes"""
        s = requests.Session()
        s.headers.update({"Content-Type": "application/json"})
        
        unique = secrets.token_hex(4)
        email = f"test_cap_{unique}@test.io"
        
        # Register new reseller
        reg_resp = s.post(f"{BASE_URL}/api/auth/register", json={
            "email": email,
            "password": "Test@2026",
            "name": "Test Cap",
            "role": "reseller"
        })
        assert reg_resp.status_code == 200
        
        # Already has 1 auto-minted code, create 4 more (total 5)
        for i in range(4):
            code_name = f"CAP{unique[:3].upper()}{i}"
            create_resp = s.post(f"{BASE_URL}/api/affiliate/codes", json={
                "code": code_name,
                "note": f"Cap test {i}"
            })
            assert create_resp.status_code == 200, f"Create code {i} failed: {create_resp.text}"
        
        # Try to create 6th code - should fail
        create_resp = s.post(f"{BASE_URL}/api/affiliate/codes", json={
            "code": f"CAP{unique[:3].upper()}X",
            "note": "Should fail"
        })
        assert create_resp.status_code == 400, f"Expected 400 on 6th code, got {create_resp.status_code}"
        error_msg = create_resp.json().get("detail", "")
        assert "5" in error_msg or "cap" in error_msg.lower(), f"Expected cap message, got: {error_msg}"
        print(f"PASS: Code cap enforced - {error_msg}")


class TestResellerMeEndpoint:
    """Test GET /affiliate/me returns correct structure"""
    
    def test_me_returns_correct_fields(self, reseller_session):
        """affiliate/me should return all required fields"""
        resp = reseller_session.get(f"{BASE_URL}/api/affiliate/me")
        assert resp.status_code == 200
        data = resp.json()
        
        # Check required fields
        required_fields = ["config", "referrals", "earned_usd", "requested_usd", "paid_usd", 
                          "available_usd", "default_code", "primary_code", "primary_code_renamed"]
        for field in required_fields:
            assert field in data, f"Missing field: {field}"
        
        # Check config has required keys
        config = data.get("config", {})
        config_keys = ["model", "commission_pct", "window_months", "payout_threshold_usd", "active"]
        for key in config_keys:
            assert key in config, f"Missing config key: {key}"
        
        print(f"PASS: affiliate/me returns all required fields")
        print(f"  primary_code: {data.get('primary_code')}")
        print(f"  primary_code_renamed: {data.get('primary_code_renamed')}")
        print(f"  default_code: {data.get('default_code')}")
        print(f"  referrals: {data.get('referrals')}")
        print(f"  earned_usd: {data.get('earned_usd')}")


if __name__ == "__main__":
    pytest.main([__file__, "-v", "--tb=short"])
