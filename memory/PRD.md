# unitxt — PRD

**Last updated**: 2026-04-20 (iteration 6)
**Version**: 1.4 (Admin Reseller Workspace)

## Implemented so far (cumulative)
### v1.0 → v1.3 (prior)
Three portals · JWT+RBAC · Credits economy · smart batching (10k in 21s) · scheduled worker · operator-aware routing · referrals · streaks · DLR webhook push · WhatsApp templates · Excel import · reseller commission model (clients pay retail, resellers earn from admin margin) · Settings Hub v2 with 5 grouped sections + search. See CHANGELOG below for details.

### v1.4 (admin reseller workspace) — **this iteration**
- **Admin Reseller Workspace** at `/admin/resellers`, surfaced inside Settings Hub → Distribution group. 3 tabs:
  - **Reseller catalog** — stats (count, total float, clients, lifetime commission), search, table with every reseller (float balance, clients_count, lifetime commission, status). Click any row to open a drawer.
  - **Global policy** — inline editors for `reseller.default_commission`, `reseller.kyc_required`, `reseller.min_float_topup`, `reseller.max_sub_clients`, `reseller.allow_invite_clients`, `onboarding.reseller_signup_open` and `pricing.reseller_commission_default`.
  - **Commission audit** — windowed ledger (7/30/90/365 days) with grand total, per-reseller totals, transaction list.
- **Per-reseller drawer**: float top-up / clawback, default commission editor, country×channel commission overrides CRUD, status toggle (active/suspended), sub-clients list, copy referral code.
- **New backend endpoints**: `GET /api/admin/resellers`, `GET /api/admin/resellers/{id}/detail`, `POST /api/admin/resellers/{id}/float`, `PUT /api/admin/resellers/{id}/status`, `GET /api/admin/resellers/commission-audit`.
- **32/32 backend tests pass**; full frontend verified.

## Upcoming (Phase 2 — Country Hub, confirmed by user)
Per user feedback "configuration per country, routes, partners, integration health, all in Settings Hub":
- **Country catalog** cards (flag/ISO/rate/health/routes/senderIDs) + "Add country" 4-step wizard (identity → pricing → routes → compliance). Mandatory setup; country stays in draft until complete.
- **Country detail** with live health score, ordered route table (priority, operators, cost, success %, toggle), partners/integrations with "Test connection" button, operators & prefixes, sender IDs, compliance (opt-out footer, patterns, caps), kill switch.
- **Integration health dashboard** — every provider × every country with simulated health pings (upgrade to real later).
- **Add integration wizard** — Twilio / Infobip / MessageBird / Africa's Talking / Tigo / custom HTTP.

## Blocked / pending user input
- **Tigo TZ SMS API** — VPN doc supplied has no HTTP spec; waiting on actual SMS API docs.
- **Twilio real REST** — stub ready; needs Account SID + Auth Token.
- **Stripe Checkout** — pack purchase wiring; user to confirm hosted vs embedded style.

## Backlog
### P0
- Country Hub (Phase 2, see above)
- Real Twilio + Tigo wiring (credentials/docs pending)
- Real Stripe Checkout session wiring
- WhatsApp send via approved templates

### P1
- CSV column-mapping wizard in Bulk Send
- Opt-out / DND list
- Spam-keyword guard enforcement
- Daily send-limit enforcement per client

### P2
- AI fraud detection
- Voice + email channels
- Quality-aware smart routing
- Multi-currency pack purchasing + FX

## Settings Hub layout (current)
- **Platform & Onboarding** — platform, onboarding
- **Economy** — credits, pricing_cfg (reseller margin), referrals, streaks
- **Messaging engine** — queue, providers →, countries →, pricing →, sender_ids →
- **Governance & lifecycle** — compliance, inactivity, notifications
- **Distribution & treasury** — resellers →, reseller_policy, wallets →, institutions →, promotions →

## Test credentials
See `/app/memory/test_credentials.md`.
