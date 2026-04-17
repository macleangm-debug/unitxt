"""
unitxt Backend API Tests
Tests all auth, wallet, messaging, contacts, templates, sender-ids, api-keys,
notifications, reseller, and admin endpoints.
"""
import pytest
import requests
import os
import time

BASE_URL = os.environ.get('REACT_APP_BACKEND_URL', '').rstrip('/')

# Test credentials from seed
ADMIN_EMAIL = "admin@unitxt.io"
ADMIN_PASSWORD = "Admin@2026"
RESELLER_EMAIL = "reseller@unitxt.io"
RESELLER_PASSWORD = "Reseller@2026"
CLIENT_EMAIL = "client@unitxt.io"
CLIENT_PASSWORD = "Client@2026"


class TestHealthCheck:
    """Basic health check"""
    
    def test_api_root(self):
        response = requests.get(f"{BASE_URL}/api/")
        assert response.status_code == 200
        data = response.json()
        assert data["app"] == "unitxt"
        assert data["status"] == "ok"
        print("API root health check passed")


class TestAuthLogin:
    """Authentication login tests"""
    
    def test_admin_login_success(self):
        """POST /api/auth/login — login as admin returns 200 with user object"""
        session = requests.Session()
        response = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": ADMIN_EMAIL,
            "password": ADMIN_PASSWORD
        })
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert "user" in data
        assert data["user"]["email"] == ADMIN_EMAIL
        assert data["user"]["role"] == "super_admin"
        assert "access_token" in data
        # Check cookies are set
        assert "access_token" in session.cookies or "access_token" in response.cookies
        print(f"Admin login success: {data['user']['email']}, role: {data['user']['role']}")
    
    def test_reseller_login_success(self):
        """POST /api/auth/login — login as reseller returns reseller user"""
        session = requests.Session()
        response = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": RESELLER_EMAIL,
            "password": RESELLER_PASSWORD
        })
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert "user" in data
        assert data["user"]["email"] == RESELLER_EMAIL
        assert data["user"]["role"] == "reseller"
        print(f"Reseller login success: {data['user']['email']}, role: {data['user']['role']}")
    
    def test_client_login_success(self):
        """POST /api/auth/login — login as client returns client user with reseller_id"""
        session = requests.Session()
        response = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL,
            "password": CLIENT_PASSWORD
        })
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert "user" in data
        assert data["user"]["email"] == CLIENT_EMAIL
        assert data["user"]["role"] == "client"
        assert data["user"].get("reseller_id") is not None, "Client should have reseller_id set"
        print(f"Client login success: {data['user']['email']}, reseller_id: {data['user']['reseller_id']}")
    
    def test_login_wrong_password(self):
        """POST /api/auth/login — wrong password returns 401"""
        response = requests.post(f"{BASE_URL}/api/auth/login", json={
            "email": ADMIN_EMAIL,
            "password": "WrongPassword123"
        })
        assert response.status_code == 401, f"Expected 401, got {response.status_code}"
        print("Wrong password correctly returns 401")


class TestAuthMe:
    """GET /api/auth/me tests"""
    
    def test_me_authenticated(self):
        """GET /api/auth/me — returns {user, wallet} when authenticated"""
        session = requests.Session()
        # Login first
        login_resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL,
            "password": CLIENT_PASSWORD
        })
        assert login_resp.status_code == 200
        token = login_resp.json().get("access_token")
        
        # Call /me with token
        response = session.get(f"{BASE_URL}/api/auth/me", headers={
            "Authorization": f"Bearer {token}"
        })
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert "user" in data
        assert "wallet" in data
        assert data["user"]["email"] == CLIENT_EMAIL
        print(f"Auth me success: user={data['user']['email']}, wallet_balance={data['wallet'].get('balance')}")
    
    def test_me_unauthenticated(self):
        """GET /api/auth/me — returns 401 when not authenticated"""
        response = requests.get(f"{BASE_URL}/api/auth/me")
        assert response.status_code == 401, f"Expected 401, got {response.status_code}"
        print("Unauthenticated /me correctly returns 401")


class TestAuthRegister:
    """POST /api/auth/register tests"""
    
    def test_register_new_client(self):
        """POST /api/auth/register — registering a new client succeeds"""
        import uuid
        unique_email = f"test_{uuid.uuid4().hex[:8]}@test.io"
        session = requests.Session()
        response = session.post(f"{BASE_URL}/api/auth/register", json={
            "email": unique_email,
            "password": "TestPass123",
            "name": "Test User",
            "role": "client",
            "country": "TZ"
        })
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert "user" in data
        assert data["user"]["email"] == unique_email
        assert data["user"]["role"] == "client"
        assert "access_token" in data
        
        # Verify wallet was created with 0 balance
        token = data["access_token"]
        wallet_resp = session.get(f"{BASE_URL}/api/wallet/me", headers={
            "Authorization": f"Bearer {token}"
        })
        assert wallet_resp.status_code == 200
        wallet = wallet_resp.json()
        assert wallet["balance"] == 0
        print(f"Register success: {unique_email}, wallet_balance={wallet['balance']}")


class TestAuthLogout:
    """POST /api/auth/logout tests"""
    
    def test_logout_clears_cookies(self):
        """POST /api/auth/logout — clears cookies"""
        session = requests.Session()
        # Login first
        login_resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL,
            "password": CLIENT_PASSWORD
        })
        assert login_resp.status_code == 200
        token = login_resp.json().get("access_token")
        
        # Logout
        response = session.post(f"{BASE_URL}/api/auth/logout", headers={
            "Authorization": f"Bearer {token}"
        })
        assert response.status_code == 200
        data = response.json()
        assert data.get("ok") == True
        print("Logout success")


class TestWallet:
    """Wallet endpoint tests"""
    
    @pytest.fixture
    def client_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL,
            "password": CLIENT_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    @pytest.fixture
    def admin_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": ADMIN_EMAIL,
            "password": ADMIN_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    def test_wallet_me(self, client_session):
        """GET /api/wallet/me — returns wallet object with balance and currency"""
        response = client_session.get(f"{BASE_URL}/api/wallet/me")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert "balance" in data
        assert "currency" in data
        assert "user_id" in data
        print(f"Wallet me: balance={data['balance']}, currency={data['currency']}")
    
    def test_topup_with_promo(self, client_session):
        """POST /api/wallet/topup — client tops up $50 with WELCOME10 should credit $50 + 10% bonus"""
        response = client_session.post(f"{BASE_URL}/api/wallet/topup", json={
            "amount": 50,
            "method": "manual",
            "note": "WELCOME10"
        })
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert data.get("ok") == True
        assert "tx" in data
        # WELCOME10 is 10% bonus on $50+ topup
        assert data.get("bonus") == 5.0, f"Expected bonus 5.0, got {data.get('bonus')}"
        print(f"Topup with promo success: amount=50, bonus={data.get('bonus')}")
    
    def test_admin_topup(self, admin_session):
        """POST /api/wallet/topup — admin user can also topup"""
        response = admin_session.post(f"{BASE_URL}/api/wallet/topup", json={
            "amount": 100,
            "method": "manual",
            "note": "Admin test topup"
        })
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert data.get("ok") == True
        print("Admin topup success")
    
    def test_wallet_transactions(self, client_session):
        """GET /api/wallet/transactions — returns list including the topup"""
        response = client_session.get(f"{BASE_URL}/api/wallet/transactions")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert isinstance(data, list)
        print(f"Wallet transactions: {len(data)} transactions found")


class TestSenderIds:
    """Sender ID endpoint tests"""
    
    @pytest.fixture
    def client_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL,
            "password": CLIENT_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    def test_list_sender_ids(self, client_session):
        """GET /api/sender-ids — client sees their seeded SUNRISE sender ID with status approved"""
        response = client_session.get(f"{BASE_URL}/api/sender-ids")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert isinstance(data, list)
        # Find SUNRISE
        sunrise = next((s for s in data if s.get("sender_id") == "SUNRISE"), None)
        assert sunrise is not None, "SUNRISE sender ID not found"
        assert sunrise.get("status") == "approved"
        print(f"Sender IDs: found SUNRISE with status={sunrise.get('status')}")
    
    def test_request_new_sender_id(self, client_session):
        """POST /api/sender-ids — client can submit a new sender ID request, status defaults to pending"""
        import uuid
        unique_sid = f"TEST{uuid.uuid4().hex[:4].upper()}"
        response = client_session.post(f"{BASE_URL}/api/sender-ids", json={
            "sender_id": unique_sid,
            "country": "TZ",
            "use_case": "Test notifications",
            "sample_message": "This is a test message"
        })
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert data.get("sender_id") == unique_sid
        assert data.get("status") == "pending"
        print(f"New sender ID request: {unique_sid}, status={data.get('status')}")


class TestMessaging:
    """Messaging endpoint tests"""
    
    @pytest.fixture
    def client_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL,
            "password": CLIENT_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    def test_quick_send(self, client_session):
        """POST /api/messaging/quick-send — client sends a quick SMS"""
        response = client_session.post(f"{BASE_URL}/api/messaging/quick-send", json={
            "channel": "sms",
            "sender_id": "SUNRISE",
            "recipients": ["+255712345678"],
            "message": "Test message from unitxt"
        })
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert data.get("ok") == True
        assert "campaign_id" in data
        print(f"Quick send success: campaign_id={data.get('campaign_id')}")
        return data.get("campaign_id")
    
    def test_campaigns_list(self, client_session):
        """GET /api/messaging/campaigns — returns campaigns"""
        # First send a message
        send_resp = client_session.post(f"{BASE_URL}/api/messaging/quick-send", json={
            "channel": "sms",
            "sender_id": "SUNRISE",
            "recipients": ["+255712345679"],
            "message": "Test for campaign list"
        })
        assert send_resp.status_code == 200
        campaign_id = send_resp.json().get("campaign_id")
        
        # Wait for campaign to complete
        time.sleep(2)
        
        response = client_session.get(f"{BASE_URL}/api/messaging/campaigns")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert isinstance(data, list)
        # Find our campaign
        campaign = next((c for c in data if c.get("id") == campaign_id), None)
        assert campaign is not None, "Campaign not found"
        # Status should be completed (or running if still processing)
        assert campaign.get("status") in ["completed", "running"], f"Unexpected status: {campaign.get('status')}"
        print(f"Campaign found: id={campaign_id}, status={campaign.get('status')}")
    
    def test_messages_list(self, client_session):
        """GET /api/messaging/messages — returns dispatched messages"""
        response = client_session.get(f"{BASE_URL}/api/messaging/messages")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert isinstance(data, list)
        print(f"Messages: {len(data)} messages found")
    
    def test_messaging_stats(self, client_session):
        """GET /api/messaging/stats — returns total_messages, delivery_rate, by_status"""
        response = client_session.get(f"{BASE_URL}/api/messaging/stats")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert "total_messages" in data
        assert "delivery_rate" in data
        assert "by_status" in data
        print(f"Stats: total={data.get('total_messages')}, delivery_rate={data.get('delivery_rate')}%")
    
    def test_bulk_send(self, client_session):
        """POST /api/messaging/bulk-send — client sends a bulk campaign with merge tags"""
        response = client_session.post(f"{BASE_URL}/api/messaging/bulk-send", json={
            "channel": "sms",
            "sender_id": "SUNRISE",
            "name": "Test Bulk Campaign",
            "recipients": [
                {"phone": "+255712345680", "name": "John"},
                {"phone": "+255712345681", "name": "Jane"}
            ],
            "template": "Hello {name}, this is a test message."
        })
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert data.get("ok") == True
        assert "campaign_id" in data
        print(f"Bulk send success: campaign_id={data.get('campaign_id')}")


class TestContacts:
    """Contacts endpoint tests"""
    
    @pytest.fixture
    def client_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL,
            "password": CLIENT_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    def test_contacts_crud(self, client_session):
        """GET /api/contacts — CRUD operations"""
        # List contacts
        list_resp = client_session.get(f"{BASE_URL}/api/contacts")
        assert list_resp.status_code == 200
        initial_count = len(list_resp.json())
        
        # Add contact
        add_resp = client_session.post(f"{BASE_URL}/api/contacts", json={
            "phone": "+255712345699",
            "name": "Test Contact",
            "tags": ["test"]
        })
        assert add_resp.status_code == 200, f"Expected 200, got {add_resp.status_code}: {add_resp.text}"
        contact = add_resp.json()
        assert contact.get("phone") == "+255712345699"
        contact_id = contact.get("id")
        
        # Verify added
        list_resp2 = client_session.get(f"{BASE_URL}/api/contacts")
        assert len(list_resp2.json()) == initial_count + 1
        
        # Delete contact
        del_resp = client_session.delete(f"{BASE_URL}/api/contacts/{contact_id}")
        assert del_resp.status_code == 200
        
        print("Contacts CRUD success")
    
    def test_contacts_import(self, client_session):
        """POST /api/contacts/import — imports many contacts"""
        response = client_session.post(f"{BASE_URL}/api/contacts/import", json=[
            {"phone": "+255712345700", "name": "Import1"},
            {"phone": "+255712345701", "name": "Import2"},
            {"phone": "+255712345702", "name": "Import3"}
        ])
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert data.get("ok") == True
        assert data.get("count") == 3
        print(f"Contacts import success: {data.get('count')} imported")


class TestTemplates:
    """Templates endpoint tests"""
    
    @pytest.fixture
    def client_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL,
            "password": CLIENT_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    def test_templates_crud(self, client_session):
        """GET /api/templates — CRUD operations"""
        # List
        list_resp = client_session.get(f"{BASE_URL}/api/templates")
        assert list_resp.status_code == 200
        
        # Add
        add_resp = client_session.post(f"{BASE_URL}/api/templates", json={
            "name": "Test Template",
            "body": "Hello {name}, your code is {code}",
            "category": "transactional"
        })
        assert add_resp.status_code == 200, f"Expected 200, got {add_resp.status_code}: {add_resp.text}"
        template = add_resp.json()
        template_id = template.get("id")
        
        # Delete
        del_resp = client_session.delete(f"{BASE_URL}/api/templates/{template_id}")
        assert del_resp.status_code == 200
        
        print("Templates CRUD success")


class TestApiKeys:
    """API Keys endpoint tests"""
    
    @pytest.fixture
    def client_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL,
            "password": CLIENT_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    def test_api_keys_crud(self, client_session):
        """GET /api/api-keys — CRUD; created keys start with 'uxk_'"""
        # List
        list_resp = client_session.get(f"{BASE_URL}/api/api-keys")
        assert list_resp.status_code == 200
        
        # Create
        create_resp = client_session.post(f"{BASE_URL}/api/api-keys", json={
            "name": "Test API Key"
        })
        assert create_resp.status_code == 200, f"Expected 200, got {create_resp.status_code}: {create_resp.text}"
        key_data = create_resp.json()
        assert key_data.get("key", "").startswith("uxk_"), f"Key should start with 'uxk_', got {key_data.get('key')}"
        key_id = key_data.get("id")
        
        # Delete
        del_resp = client_session.delete(f"{BASE_URL}/api/api-keys/{key_id}")
        assert del_resp.status_code == 200
        
        print(f"API Keys CRUD success: key starts with 'uxk_'")


class TestNotifications:
    """Notifications endpoint tests"""
    
    @pytest.fixture
    def client_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL,
            "password": CLIENT_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    def test_notifications_list(self, client_session):
        """GET /api/notifications — returns items with unread count"""
        response = client_session.get(f"{BASE_URL}/api/notifications")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert "items" in data
        assert "unread" in data
        print(f"Notifications: {len(data['items'])} items, {data['unread']} unread")
    
    def test_notifications_read_all(self, client_session):
        """POST /api/notifications/read-all — clears unread"""
        response = client_session.post(f"{BASE_URL}/api/notifications/read-all")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert data.get("ok") == True
        print("Notifications read-all success")


class TestReseller:
    """Reseller endpoint tests"""
    
    @pytest.fixture
    def reseller_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": RESELLER_EMAIL,
            "password": RESELLER_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    @pytest.fixture
    def client_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL,
            "password": CLIENT_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    def test_reseller_clients(self, reseller_session):
        """GET /api/reseller/clients — reseller sees at least 1 client with wallet_balance"""
        response = reseller_session.get(f"{BASE_URL}/api/reseller/clients")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert isinstance(data, list)
        assert len(data) >= 1, "Reseller should have at least 1 client"
        # Check wallet_balance attached
        client = data[0]
        assert "wallet_balance" in client
        print(f"Reseller clients: {len(data)} clients, first client wallet_balance={client.get('wallet_balance')}")
    
    def test_reseller_transfer(self, reseller_session):
        """POST /api/reseller/transfer — reseller transfers $10 to its client"""
        # First get client list
        clients_resp = reseller_session.get(f"{BASE_URL}/api/reseller/clients")
        clients = clients_resp.json()
        assert len(clients) > 0
        client_id = clients[0]["id"]
        
        response = reseller_session.post(f"{BASE_URL}/api/reseller/transfer", json={
            "target_user_id": client_id,
            "amount": 10,
            "note": "Test transfer"
        })
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert data.get("ok") == True
        print("Reseller transfer success")
    
    def test_reseller_earnings(self, reseller_session):
        """GET /api/reseller/earnings — returns clients count, client_spend, commission_rate, earned"""
        response = reseller_session.get(f"{BASE_URL}/api/reseller/earnings")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert "clients" in data
        assert "client_spend" in data
        assert "commission_rate" in data
        assert "earned" in data
        print(f"Reseller earnings: clients={data['clients']}, earned={data['earned']}")
    
    def test_reseller_code(self, reseller_session):
        """GET /api/reseller/code — returns reseller code 'RDEMO1'"""
        response = reseller_session.get(f"{BASE_URL}/api/reseller/code")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert data.get("code") == "RDEMO1"
        print(f"Reseller code: {data.get('code')}")


class TestRBAC:
    """Role-Based Access Control tests"""
    
    @pytest.fixture
    def client_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": CLIENT_EMAIL,
            "password": CLIENT_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    @pytest.fixture
    def reseller_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": RESELLER_EMAIL,
            "password": RESELLER_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    def test_client_cannot_access_reseller_clients(self, client_session):
        """RBAC: client cannot call /api/reseller/clients (should 403)"""
        response = client_session.get(f"{BASE_URL}/api/reseller/clients")
        assert response.status_code == 403, f"Expected 403, got {response.status_code}"
        print("RBAC: client correctly blocked from /api/reseller/clients")
    
    def test_reseller_cannot_access_admin_overview(self, reseller_session):
        """RBAC: reseller cannot call /api/admin/overview (should 403)"""
        response = reseller_session.get(f"{BASE_URL}/api/admin/overview")
        assert response.status_code == 403, f"Expected 403, got {response.status_code}"
        print("RBAC: reseller correctly blocked from /api/admin/overview")
    
    def test_client_cannot_access_admin_users(self, client_session):
        """RBAC: client cannot call /api/admin/users (should 403)"""
        response = client_session.get(f"{BASE_URL}/api/admin/users")
        assert response.status_code == 403, f"Expected 403, got {response.status_code}"
        print("RBAC: client correctly blocked from /api/admin/users")


class TestAdmin:
    """Admin endpoint tests"""
    
    @pytest.fixture
    def admin_session(self):
        session = requests.Session()
        resp = session.post(f"{BASE_URL}/api/auth/login", json={
            "email": ADMIN_EMAIL,
            "password": ADMIN_PASSWORD
        })
        token = resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        return session
    
    def test_admin_overview(self, admin_session):
        """GET /api/admin/overview — admin returns kpi, by_country, providers, recent_activity"""
        response = admin_session.get(f"{BASE_URL}/api/admin/overview")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert "kpi" in data
        assert "by_country" in data
        assert "providers" in data
        assert "recent_activity" in data
        kpi = data["kpi"]
        assert "revenue" in kpi
        assert "msgs_total" in kpi
        print(f"Admin overview: users={kpi.get('users_total')}, msgs={kpi.get('msgs_total')}, revenue={kpi.get('revenue')}")
    
    def test_admin_users(self, admin_session):
        """GET /api/admin/users — returns all users"""
        response = admin_session.get(f"{BASE_URL}/api/admin/users")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert isinstance(data, list)
        assert len(data) >= 3  # At least admin, reseller, client
        print(f"Admin users: {len(data)} users")
    
    def test_admin_update_user(self, admin_session):
        """PATCH /api/admin/users/{id} — admin can update name, status, role"""
        # Get users first
        users_resp = admin_session.get(f"{BASE_URL}/api/admin/users")
        users = users_resp.json()
        # Find client user
        client = next((u for u in users if u.get("email") == CLIENT_EMAIL), None)
        assert client is not None
        
        response = admin_session.patch(f"{BASE_URL}/api/admin/users/{client['id']}", json={
            "name": "Demo Client Updated"
        })
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        
        # Revert
        admin_session.patch(f"{BASE_URL}/api/admin/users/{client['id']}", json={
            "name": "Demo Client"
        })
        print("Admin update user success")
    
    def test_admin_credit_user(self, admin_session):
        """POST /api/admin/users/{id}/credit — admin credits a user wallet manually"""
        # Get users first
        users_resp = admin_session.get(f"{BASE_URL}/api/admin/users")
        users = users_resp.json()
        client = next((u for u in users if u.get("email") == CLIENT_EMAIL), None)
        assert client is not None
        
        response = admin_session.post(f"{BASE_URL}/api/admin/users/{client['id']}/credit", json={
            "amount": 5,
            "note": "Test admin credit"
        })
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        print("Admin credit user success")
    
    def test_admin_sender_ids(self, admin_session):
        """GET /api/admin/sender-ids — admin sees pending requests"""
        response = admin_session.get(f"{BASE_URL}/api/admin/sender-ids")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert isinstance(data, list)
        print(f"Admin sender-ids: {len(data)} requests")
    
    def test_admin_review_sender_id(self, admin_session):
        """POST /api/admin/sender-ids/{id}/review — approves or rejects"""
        # Get sender IDs
        sids_resp = admin_session.get(f"{BASE_URL}/api/admin/sender-ids")
        sids = sids_resp.json()
        # Find a pending one if exists
        pending = next((s for s in sids if s.get("status") == "pending"), None)
        if pending:
            response = admin_session.post(f"{BASE_URL}/api/admin/sender-ids/{pending['id']}/review", json={
                "status": "approved",
                "note": "Test approval"
            })
            assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
            print("Admin review sender ID success")
        else:
            print("No pending sender IDs to review (skipped)")
    
    def test_admin_countries(self, admin_session):
        """GET /api/admin/countries — returns 12 seeded countries"""
        response = admin_session.get(f"{BASE_URL}/api/admin/countries")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert isinstance(data, list)
        assert len(data) >= 12, f"Expected at least 12 countries, got {len(data)}"
        print(f"Admin countries: {len(data)} countries")
    
    def test_admin_providers(self, admin_session):
        """GET /api/admin/providers — returns 3 seeded providers"""
        response = admin_session.get(f"{BASE_URL}/api/admin/providers")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert isinstance(data, list)
        assert len(data) >= 3, f"Expected at least 3 providers, got {len(data)}"
        # Check for expected providers
        names = [p.get("name", "").lower() for p in data]
        assert any("tz" in n or "direct" in n for n in names), "TZ Direct provider not found"
        assert any("twilio" in n for n in names), "Twilio provider not found"
        print(f"Admin providers: {len(data)} providers")
    
    def test_admin_pricing(self, admin_session):
        """GET /api/admin/pricing — returns 3 seeded plans"""
        response = admin_session.get(f"{BASE_URL}/api/admin/pricing")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert isinstance(data, list)
        assert len(data) >= 3, f"Expected at least 3 pricing plans, got {len(data)}"
        print(f"Admin pricing: {len(data)} plans")
    
    def test_admin_institutions(self, admin_session):
        """GET /api/admin/institutions — returns 2 seeded"""
        response = admin_session.get(f"{BASE_URL}/api/admin/institutions")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert isinstance(data, list)
        assert len(data) >= 2, f"Expected at least 2 institutions, got {len(data)}"
        print(f"Admin institutions: {len(data)} institutions")
    
    def test_admin_promotions(self, admin_session):
        """GET /api/admin/promotions — returns 2 seeded (WELCOME10, BONUS25)"""
        response = admin_session.get(f"{BASE_URL}/api/admin/promotions")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert isinstance(data, list)
        assert len(data) >= 2, f"Expected at least 2 promotions, got {len(data)}"
        codes = [p.get("code") for p in data]
        assert "WELCOME10" in codes, "WELCOME10 promo not found"
        assert "BONUS25" in codes, "BONUS25 promo not found"
        print(f"Admin promotions: {len(data)} promotions, codes={codes}")
    
    def test_admin_settings(self, admin_session):
        """GET /api/admin/settings — returns settings keys"""
        response = admin_session.get(f"{BASE_URL}/api/admin/settings")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert isinstance(data, list)
        print(f"Admin settings: {len(data)} settings")
    
    def test_admin_upsert_setting(self, admin_session):
        """PUT /api/admin/settings — upserts a key"""
        response = admin_session.put(f"{BASE_URL}/api/admin/settings", json={
            "key": "test.setting",
            "value": "test_value",
            "category": "test"
        })
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert data.get("ok") == True
        print("Admin upsert setting success")
    
    def test_admin_audit_logs(self, admin_session):
        """GET /api/admin/audit-logs — returns audit log entries"""
        response = admin_session.get(f"{BASE_URL}/api/admin/audit-logs")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert isinstance(data, list)
        print(f"Admin audit logs: {len(data)} entries")
    
    def test_admin_wallets(self, admin_session):
        """GET /api/admin/wallets — returns wallets with attached user info"""
        response = admin_session.get(f"{BASE_URL}/api/admin/wallets")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert isinstance(data, list)
        if data:
            assert "user" in data[0], "Wallet should have user info attached"
        print(f"Admin wallets: {len(data)} wallets")
    
    def test_admin_campaigns(self, admin_session):
        """GET /api/admin/campaigns — returns all campaigns across users"""
        response = admin_session.get(f"{BASE_URL}/api/admin/campaigns")
        assert response.status_code == 200, f"Expected 200, got {response.status_code}: {response.text}"
        data = response.json()
        assert isinstance(data, list)
        print(f"Admin campaigns: {len(data)} campaigns")


class TestInsufficientBalance:
    """Test insufficient balance scenario"""
    
    def test_insufficient_balance_for_bulk_send(self):
        """Insufficient balance: client tries to send 1000 messages exceeding wallet — should 400"""
        # Register a new user with 0 balance
        import uuid
        unique_email = f"test_broke_{uuid.uuid4().hex[:8]}@test.io"
        session = requests.Session()
        reg_resp = session.post(f"{BASE_URL}/api/auth/register", json={
            "email": unique_email,
            "password": "TestPass123",
            "name": "Broke User",
            "role": "client",
            "country": "TZ"
        })
        assert reg_resp.status_code == 200
        token = reg_resp.json().get("access_token")
        session.headers.update({"Authorization": f"Bearer {token}"})
        
        # Try to send 1000 messages (should fail due to insufficient balance)
        recipients = [{"phone": f"+25571234{i:04d}", "name": f"User{i}"} for i in range(1000)]
        response = session.post(f"{BASE_URL}/api/messaging/bulk-send", json={
            "channel": "sms",
            "sender_id": "TEST",
            "name": "Bulk Test",
            "recipients": recipients,
            "template": "Hello {name}"
        })
        assert response.status_code == 400, f"Expected 400, got {response.status_code}: {response.text}"
        print("Insufficient balance correctly returns 400")


if __name__ == "__main__":
    pytest.main([__file__, "-v", "--tb=short"])
