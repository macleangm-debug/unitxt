# unitxt — PRD

**Last updated**: 2026-04-25 (iteration 12)
**Version**: 1.11 (Customer-acquisition copy · Routes split · Settings-hub tunables)

## Implemented so far (cumulative)
### v1.0 → v1.4 (prior)
Three portals · JWT+RBAC · Credits economy · smart batching (10k in 21s) · scheduled worker · operator-aware routing · referrals · streaks · DLR webhook push · WhatsApp templates · Excel import · reseller commission (clients pay retail, resellers earn from admin margin) · Settings Hub v2 (grouped + search) · Admin Reseller Workspace.

### v1.5 — Country Hub + Integration Health
Country Hub catalog + 4-step wizard, Integration Health dashboard, Add-integration wizard restricted to implemented adapters only (Twilio, Tigo TZ, Mock).

### v1.7 — Ops-only admin + unified approvals + public apply + country economics
Ops-only admin sidebar with 3 groups. Unified Approvals inbox. Public `/apply/reseller` and `/apply/institution`. Users drawer. Country Hub as table + Economics tab. Credits ↔ USD model.

### v1.8 — Validation + auto-clean + delivery dashboard + column wizard
Number lookup (smart + HLR stub) · Contact auto-clean · Delivery-rate dashboard · CSV/Excel column-mapping wizard.

### v1.9 — Country packs + FX + promotions
Country-scoped credit packs. Per-country FX rates. Country promotions.

### v1.10 — Bank transfers + Pre-flight + Saved CSV mappings
Bank accounts registry + bank-transfer top-up E2E (proof upload, admin approves). Wave A pre-flight free sample validation. Wave B per-user saved CSV mappings.

### v1.11 — Routes split + customer copy + Settings-Hub tunables — **this iteration**
- **Routes refactor (phase 1)**: extracted recently authored routers from `server.py` into `/app/backend/routes/`:
  - `routes/banks.py` — `admin_r` (CRUD) + `client_r` (scoped lookup)
  - `routes/topups.py` — `client_r` (submit/list/proof) + `admin_r` (list/detail/review)
  - `routes/messaging_extras.py` — preflight + csv-mappings under `/messaging`
  Pattern proven; phase 2 will extract the remaining 20 routers (auth, wallet, contacts, numbers, sender_ids, msg_r, adm_r, country_hub, applications, etc.) one cluster at a time.
- **Settings Hub source-of-truth**: every constant added in iter 11 is now a tunable Settings Hub key:
  - `messaging.preflight_free_sample` (default 20)
  - `messaging.preflight_red_threshold` (50%)
  - `messaging.preflight_amber_threshold` (80%)
  - `topups.max_proof_kb` (4096)
- **Public copy rewrite**: Landing + Login pages now focus on customer outcomes — "Reach every customer. In every country.", "Send your next campaign in under a minute." Removed all internal-architecture jargon ("operating system", "control room", "configurable spine", "Bloomberg of SMS", "CMS"). Feature card copy now talks about deliverability, scale, and revenue, not implementation details.
- **'Made with Emergent' badge removed** from `/app/frontend/public/index.html`.
- **Settings Hub UI**: bottom banner pill renamed `CONFIG SPINE` → `CONFIGURATION`.
- **Testing (iteration 12)**: 25/25 new tests + 43/43 regression tests pass. See `/app/test_reports/iteration_12.json`.

## What's still mocked / pending user input
- **Tigo TZ SMS API** — adapter stub; waiting on HTTP spec.
- **Twilio real REST** — needs Account SID + Auth Token.
- **Stripe Checkout** — credit-pack purchase wiring; user to confirm hosted vs embedded.
- **Integration health ping** — derived from in-house traffic + creds state (simulated).

## Backlog
### P0 (blocked on creds / decisions)
- Real Twilio wiring
- Real Tigo TZ wiring
- Real Stripe Checkout
- WhatsApp send via approved templates

### P1
- Per-group send stats (last send, delivery rate, top failure reasons per Contact Group)
- Automated failure-reason alerts (background job every 15 mins → email admin if delivery < 80%)
- Opt-out / DND list enforcement (data already stored)
- Spam-keyword guard enforcement (data already stored)
- Daily send-limit enforcement (data already stored)
- Country-Admin scoped views

### P2
- AI fraud detection
- Voice + email channels
- Quality-aware smart routing (delivery-rate feedback loop)
- Real-time per-provider health pings via partner status endpoints
- Institutions API Integrations (Phase 2)

### Refactor (Phase 2 of routes split)
Remaining router clusters to extract from `server.py`:
- `routes/auth.py` — auth_r (~150 lines)
- `routes/wallet.py` — wallet_r
- `routes/contacts.py` — contacts_r
- `routes/numbers.py` — num_r (smart validation, HLR stub)
- `routes/messaging.py` — msg_r (quick-send, bulk-send, campaigns) — biggest, most cautious
- `routes/sender_ids.py` — sid_r
- `routes/templates.py` — tpl_r
- `routes/wa_templates.py` — wa_r
- `routes/admin.py` — adm_r (huge — split further into admin_users, admin_resellers, admin_audit)
- `routes/country_hub.py` — country_r + int_r
- `routes/approvals.py` — approv_r + app_r + pub_r (apply pages)
- `routes/credits.py` — credits_r
- `routes/dlr.py` — dlr_r
- `routes/profile.py` — prof_r
- `routes/referrals.py` — ref_r
- `routes/reseller.py` — res_r + res_px_r + adm_res_px_r
- `routes/notifications.py` — notif_r
- `routes/api_keys.py` — key_r
- `routes/prefixes.py` — prefix_r

Then `core.py` for shared helpers (db, auth deps, scheduler, workers, models).

## Settings Hub layout (current)
- **Platform & Onboarding** — platform, onboarding
- **Economy** — credits, pricing_cfg, credit_packs →, referrals, streaks
- **Geographies** — country_hub →, integrations →, routing →, providers →, prefixes →, countries →, pricing →
- **Messaging engine** — queue (incl. new preflight tunables), sender_ids →
- **Governance & lifecycle** — compliance (incl. topups.max_proof_kb), inactivity, notifications
- **Distribution & treasury** — resellers →, reseller_policy, wallets →, banks →, institutions →, promotions →

## Test credentials
See `/app/memory/test_credentials.md`.
