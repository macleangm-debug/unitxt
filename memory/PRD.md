# unitxt — PRD

**Last updated**: 2026-04-26 (iteration 16)
**Version**: 1.15 (Affiliate self-service portal · Public promo-code attribution · One-shot primary-code rename)

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

## Backlog
### P0 (blocked on creds / decisions)
- Real Twilio · Real Tigo TZ · Real Stripe · WhatsApp send via approved templates

### P1
- Affiliate portal UI (rewrite reseller portal — Codes/Referrals/Earnings/Payouts pages on top of new endpoints)
- Public Apply page promo-code field for affiliate sign-ups
- Per-Contact-Group send stats
- Automated failure-reason email alerts (15-min loop)
- Opt-out / DND / spam-keyword / daily-send-limit enforcement
- Snapshot `cost_pre_vat_local` + `revenue_local` on each message at send-time (so P&L is exact, not derived) — currently P&L falls back to economics × delivered count

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
