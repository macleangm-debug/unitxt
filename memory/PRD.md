# unitxt — PRD

**Last updated**: 2026-04-20 (iteration 10)
**Version**: 1.9 (Country packs · Auto-clean · Delivery dashboard · Column-map wizard)

## Implemented so far (cumulative)
### v1.0 → v1.4 (prior)
Three portals · JWT+RBAC · Credits economy · smart batching (10k in 21s) · scheduled worker · operator-aware routing · referrals · streaks · DLR webhook push · WhatsApp templates · Excel import · reseller commission (clients pay retail, resellers earn from admin margin) · Settings Hub v2 (grouped + search) · Admin Reseller Workspace (catalog + drawer + policy + audit).

### v1.5 (Country Hub + Integration Health)
- Country Hub catalog + 4-step Add-country wizard (draft until routes set). Country detail with 5 tabs + kill switch. Integration Health dashboard with health chips + Test button. Add-integration wizard lists ONLY actually-implemented adapters (Twilio, Tigo TZ, Mock) — explicit banner, no defaults.

### v1.6 (Grouped admin sidebar)
- Admin nav grouped into sections. Superseded in v1.7.

### v1.7 (Ops-only admin + unified approvals + public apply + country economics) — **this iteration**
- **Admin is now operations-only**, sidebar collapsed to **3 groups**:
  - **Platform ops**: Users, Resellers, Institutions, Approvals
  - **Insights**: Overview, Margin & revenue, Audit logs
  - **Configuration**: Settings hub (contains Country hub, Integrations, Routing, Providers, Prefixes, Countries, Pricing, Credit packs, Promotions — everything that was scattered across the old top nav).
- **No more user-activity pages on admin**: Campaigns, standalone Pricing/CreditPacks/Promotions/Routing/Providers/Prefixes/CountryHub/Integrations/WhatsApp were removed from top nav and live inside Settings hub.
- **Unified Approvals inbox** at `/admin/approvals` — 4 tabs (Sender IDs, WhatsApp templates, Reseller applications, Institution applications) with count badges, stats row, and a drawer-based review flow (Approve/Reject + internal note).
- **Public apply pages**: `/apply/reseller` and `/apply/institution` — no-auth marketing + form pages. Reseller highlights 15% commission/0% overcharge/instant payout. Institution highlights 200k+ queue/<30s delivery/real-time DLR/99.9% uptime. Approvals appear in admin inbox; approving reseller auto-provisions a reseller user with temp password + `reseller_code`; approving institution promotes into `institutions` collection.
- **Users page** rewritten with drawer pattern: suspend/reactivate toggle, profile (name/role) edit, credit-adjust (positive or negative with note).
- **Country Hub** now TABLE format (not cards) with inline Manage CTA per row; Add-country wizard unchanged.
- **Country detail** gains an **Economics tab** showing unit economics (credits/SMS, retail USD/SMS, weighted cost min/max/avg, margin USD & %, providers count), editable country rate, editable global `economy.usd_per_credit`, and a P&L table (1/7/30/90 day windows with sends/credits/revenue/cost/margin/margin%). **Operators tab** gets Add prefix + delete CTAs; **Sender IDs tab** gets inline Approve/Reject.
- **Credits ↔ USD economics model**: global `economy.usd_per_credit` (default $0.01) drives every country's retail USD/SMS = `credits_per_sms × usd_per_credit`. Cost avg pulled from active providers' `cost_per_sms`. Margin = retail − cost. Fully auditable via `/api/admin/country-hub/{code}/economics`.
- **Copy cleanup**: setting keys humanized on Settings hub UI (e.g. `credits.default_rate` → "Credits default rate", `reseller.kyc_required` → "Reseller KYC required" with KYC kept uppercase). Original mono-typed key remains below as reference.
- **10+ new backend endpoints**: approvals inbox, public apply × 2, admin review × 2, country economics, country rate update, prefix CRUD per country, `economy.usd_per_credit` setting.
- **28/28 backend tests pass**; full frontend verified; iteration 5/6/7 regressions clean.
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
