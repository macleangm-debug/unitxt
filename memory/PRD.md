# unitxt — PRD

**Last updated**: 2026-04-22 (iteration 11)
**Version**: 1.10 (Bank transfers · Pre-flight · Saved CSV mappings)

## Implemented so far (cumulative)
### v1.0 → v1.4 (prior)
Three portals · JWT+RBAC · Credits economy · smart batching (10k in 21s) · scheduled worker · operator-aware routing · referrals · streaks · DLR webhook push · WhatsApp templates · Excel import · reseller commission (clients pay retail, resellers earn from admin margin) · Settings Hub v2 (grouped + search) · Admin Reseller Workspace (catalog + drawer + policy + audit).

### v1.5 — Country Hub + Integration Health
Country Hub catalog + 4-step wizard, Integration Health dashboard, Add-integration wizard restricted to implemented adapters only (Twilio, Tigo TZ, Mock).

### v1.6 — Grouped admin sidebar (superseded in v1.7).

### v1.7 — Ops-only admin + unified approvals + public apply + country economics
Ops-only admin sidebar with 3 groups. Unified Approvals inbox (sender IDs, WA templates, reseller & institution apps). Public `/apply/reseller` and `/apply/institution`. Users drawer. Country Hub as table + Economics tab. Credits ↔ USD model (`economy.usd_per_credit`). Plain-English Settings hub labels. 28/28 backend tests pass.

### v1.8 — Validation + auto-clean + delivery dashboard + column wizard
Number lookup (smart + HLR stub) · Contact auto-clean · Delivery-rate dashboard with top failure reasons · CSV/Excel column-mapping wizard (one-off).

### v1.9 — Country packs + FX + promotions
Country-scoped credit packs. Per-country FX rates drive local-currency display. Country promotions.

### v1.10 — Bank transfers + Pre-flight + Saved CSV mappings — **this iteration**
- **Bank accounts registry (admin)**: new `/admin/banks` page with CRUD (country, bank name, account name/number, branch, SWIFT, currency, plain-English instructions, active flag). Seeded with CRDB Bank (TZ). Exposed as a module under Settings Hub → Distribution & treasury.
- **Bank transfer top-up flow**: client picks a pack → "Pay by bank transfer" modal shows local-currency amount + receiving bank details → uploads receipt (base64, max 4MB) → submits. `POST /api/wallet/topups` creates a pending `topup_request` with locked-in FX rate. Admin sees a new **Top-up requests** tab in `/admin/approvals` (with count badge). Review drawer now displays the proof image. Approve credits the wallet (`tx_kind=topup_bank`, honors promo codes). Reject leaves the wallet unchanged. Clients see their history in `/client/wallet → My top-up requests`.
- **Wave A — Pre-flight check** in Bulk send: one-click "Validate sample before sending" runs smart-validation on up to 20 spread-sampled numbers **for free** (no credit deduction). Returns per-number results plus summary card: Checked N of M, Deliverable %, Predicted wasted credits, amber warning when deliverability < 80% / red when < 50%, green "safe to send" otherwise.
- **Wave B — Saved CSV mappings**: per-user presets. After the Column-Map modal runs, a new "Save current mapping" CTA writes a named preset (`CRM Export`, etc.). A new "Saved column maps" card on the right rail lists presets with Apply/Delete. The Column-Map modal itself surfaces compatible presets at the top for one-click reuse. Endpoints: `GET/POST/DELETE /api/messaging/csv-mappings`, `POST /api/messaging/csv-mappings/{id}/used`.
- **Testing (iteration 11)**: 43/43 backend tests pass, all frontend UI flows verified. See `/app/test_reports/iteration_11.json`.

## What's still mocked / pending user input
- **Tigo TZ SMS API** — adapter exists as stub; waiting on HTTP spec/endpoints.
- **Twilio real REST** — adapter skeleton; needs real Account SID + Auth Token.
- **Stripe Checkout** — credit-pack purchase wiring; user to confirm hosted vs embedded.
- **Integration health ping** — currently derived from in-house traffic + creds state.

## Backlog
### P0
- Real Twilio wiring (creds pending)
- Real Tigo TZ wiring (API docs pending)
- Real Stripe Checkout session
- WhatsApp send via approved templates

### P1
- Per-group send stats (last send, delivery rate, top failure reasons per Contact Group)
- Automated failure-reason alerts (background job every 15 mins → email admin if delivery rate < 80%)
- Opt-out / DND list (referenced in country compliance)
- Spam-keyword guard enforcement
- Daily send-limit enforcement (already stored per country)
- Country-Admin scoped views

### P2
- AI fraud detection
- Voice + email channels
- Quality-aware smart routing (delivery-rate feedback loop)
- Real-time per-provider health pings via partner status endpoints
- Institutions API Integrations (Phase 2)

### Refactor
- `/app/backend/server.py` is ~4,300 lines — consider splitting into `routes/`, `models/`, `workers/`.

## Settings Hub layout (current)
- **Platform & Onboarding** — platform, onboarding
- **Economy** — credits, pricing_cfg (reseller margin), credit_packs →, referrals, streaks
- **Geographies** — country_hub →, integrations →, routing →, providers →, prefixes →, countries →, pricing →
- **Messaging engine** — queue, sender_ids →
- **Governance & lifecycle** — compliance, inactivity, notifications
- **Distribution & treasury** — resellers →, reseller_policy, wallets →, **banks → 🆕**, institutions →, promotions →

## Test credentials
See `/app/memory/test_credentials.md`.
