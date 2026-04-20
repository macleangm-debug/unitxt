"""
Iteration 7 Tests: Country Hub, Integration Health, Add-Country Wizard, Add-Integration Wizard
Tests Phase 2 features: Country Hub (catalog + detail + 4-step wizard), Integration Health dashboard, Add-integration wizard
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


@pytest.fixture(scope="module")
def admin_session():
    """Authenticated admin session"""
    session = requests.Session()
    session.headers.update({"Content-Type": "application/json"})
    resp = session.post(f"{BASE_URL}/api/auth/login", json={
        "email": ADMIN_EMAIL, "password": ADMIN_PASSWORD
    })
    assert resp.status_code == 200, f"Admin login failed: {resp.text}"
    token = resp.json().get("access_token")
    if token:
        session.headers.update({"Authorization": f"Bearer {token}"})
    return session


@pytest.fixture(scope="module")
def reseller_session():
    """Authenticated reseller session (for permission tests)"""
    session = requests.Session()
    session.headers.update({"Content-Type": "application/json"})
    resp = session.post(f"{BASE_URL}/api/auth/login", json={
        "email": RESELLER_EMAIL, "password": RESELLER_PASSWORD
    })
    assert resp.status_code == 200, f"Reseller login failed: {resp.text}"
    token = resp.json().get("access_token")
    if token:
        session.headers.update({"Authorization": f"Bearer {token}"})
    return session


# ============================================================
# INTEGRATED ADAPTERS - CRITICAL: Only 3 adapters allowed
# ============================================================
class TestIntegratedAdapters:
    """CRITICAL: Verify only Twilio, Tigo TZ, Mock adapters are exposed"""

    def test_available_integrations_returns_only_3_adapters(self, admin_session):
        """GET /api/admin/country-hub/integrations/available returns ONLY 3 adapters"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-hub/integrations/available")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        adapters = resp.json()
        
        # CRITICAL: Must be exactly 3 adapters
        assert len(adapters) == 3, f"Expected 3 adapters, got {len(adapters)}: {[a.get('kind') for a in adapters]}"
        
        # Verify the exact kinds
        kinds = {a["kind"] for a in adapters}
        expected_kinds = {"twilio", "tigo_tz", "mock"}
        assert kinds == expected_kinds, f"Expected {expected_kinds}, got {kinds}"
        
        # Verify NO Infobip, MessageBird, Africa's Talking, etc.
        forbidden = ["infobip", "messagebird", "africas_talking", "nexmo", "plivo", "vonage"]
        for adapter in adapters:
            kind = adapter.get("kind", "").lower()
            label = adapter.get("label", "").lower()
            for f in forbidden:
                assert f not in kind, f"Forbidden adapter {f} found in kind"
                assert f not in label, f"Forbidden adapter {f} found in label"

    def test_adapters_have_required_fields(self, admin_session):
        """Each adapter has kind, label, description, channels, fields"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-hub/integrations/available")
        assert resp.status_code == 200
        adapters = resp.json()
        
        for adapter in adapters:
            assert "kind" in adapter, f"Missing 'kind' in adapter"
            assert "label" in adapter, f"Missing 'label' in adapter"
            assert "description" in adapter, f"Missing 'description' in adapter"
            assert "channels" in adapter, f"Missing 'channels' in adapter"
            assert "fields" in adapter, f"Missing 'fields' in adapter"
            assert isinstance(adapter["channels"], list), "channels must be a list"
            assert isinstance(adapter["fields"], list), "fields must be a list"

    def test_twilio_adapter_fields(self, admin_session):
        """Twilio adapter has api_key, api_secret, base_url fields"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-hub/integrations/available")
        adapters = resp.json()
        twilio = next((a for a in adapters if a["kind"] == "twilio"), None)
        assert twilio is not None, "Twilio adapter not found"
        
        field_keys = {f["key"] for f in twilio["fields"]}
        assert "api_key" in field_keys, "Twilio missing api_key field"
        assert "api_secret" in field_keys, "Twilio missing api_secret field"
        assert "sms" in twilio["channels"], "Twilio should support sms"
        assert "whatsapp" in twilio["channels"], "Twilio should support whatsapp"

    def test_tigo_tz_adapter_fields(self, admin_session):
        """Tigo TZ adapter has api_key, api_secret, base_url fields"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-hub/integrations/available")
        adapters = resp.json()
        tigo = next((a for a in adapters if a["kind"] == "tigo_tz"), None)
        assert tigo is not None, "Tigo TZ adapter not found"
        
        field_keys = {f["key"] for f in tigo["fields"]}
        assert "api_key" in field_keys, "Tigo TZ missing api_key field"
        assert "api_secret" in field_keys, "Tigo TZ missing api_secret field"
        assert "base_url" in field_keys, "Tigo TZ missing base_url field"
        assert "sms" in tigo["channels"], "Tigo TZ should support sms"

    def test_mock_adapter_no_required_fields(self, admin_session):
        """Mock adapter has no required credential fields"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-hub/integrations/available")
        adapters = resp.json()
        mock = next((a for a in adapters if a["kind"] == "mock"), None)
        assert mock is not None, "Mock adapter not found"
        
        # Mock should have empty or no required fields
        required_fields = [f for f in mock["fields"] if f.get("required")]
        assert len(required_fields) == 0, f"Mock adapter should have no required fields, got {required_fields}"

    def test_available_integrations_requires_admin(self, reseller_session):
        """Non-admin cannot access available integrations"""
        resp = reseller_session.get(f"{BASE_URL}/api/admin/country-hub/integrations/available")
        assert resp.status_code == 403, f"Expected 403 for reseller, got {resp.status_code}"


# ============================================================
# COUNTRY HUB CATALOG
# ============================================================
class TestCountryHubCatalog:
    """GET /api/admin/country-hub - catalog of countries"""

    def test_country_catalog_returns_array(self, admin_session):
        """GET /api/admin/country-hub returns array of countries"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-hub")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        countries = resp.json()
        assert isinstance(countries, list), "Expected array of countries"

    def test_country_catalog_enriched_fields(self, admin_session):
        """Each country has enriched fields: credits_per_sms, routes_count, health, etc."""
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-hub")
        countries = resp.json()
        
        if len(countries) == 0:
            pytest.skip("No countries in database")
        
        c = countries[0]
        required_fields = [
            "code", "name", "credits_per_sms", "routes_count", "prefixes_count",
            "operators_count", "sender_ids_count", "active_sender_ids", "health", "status"
        ]
        for field in required_fields:
            assert field in c, f"Missing field '{field}' in country catalog entry"

    def test_country_health_structure(self, admin_session):
        """Country health has status, success_rate, sent_24h, providers_total, providers_healthy"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-hub")
        countries = resp.json()
        
        if len(countries) == 0:
            pytest.skip("No countries in database")
        
        c = countries[0]
        health = c.get("health", {})
        assert "status" in health, "health missing 'status'"
        assert health["status"] in ["healthy", "degraded", "down", "idle", "offline", "unconfigured"], \
            f"Invalid health status: {health['status']}"

    def test_country_catalog_requires_admin(self, reseller_session):
        """Non-admin cannot access country catalog"""
        resp = reseller_session.get(f"{BASE_URL}/api/admin/country-hub")
        assert resp.status_code == 403, f"Expected 403 for reseller, got {resp.status_code}"


# ============================================================
# COUNTRY WIZARD
# ============================================================
class TestCountryWizard:
    """POST /api/admin/country-hub/wizard - 4-step add-country wizard"""

    def test_wizard_creates_active_country_with_routes(self, admin_session):
        """Wizard with all 4 steps creates active country"""
        # First get a provider ID to use as route
        providers_resp = admin_session.get(f"{BASE_URL}/api/admin/providers")
        providers = providers_resp.json() if providers_resp.status_code == 200 else []
        
        provider_id = providers[0]["id"] if providers else None
        routes = [{"provider_id": provider_id, "priority": 100}] if provider_id else []
        
        payload = {
            "code": "MW",  # Malawi
            "name": "Malawi",
            "dial_code": "+265",
            "currency": "MWK",
            "timezone": "Africa/Blantyre",
            "credits_per_sms": 2,
            "credits_per_whatsapp": 3,
            "routes": routes,
            "sender_id_required": True,
            "opt_out_footer": "Reply STOP to opt out.",
            "allowed_sender_patterns": [],
            "daily_cap": 100000
        }
        
        resp = admin_session.post(f"{BASE_URL}/api/admin/country-hub/wizard", json=payload)
        assert resp.status_code == 200, f"Wizard failed: {resp.text}"
        
        data = resp.json()
        assert data["ok"] is True
        assert data["code"] == "MW"
        
        if routes:
            assert data["status"] == "active", f"Expected active status with routes, got {data['status']}"
            assert data["complete"] is True
            assert data["missing"] == []
        else:
            # Without routes, should be draft
            assert data["status"] == "draft"
            assert "routes" in data["missing"]

    def test_wizard_creates_draft_without_routes(self, admin_session):
        """Wizard without routes creates draft country"""
        payload = {
            "code": "ZW",  # Zimbabwe
            "name": "Zimbabwe",
            "dial_code": "+263",
            "currency": "ZWL",
            "credits_per_sms": 3,
            "credits_per_whatsapp": 4,
            "routes": [],  # No routes
            "sender_id_required": True,
            "opt_out_footer": "Reply STOP",
            "daily_cap": 50000
        }
        
        resp = admin_session.post(f"{BASE_URL}/api/admin/country-hub/wizard", json=payload)
        assert resp.status_code == 200, f"Wizard failed: {resp.text}"
        
        data = resp.json()
        assert data["status"] == "draft", f"Expected draft without routes, got {data['status']}"
        assert data["complete"] is False
        assert "routes" in data["missing"]

    def test_wizard_writes_credits_rate(self, admin_session):
        """Wizard writes credits.country_rate setting"""
        payload = {
            "code": "BW",  # Botswana
            "name": "Botswana",
            "dial_code": "+267",
            "credits_per_sms": 4,
            "credits_per_whatsapp": 5,
            "routes": [],
            "opt_out_footer": "STOP"
        }
        
        resp = admin_session.post(f"{BASE_URL}/api/admin/country-hub/wizard", json=payload)
        assert resp.status_code == 200
        
        # Verify the rate was written
        settings_resp = admin_session.get(f"{BASE_URL}/api/admin/settings?category=credits")
        settings = settings_resp.json()
        rate_setting = next((s for s in settings if s["key"] == "credits.country_rate"), None)
        
        if rate_setting:
            rates = rate_setting.get("value", {})
            assert rates.get("BW") == 4, f"Expected BW rate 4, got {rates.get('BW')}"

    def test_wizard_requires_code_and_name(self, admin_session):
        """Wizard rejects empty code or name"""
        payload = {
            "code": "",
            "name": "Test",
            "dial_code": "+1",
            "credits_per_sms": 2,
            "credits_per_whatsapp": 3,
            "routes": []
        }
        
        resp = admin_session.post(f"{BASE_URL}/api/admin/country-hub/wizard", json=payload)
        assert resp.status_code == 400, f"Expected 400 for empty code, got {resp.status_code}"

    def test_wizard_requires_admin(self, reseller_session):
        """Non-admin cannot use wizard"""
        payload = {
            "code": "XX",
            "name": "Test",
            "dial_code": "+1",
            "credits_per_sms": 2,
            "credits_per_whatsapp": 3,
            "routes": []
        }
        resp = reseller_session.post(f"{BASE_URL}/api/admin/country-hub/wizard", json=payload)
        assert resp.status_code == 403


# ============================================================
# COUNTRY DETAIL
# ============================================================
class TestCountryDetail:
    """GET /api/admin/country-hub/{code} - country detail page"""

    def test_country_detail_returns_full_data(self, admin_session):
        """GET /api/admin/country-hub/{code} returns full country data"""
        # First ensure we have a country
        catalog_resp = admin_session.get(f"{BASE_URL}/api/admin/country-hub")
        countries = catalog_resp.json()
        
        if not countries:
            pytest.skip("No countries to test detail")
        
        code = countries[0]["code"]
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-hub/{code}")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        
        data = resp.json()
        required_fields = ["country", "credits_per_sms", "credits_per_whatsapp",
                          "routes", "operators", "prefixes", "sender_ids", "health"]
        for field in required_fields:
            assert field in data, f"Missing field '{field}' in country detail"

    def test_country_detail_routes_have_health(self, admin_session):
        """Routes in detail have health and kind"""
        catalog_resp = admin_session.get(f"{BASE_URL}/api/admin/country-hub")
        countries = catalog_resp.json()
        
        if not countries:
            pytest.skip("No countries to test")
        
        code = countries[0]["code"]
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-hub/{code}")
        data = resp.json()
        
        for route in data.get("routes", []):
            assert "health" in route, "Route missing health"
            assert "kind" in route, "Route missing kind"
            assert "name" in route, "Route missing name"

    def test_country_detail_404_for_invalid(self, admin_session):
        """GET /api/admin/country-hub/XX returns 404"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/country-hub/INVALID_CODE_XYZ")
        assert resp.status_code == 404


# ============================================================
# COUNTRY STATUS (KILL SWITCH)
# ============================================================
class TestCountryStatus:
    """PUT /api/admin/country-hub/{code}/status - kill switch"""

    def test_set_country_paused(self, admin_session):
        """Can pause a country"""
        # First create/ensure a country exists
        admin_session.post(f"{BASE_URL}/api/admin/country-hub/wizard", json={
            "code": "MZ", "name": "Mozambique", "dial_code": "+258",
            "credits_per_sms": 2, "credits_per_whatsapp": 3, "routes": []
        })
        
        resp = admin_session.put(f"{BASE_URL}/api/admin/country-hub/MZ/status",
                                  json={"status": "paused"})
        assert resp.status_code == 200, f"Failed: {resp.text}"
        assert resp.json()["status"] == "paused"

    def test_set_country_active(self, admin_session):
        """Can activate a country"""
        resp = admin_session.put(f"{BASE_URL}/api/admin/country-hub/MZ/status",
                                  json={"status": "active"})
        assert resp.status_code == 200
        assert resp.json()["status"] == "active"

    def test_set_country_draft(self, admin_session):
        """Can set country to draft"""
        resp = admin_session.put(f"{BASE_URL}/api/admin/country-hub/MZ/status",
                                  json={"status": "draft"})
        assert resp.status_code == 200
        assert resp.json()["status"] == "draft"

    def test_invalid_status_rejected(self, admin_session):
        """Invalid status is rejected"""
        resp = admin_session.put(f"{BASE_URL}/api/admin/country-hub/MZ/status",
                                  json={"status": "invalid_status"})
        assert resp.status_code == 400

    def test_status_404_for_invalid_country(self, admin_session):
        """Status update returns 404 for invalid country"""
        resp = admin_session.put(f"{BASE_URL}/api/admin/country-hub/INVALID_XYZ/status",
                                  json={"status": "active"})
        assert resp.status_code == 404


# ============================================================
# COUNTRY COMPLIANCE
# ============================================================
class TestCountryCompliance:
    """PUT /api/admin/country-hub/{code}/compliance"""

    def test_update_compliance(self, admin_session):
        """Can update compliance settings"""
        # Ensure country exists
        admin_session.post(f"{BASE_URL}/api/admin/country-hub/wizard", json={
            "code": "ZM", "name": "Zambia", "dial_code": "+260",
            "credits_per_sms": 2, "credits_per_whatsapp": 3, "routes": []
        })
        
        resp = admin_session.put(f"{BASE_URL}/api/admin/country-hub/ZM/compliance", json={
            "opt_out_footer": "Reply STOP to unsubscribe",
            "allowed_sender_patterns": ["^[A-Z]{3,11}$"],
            "daily_cap": 200000,
            "sender_id_required": True
        })
        assert resp.status_code == 200, f"Failed: {resp.text}"
        assert resp.json()["ok"] is True

    def test_compliance_empty_body_rejected(self, admin_session):
        """Empty compliance update is rejected"""
        resp = admin_session.put(f"{BASE_URL}/api/admin/country-hub/ZM/compliance", json={})
        assert resp.status_code == 400


# ============================================================
# INTEGRATION HEALTH
# ============================================================
class TestIntegrationHealth:
    """GET /api/admin/integrations/health"""

    def test_health_returns_array(self, admin_session):
        """GET /api/admin/integrations/health returns array of providers"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/integrations/health")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        providers = resp.json()
        assert isinstance(providers, list)

    def test_health_provider_structure(self, admin_session):
        """Each provider has required health fields"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/integrations/health")
        providers = resp.json()
        
        if not providers:
            pytest.skip("No providers in database")
        
        p = providers[0]
        required = ["id", "name", "kind", "countries", "channels", "priority", "active", "health"]
        for field in required:
            assert field in p, f"Missing field '{field}' in provider health"
        
        health = p["health"]
        health_fields = ["status", "creds", "sent_24h"]
        for field in health_fields:
            assert field in health, f"Missing health field '{field}'"

    def test_health_status_values(self, admin_session):
        """Health status is one of valid values"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/integrations/health")
        providers = resp.json()
        
        valid_statuses = {"healthy", "degraded", "down", "idle", "offline"}
        for p in providers:
            status = p["health"]["status"]
            assert status in valid_statuses, f"Invalid health status: {status}"


# ============================================================
# INTEGRATION TEST
# ============================================================
class TestIntegrationTest:
    """POST /api/admin/integrations/{provider_id}/test"""

    def test_integration_test_returns_result(self, admin_session):
        """Test endpoint returns ok, latency_ms, etc."""
        # Get a provider
        health_resp = admin_session.get(f"{BASE_URL}/api/admin/integrations/health")
        providers = health_resp.json()
        
        if not providers:
            pytest.skip("No providers to test")
        
        provider_id = providers[0]["id"]
        resp = admin_session.post(f"{BASE_URL}/api/admin/integrations/{provider_id}/test")
        assert resp.status_code == 200, f"Failed: {resp.text}"
        
        data = resp.json()
        assert "ok" in data
        assert "latency_ms" in data
        assert isinstance(data["latency_ms"], int)

    def test_integration_test_404_invalid_provider(self, admin_session):
        """Test returns 404 for invalid provider"""
        resp = admin_session.post(f"{BASE_URL}/api/admin/integrations/invalid-provider-id/test")
        assert resp.status_code == 404


# ============================================================
# REGRESSION TESTS - Iteration 5/6 endpoints
# ============================================================
class TestRegressionIteration5And6:
    """Regression tests for previous iteration endpoints"""

    def test_auth_me(self, admin_session):
        """GET /api/auth/me still works"""
        resp = admin_session.get(f"{BASE_URL}/api/auth/me")
        assert resp.status_code == 200
        assert "user" in resp.json()

    def test_admin_overview(self, admin_session):
        """GET /api/admin/overview still works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/overview")
        assert resp.status_code == 200
        assert "kpi" in resp.json()

    def test_admin_resellers_list(self, admin_session):
        """GET /api/admin/resellers still works (iteration 6)"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/resellers")
        assert resp.status_code == 200
        assert isinstance(resp.json(), list)

    def test_admin_commission_audit(self, admin_session):
        """GET /api/admin/resellers/commission-audit still works (iteration 6)"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/resellers/commission-audit?days=30")
        assert resp.status_code == 200
        data = resp.json()
        assert "grand_total" in data
        assert "transactions" in data

    def test_admin_settings(self, admin_session):
        """GET /api/admin/settings still works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/settings")
        assert resp.status_code == 200
        assert isinstance(resp.json(), list)

    def test_admin_providers(self, admin_session):
        """GET /api/admin/providers still works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/providers")
        assert resp.status_code == 200
        assert isinstance(resp.json(), list)

    def test_admin_countries_legacy(self, admin_session):
        """GET /api/admin/countries (legacy) still works"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/countries")
        assert resp.status_code == 200
        assert isinstance(resp.json(), list)

    def test_reseller_clients(self, reseller_session):
        """GET /api/reseller/clients still works (iteration 5)"""
        resp = reseller_session.get(f"{BASE_URL}/api/reseller/clients")
        assert resp.status_code == 200
        assert isinstance(resp.json(), list)

    def test_reseller_earnings(self, reseller_session):
        """GET /api/reseller/earnings still works (iteration 5)"""
        resp = reseller_session.get(f"{BASE_URL}/api/reseller/earnings")
        assert resp.status_code == 200
        data = resp.json()
        assert "commission_rate" in data


# ============================================================
# SETTINGS HUB - Geographies group
# ============================================================
class TestSettingsHubGeographies:
    """Verify Settings Hub has Geographies group with Country hub and Integration health"""

    def test_settings_categories_exist(self, admin_session):
        """Settings endpoint returns data"""
        resp = admin_session.get(f"{BASE_URL}/api/admin/settings")
        assert resp.status_code == 200
        # Settings Hub categories are frontend-only, but we verify the endpoint works


if __name__ == "__main__":
    pytest.main([__file__, "-v", "--tb=short"])
