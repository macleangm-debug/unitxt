# unitxt — PRD

**Last updated**: 2026-04-18 (iteration 4)
**Version**: 1.2 (Scale engine + distribution economics)

## Implemented so far (cumulative)
### v1.0 (first ship)
- Three portals (Client, Reseller, Admin), JWT+RBAC, messaging engine (quick/bulk/scheduled), pluggable providers, wallets, promos, sender IDs, Settings Hub basics, 12 admin pages.

### v1.1 (credits economy)
- Credits-based wallet everywhere (TZ=1, KE=2, US=5, WhatsApp=3, sender ID=500, renewal=500, expiry=365d). 4 credit packs (Starter/Growth/Scale/Enterprise). Tigo TZ adapter (stub). Conversational 4-step Quick Send wizard. DLR webhook. Queue w/ concurrency. Margin & revenue report. Inactivity policy. 65 seeded African+global mobile prefixes with lookup.

### v1.2 (scale + distribution) — **this iteration**
- **Smart batching queue**: processes 100k-200k recipients per campaign. Batches of 1,000 (configurable), per-provider semaphore (default 200 concurrent), credit reservation upfront with auto-refund of unused, `insert_many` bulk writes, progress_pct live-updated every batch. **Locally tested: 10,000 recipients delivered in 21 seconds, 100% delivery.**
- **Scheduled-campaign worker**: `background_loop` drains campaigns where `schedule_at <= now` every 60 seconds. Verified.
- **Client referrals (loss-proof)**: each user has a unique referral_code; new users register with it; when they *buy a pack*, the referrer is automatically credited **5% of pack credits capped at 500** (configurable). Rewards come from pack revenue, never the referrer's balance. Full audit in `referral_earnings`.
- **Send streak gamification**: auto-bumps on campaign completion. Milestones pay **+100 / +1,000 / +5,000** credits at 7/30/90 days, idempotent via `streak_awards`. Flame icon widget on client dashboard.
- **Operator-aware routing**: `pick_provider_for(country, channel, operator)` uses the existing `mobile_prefixes` to detect operator per recipient; Tigo provider is seeded with `operators=["Tigo"]` so TZ/71/65/67 numbers hit Tigo direct.
- **Reseller markup pricing**: Reseller → Client pricing page. Multiplier per country+channel (default/wildcard `*` supported). Clients charged `base × markup`; markup delta credited to reseller's wallet on every send.
- **WhatsApp template workflow**: Client submits HSM template → admin approves/rejects → client notified. Ready to be wired to Meta/Twilio template APIs later.
- **DLR webhook push to client systems**: user configures `dlr_webhook_url` + `dlr_webhook_secret`; engine fires-and-forgets JSON POST to client URL per message update. Webhook settings page in client portal.
- **Excel import for mobile prefixes**: frontend now accepts `.csv`, `.xlsx`, `.xls` via SheetJS, converts to CSV, feeds existing import endpoint.
- **134/134 backend tests pass** (43 new + 91 prior).

## What's still mocked / pending user input
- **Tigo TZ VPN call body** — stub ready; user to share docs.
- **Twilio real REST** — stub ready; user to provide Account SID + Auth Token.
- **Stripe Checkout** — pack purchase credits wallet directly; user to confirm checkout style.

## Backlog
### P0
- Real Twilio + Tigo wiring (credentials pending)
- Real Stripe Checkout (credentials available; needs wiring session)
- WhatsApp send channel using approved templates (today WhatsApp sends go through mock; once approved templates flow to Twilio WA Business, use those)

### P1
- Country Admin scoped views
- CSV column-mapping wizard in Bulk send
- Opt-out / DND list
- Spam-keyword guard on message body (setting already exists)
- Daily send limit enforcement per client (setting exists)

### P2
- AI fraud detection
- Voice + email channels
- Quality-aware smart routing (delivery-rate feedback loop)
- Multi-currency pack purchasing + FX

## Settings Hub categories (now 16)
platform, credits, referrals, streaks, inactivity, queue, onboarding, compliance, notifications, providers (→ page), countries (→ page), pricing (→ page), wallets (→ page), sender_ids (→ page), institutions (→ page), promotions (→ page)

## Test credentials
See `/app/memory/test_credentials.md`. Demo reseller has 500k credits, demo client has active sender ID + many credits from tests.
