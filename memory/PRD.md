# unitxt — PRD

**Last updated**: 2026-05-05 (iteration 20)
**Version**: 1.19 (SMPP transport for Tigo TZ · Compliance enforcement · Active provider probes)

## Implemented so far (cumulative)
### v1.0 → v1.10
Three portals · JWT+RBAC · Credits economy · smart batching · scheduled worker · operator-aware routing · referrals · streaks · DLR webhooks · WhatsApp templates · Excel import · Country Hub + Integration Health · Ops admin + unified approvals + public apply · Number lookup + auto-clean + delivery dashboard + column wizard · Country packs + FX + promotions · Bank transfers + Pre-flight + Saved CSV mappings.

### v1.11 — Routes split + Settings-Hub tunables + Public copy
Extracted banks/topups/messaging-extras into `/app/backend/routes/`. Added preflight + topup proof-KB tunables. Public copy rewrite. 25/25 + 43/43 tests pass.

### v1.12 — Corporate UI + Grouped sidebars
Plus Jakarta Sans + zinc + blue accent system. Grouped sidebars all 3 portals.

### v1.13 — In-app API docs + Mobile-friendly Wallet
4-tab API integrator console (Keys / Quick start / Endpoints / Sender ID flow) with cURL/Python/Node code snippets and Sender ID lifecycle. Wallet redesigned for mobile: collapsible sections, table → card-list views, Bank Pay modal polish.

### v1.14 — Country economics + Affiliate program backend
Country VAT + sell + wholesale fields per country, per-route `buy_price_local_pre_vat`, Country P&L dashboard, affiliate program backend with 3 commission models. 21/21 + 100% pass.

### v1.15 — Affiliate self-service portal — **this iteration**
- **Affiliate portal** (replaces the old reseller portal):
  - **Dashboard** — primary code hero with one-click share-link copy, 4-stat overview (referrals, earned, in-review, paid), plain-English "How you earn" banner that adapts to the active commission model, recent earnings, quick-action sidebar.
  - **Promo codes** — primary code with one-shot rename (modal warns "you can only do this once"); after rename, the card flips to a "customised · Locked" pill and a `Lock` icon. Secondary codes CRUD up to a configurable cap (default 5), each with optional note + use-count + per-card copy.
  - **Referrals** — table (desktop) / card list (mobile) of referred users with country, sign-up date, top-up count, lifetime spend, and commission earned per user.
  - **Earnings** — ledger with 4 status filter tabs (All / Available / In review / Paid), per-row top-up amount, model used, and status pill.
  - **Payouts** — available-balance hero, "Request payout" CTA disabled below threshold, modal with 3 method options (bank / mobile money / crypto) and method-specific fields, history table/card list.
- **Public registration** — `/register?ref=CODE` shows a green "Promo applied" banner on the left rail and pre-fills + LOCKS the promo code field. Without `?ref` the field is freely editable. Account-type selector now shows "Send messages" or "Earn as affiliate".
- **One-shot primary-code rename** — backend endpoint `POST /api/affiliate/primary-code` enforces:
  - 3–24 character range
  - cross-platform uniqueness (vs other affiliate codes, other users' referral codes, and reserved promotion codes)
  - one-shot via `users.primary_code_renamed` flag — second attempt returns plain-English 400
- **Auto-minted default codes** — every newly registered `reseller`/`affiliate` gets a unitxt-generated default promo code (a U-prefixed 7-char hex) so they can start sharing immediately.
- **Sidebar regrouped** for the reseller role: Workspace · Affiliate (Promo codes / Referrals / Earnings / Payouts) · Send · Assets · Account.
- **Bug fix (caught by test agent)**: commission idempotency was keyed on `pack_id` so only the first purchase of each pack earned commission; now keyed on unique payment id.
- **Testing (iteration 16)**: 11/11 backend + 100% frontend pass. See `/app/test_reports/iteration_16.json`.
- **Country economics in local currency** (no more USD math for admins):
  - Per country: `vat_rate_pct`, `sell_per_sms_local`, `wholesale_per_sms_local` (currency + FX rate already existed)
  - New endpoints: `GET /api/admin/country-economics/{code}`, `PUT /api/admin/country-economics/{code}`
  - **UI**: "Local-currency economics" card on Country Hub Detail → Economics tab, with **live margin preview**:
    > *"clients in TZ pay TZS 20 per SMS. Wholesale TZS 15 + 18% VAT = TZS 17.70 true cost. Gross profit per SMS = TZS 2.30 (11.5%)"*
  - Per-route `buy_price_local_pre_vat` field on providers (true cost computed at runtime)
- **Per-country P&L dashboard** (`/admin/country-pnl`):
  - Aggregated revenue/cost/profit/margin per country, sourced from delivered messages
  - Total profit in USD across all countries
  - Empty-state when no eligible deliveries
- **Affiliate program v1** (replaces legacy referral reward):
  - **3 configurable commission models** (default = Time window):
    1. **Time window** — earn % on every paid top-up within N months (default: 6mo @ 10%)
    2. **First N top-ups** — earn % on first N paid top-ups (default: 3 top-ups @ 10%)
    3. **Tier bonus** — % on first top-up + USD bonuses when referee crosses spend tiers (default: 15% first + $20 at $200 + $50 at $1000, 12-month window)
  - **Welcome bonus** to referred user on first paid top-up: 5% of pack credits, capped at 500 credits (configurable)
  - **Affiliate codes**: each affiliate can mint up to 5 promo codes; codes own attribution; legacy `users.referral_code` still resolved as fallback
  - **Commission ledger** (`db.affiliate_earnings`): tracks every commission with status `earned` → `requested` → `paid`
  - **Payouts**: affiliate requests payout once they cross threshold ($50 default); admin reviews and approves/rejects
  - **Settings Hub**: full config under "Distribution & treasury → Affiliate program" (`/admin/affiliate`):
    - 4-stat overview strip (affiliates, referred users, lifetime earned, pending payouts)
    - 3 model selector cards (clickable, blue highlight on active)
    - Model-specific field set (changes when you change model)
    - Welcome bonus card · Payout threshold card · Save button
    - Pending payouts table with approve/reject drawer
- **Settings Hub additions** in the right groups (kept neat & clean):
  - Economy → Country P&L
  - Distribution & treasury → Affiliate program
- **Testing (iteration 15)**: 21/21 backend + 100% frontend tests pass. See `/app/test_reports/iteration_15.json`.

## What's still mocked / pending user input
- **Tigo TZ SMS API** — adapter stub; waiting on HTTP spec.
- **Twilio real REST** — needs Account SID + Auth Token.
- **Stripe Checkout** — credit-pack purchase wiring.

## v1.16 — Phase-2 routes split + Per-Contact-Group stats + Affiliate local-currency
- Extracted `notifications`, `api_keys`, `prefixes` into `/app/backend/routes/`
- New endpoint `GET /api/contacts/groups/{gid}/stats` + Stats modal on `/client/contacts`
- Affiliate dashboard + earnings now show local-currency previews (e.g. "≈ TZS 14,300")
- Iteration 17 testing: 25/25 backend + 100% frontend pass.

## v1.17 — Cost/revenue snapshots + Automated route-health alerts — **this iteration**
- **Per-message snapshots at send-time** (no more derivation in P&L). Every message in `db.messages` now carries:
  - `currency`, `fx_rate_to_usd`, `vat_rate_pct`
  - `cost_pre_vat_local` (provider buy × segments)
  - `cost_incl_vat_local` (cost_pre_vat_local × (1 + VAT))
  - `revenue_local` (sell_per_sms_local × segments)
  - `country` (denormalised from campaign)
  Same fields are mirrored on `platform_revenue_log` so legacy USD reports keep working.
- **Country P&L** now sources from message-level snapshots — exact, not estimated. The legacy "× delivered count" fallback still kicks in only for pre-snapshot messages.
- **Automated route-health monitor**: every 15 min the background loop aggregates delivery rate per provider over the rolling window. Below threshold → in-app warning notification to all super_admins, with top failure reasons embedded in the body. Per-provider 60-min cool-down to prevent spam.
- **Settings keys** (configurable via `PUT /api/admin/settings`):
  - `alerts.route_health_enabled` (bool, default true)
  - `alerts.route_health_window_min` (int, default 15)
  - `alerts.route_health_min_msgs` (int, default 10)
  - `alerts.route_health_threshold_pct` (int, default 80)
  - `alerts.route_health_cooldown_min` (int, default 60)
- **New helper** `set_setting(key, value)` for use by background workers.
- **Testing (iteration 18)**: 23/23 new backend + 25/25 regression. See `/app/test_reports/iteration_18.json`.

## v1.18 — Phase-3 routes split + Settings Hub route-health panel — **this iteration**
- **Phase-3 routes split** — six more routers extracted from `server.py` (now 3,951 lines, down from 4,170):
  - `routes/credits.py` — `/api/credits/{packs,rates,buy,recover}` (client pack purchase + recovery)
  - `routes/dlr.py` — `/api/dlr/{provider_id}` (delivery-receipt webhook)
  - `routes/referrals.py` — `/api/referrals/me` (legacy invite codes, kept for compat)
  - `routes/profile.py` — `/api/profile/{streak,webhook}` (streak overview + DLR webhook config)
  - `routes/wa_templates.py` — `/api/wa-templates` and `/api/admin/wa-templates` (client + admin WA template review)
  - `routes/country_economics.py` — `/api/admin/country-economics/{code}` and `/api/admin/country-pnl`
- **Settings Hub UI** — new `alerts.route_health_*` keys auto-render under Notifications tab (Governance & lifecycle group). The existing generic editor handles boolean/number editors out of the box, so no custom panel was needed. Runtime keys (`last_run_at`, per-provider `last_alert.{pid}`) are deliberately uncategorised so they never clutter the admin UI.
- One-shot migration moves any pre-existing route-health settings from category `alerts` → `notifications` so they show in the right tab.
- **Testing (iteration 19)**: 32/32 phase-3 + 25/25 iter-17 + 23/23 iter-18 regression all pass. See `/app/test_reports/iteration_19.json`.

## v1.19 — SMPP transport + Compliance + Active probes — **this iteration**
- **SMPP transport (direct telco binds)**:
  - New provider field `transport: "http" | "smpp"` plus a full SMPP credential block: `smpp_host`, `smpp_port`, `smpp_system_id`, `smpp_password`, `smpp_system_type`, `smpp_bind_mode` (tx/rx/trx), `smpp_use_tls`, `smpp_source_ton`, `smpp_source_npi`, `smpp_dest_ton`, `smpp_dest_npi`, `smpp_throughput_per_sec`, `smpp_window_size`, `smpp_notes`.
  - `SMPPOutboxAdapter` enqueues each send as a row in `db.smpp_outbox` and returns `status="queued"`.
  - **Standalone relay daemon** at `/app/backend/smpp_relay/` (relay.py + README.md + systemd unit) — deployed on the VPN-attached host (e.g. Datavision-YTS Server `41.220.143.37` for Tigo TZ), holds the SMPP TRX bind, drains the outbox, writes DLRs back via `provider_msg_id` correlation, and heartbeats `providers.last_smpp_heartbeat` every 30 s.
  - **Tigo specifics from VPN form**: SMSC = `smpp01.tigo.co.tz:10501`, accessible only via IPSec from `41.220.143.37/32`.
- **Compliance enforcement** (gates every quick-send / bulk-send before reservation):
  - **Spam keyword block** — body containing any keyword from `compliance.spam_keywords` returns HTTP 400 with the keyword surfaced in the error.
  - **Daily send limit** — `compliance.daily_send_limit` truncates over-cap recipients; full block returns 400.
  - **Opt-out filter** — phones in `db.opt_outs` are silently dropped from the recipient list. All-opted batches return HTTP 400.
  - **Inbound STOP / UNSUBSCRIBE / UNSUB / OPTOUT / CANCEL** keywords on `POST /api/optout/inbound` auto-add the sender to the opt-out list. Non-STOP inbound is logged in `db.inbound_messages`.
  - **Opt-out admin** — new page `/admin/opt-out` (count + manual add + table) plus tile in Settings Hub → Governance & lifecycle.
- **Active provider probes**:
  - Background loop now runs `_active_probe_providers` every 60 s.
  - HTTP providers: HEAD/GET `${base_url}/healthz` — 5xx = down, 4xx = degraded.
  - SMPP providers: heartbeat staleness — >180 s = down, >90 s = degraded.
  - Routing (`pick_provider_for`) skips `probe_status="down"` providers; falls back to ignore-probe if all are down.
  - Settings keys: `alerts.active_probe_enabled`, `alerts.active_probe_interval_sec`, `alerts.active_probe_http_timeout_sec`.
- **Testing (iteration 20)**: 35/35 new backend + 32/32 iter-19 + 23/23 iter-18 + 25/25 iter-17 regression. Frontend SMPP form & Opt-out page verified. See `/app/test_reports/iteration_20.json`.

## Backlog
### P0 (blocked on creds / decisions)
- Real Twilio · Real Tigo TZ · Real Stripe · WhatsApp send via approved templates

### P1
- Phase-4 routes split (msg_r, adm_r, adm_r2 — the heaviest remaining ~2,000 lines)
- **Deploy SMPP relay daemon** on Datavision-YTS Server (production handover)
- Email channel for route-health alerts (currently in-app only)
- Inbound DLR/STOP routing per-provider — wire individual providers to call `/api/optout/inbound`
- Operator-aware SMPP routing (currently routes by country only when SMPP is the picked transport)

### P2
- AI fraud detection · Voice + email channels · Quality-aware smart routing · Real-time provider health pings · Institutions API integrations Phase 2

### Refactor (Phase 2 of routes split)
Extract remaining ~20 routers from `server.py` into `/app/backend/routes/`. Pull shared helpers into `core.py`.

## Settings Hub layout (current, neat & clean)
- **Platform & Onboarding** — platform, onboarding
- **Economy** — credits, pricing_cfg, credit_packs →, **country_pnl →** ★, referrals, streaks
- **Geographies** — country_hub →, integrations →, routing →, providers →, prefixes →, countries →, pricing →
- **Messaging engine** — queue (preflight tunables), sender_ids →
- **Governance & lifecycle** — compliance (incl. topups.max_proof_kb), inactivity, notifications
- **Distribution & treasury** — **affiliate →** ★, resellers →, reseller_policy, wallets →, banks →, institutions →, promotions →

## Test credentials
See `/app/memory/test_credentials.md`.
