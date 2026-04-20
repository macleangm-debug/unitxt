# unitxt — PRD

**Last updated**: 2026-04-20 (iteration 8)
**Version**: 1.6 (Grouped admin sidebar)

## Implemented so far (cumulative)
### v1.0 → v1.4 (prior)
Three portals · JWT+RBAC · Credits economy · smart batching (10k in 21s) · scheduled worker · operator-aware routing · referrals · streaks · DLR webhook push · WhatsApp templates · Excel import · reseller commission (clients pay retail, resellers earn from admin margin) · Settings Hub v2 (grouped + search) · Admin Reseller Workspace (catalog + drawer + policy + audit).

### v1.5 (Country Hub + Integration Health)
- Country Hub catalog + 4-step Add-country wizard (draft until routes set). Country detail with 5 tabs + kill switch. Integration Health dashboard with health chips + Test button. Add-integration wizard lists ONLY actually-implemented adapters (Twilio, Tigo TZ, Mock) — explicit banner, no defaults.

### v1.6 (Grouped admin sidebar) — **this iteration**
- **Admin nav reorganized** from a flat 20-item list into 6 logical groups with section headers:
  - **Overview** — Overview, Margin & revenue, Audit logs
  - **Geographies** — Country hub, Integration health, Mobile prefixes
  - **Messaging** — Routing engine, Providers, Campaigns, Sender IDs, WhatsApp approvals
  - **Economy** — Pricing, Credit packs, Wallets, Promotions
  - **Distribution** — Resellers, Institutions, Users
  - **System** — Countries (legacy), Settings hub
- `renderNav()` helper handles both flat (client/reseller) and grouped (admin) nav structures — no impact on client/reseller sidebars.
- **Country Hub** at `/admin/country-hub` — catalog of country cards (flag, ISO, dial code, credit rate, route count, sender IDs, live health chip). Stats row (countries / active / healthy / unconfigured). Search + refresh.
- **4-step Add-country wizard** — Identity → Pricing → Routes → Compliance. Mandatory setup: country stays **draft** until at least one route is selected. On complete submit, writes `credits.country_rate[code]` setting, wires providers via `$addToSet` on `countries`, creates the country with `status=active`. Incomplete saves produce `status=draft, missing=[...]`.
- **Country detail** at `/admin/country-hub/{code}` — 5 tabs (Overview, Routes, Operators & prefixes, Sender IDs, Compliance), kill-switch/activate button, per-route Test connection button with latency report, live country-level health (derived from 24h message success rate across providers).
- **Integration Health dashboard** at `/admin/integrations` — table of every provider × country it covers with health chip (healthy / degraded / down / idle / offline), creds presence pill, 24h volume, 1-click Test button. Stats row. Info card explaining the health formula (≥90% healthy, 50-90% degraded, <50% down with traffic, idle otherwise).
- **Add-integration wizard** — Step 1 picker lists **ONLY adapters actually implemented** in the backend (`INTEGRATED_ADAPTERS` list: Twilio, Tigo TZ, Mock). Info banner clearly states no defaults/marketing partners. Step 2 dynamic form pulls fields from the selected adapter's manifest (Account SID / Auth Token / Username / Password / VPN URL as appropriate). Users pick countries to cover from already-onboarded list.
- **New backend endpoints**: `GET /api/admin/country-hub`, `GET /api/admin/country-hub/integrations/available`, `POST /api/admin/country-hub/wizard`, `GET /api/admin/country-hub/{code}`, `PUT /api/admin/country-hub/{code}/status`, `PUT /api/admin/country-hub/{code}/compliance`, `GET /api/admin/integrations/health`, `POST /api/admin/integrations/{id}/test`.
- **Health signal**: `compute_provider_health()` aggregates last 24h `messages` per provider_id, factors in creds + active flag. `country_health_summary()` rolls up all providers for a country.
- **Settings Hub layout** gains a new **Geographies** group exposing Country hub → and Integration health → as module links.
- **40/40 backend tests pass**; full frontend verified (including critical check: no Infobip/MessageBird/Africa's Talking leaks into the wizard).

## What's still mocked / pending user input
- **Tigo TZ SMS API** — adapter exists as stub; waiting on HTTP spec/endpoints.
- **Twilio real REST** — adapter skeleton; needs real Account SID + Auth Token to call live API.
- **Stripe Checkout** — pack purchase wiring; user to confirm hosted vs embedded.
- **Integration health ping** — currently derived from in-house traffic + creds state (simulated). Upgrade to per-provider real health endpoint once adapters go live.

## Backlog
### P0
- Real Twilio wiring (creds pending)
- Real Tigo TZ wiring (API docs pending)
- Real Stripe Checkout session
- WhatsApp send via approved templates

### P1
- CSV column-mapping wizard in Bulk Send
- Opt-out / DND list (referenced in country compliance)
- Spam-keyword guard enforcement
- Daily send-limit enforcement (already stored per country)
- Country-Admin scoped views

### P2
- AI fraud detection
- Voice + email channels
- Quality-aware smart routing (delivery-rate feedback loop)
- Multi-currency pack purchasing + FX
- Real-time per-provider health pings via partner status endpoints

## Settings Hub layout (current)
- **Platform & Onboarding** — platform, onboarding
- **Economy** — credits, pricing_cfg (reseller margin), referrals, streaks
- **Geographies** 🆕 — country_hub →, integrations →
- **Messaging engine** — queue, providers →, countries →, pricing →, sender_ids →
- **Governance & lifecycle** — compliance, inactivity, notifications
- **Distribution & treasury** — resellers →, reseller_policy, wallets →, institutions →, promotions →

## Test credentials
See `/app/memory/test_credentials.md`.
