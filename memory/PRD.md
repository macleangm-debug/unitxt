# unitxt — PRD

**Last updated**: 2026-04-18 (iteration 5)
**Version**: 1.3 (Reseller commission + Settings Hub v2)

## Implemented so far (cumulative)
### v1.0 (first ship)
- Three portals (Client, Reseller, Admin), JWT+RBAC, messaging engine (quick/bulk/scheduled), pluggable providers, wallets, promos, sender IDs, Settings Hub basics, 12 admin pages.

### v1.1 (credits economy)
- Credits-based wallet everywhere (TZ=1, KE=2, US=5, WhatsApp=3, sender ID=500, renewal=500, expiry=365d). 4 credit packs. Tigo TZ adapter (stub). Conversational 4-step Quick Send wizard. DLR webhook. Queue w/ concurrency. Margin & revenue report. Inactivity policy. 65 seeded mobile prefixes with lookup.

### v1.2 (scale + distribution)
- Smart batching queue (10,000 msgs in 21s, 100% delivery). Scheduled-campaign worker. Client referrals (loss-proof, 5% of pack capped at 500). Send-streak gamification (+100/+1,000/+5,000 at 7/30/90 days). Operator-aware routing via mobile_prefixes. WhatsApp template workflow. DLR webhook push. Excel import for mobile prefixes.

### v1.3 (distribution economics + UI polish) — **this iteration**
- **Reseller commission model** — replaces markup. Clients ALWAYS pay the global retail rate; resellers earn a configurable commission (0.0–1.0) paid out of the admin's margin on every client send. Commission resolved per request in this order: country+channel override → default (*+channel) override → reseller's `commission_rate` on user doc → global setting `pricing.reseller_commission_default` (0.15).
- **Reseller pricing page** is now read-only. Resellers see their default commission + any country/channel overrides. All markup-setting UI removed.
- **Admin-controlled commission endpoints**: `GET/POST/DELETE /api/admin/resellers/{id}/commissions` + `PUT /api/admin/resellers/{id}/default-commission`. Legacy POST `/api/reseller/pricing` now returns 405.
- **Startup migration** retires legacy `reseller_pricing` records that carried `markup` without `commission_rate`.
- **Settings Hub v2** — refactored from a flat 16-item sidebar into 5 logical groups (Platform & Onboarding, Economy, Messaging engine, Governance & lifecycle, Distribution & treasury) with a live search that filters keys/values. Categories show icons, descriptions, count badges. Module-link categories (Providers, Countries, Pricing, Wallets, Sender IDs, Institutions, Promotions) render an "Open module" CTA rather than inline setting editors.
- **E2E verified** (iteration 5): client bulk-send of 50 TZ SMS → client charged exactly 50 credits (base), reseller earned 10 credits (20% commission override). 18/18 new backend tests pass; full regression on prior 134 endpoints — no breakage.

## What's still mocked / pending user input
- **Tigo TZ SMS API** — the doc supplied (`TIGO-VPN Form-Filled.docx`) is IPsec networking only; no HTTP spec. Waiting on actual SMS API docs.
- **Twilio real REST** — stub ready; user to provide Account SID + Auth Token.
- **Stripe Checkout** — pack purchase credits wallet directly; user to confirm checkout style.

## Backlog
### P0
- Real Twilio + Tigo wiring (credentials/docs pending)
- Real Stripe Checkout session wiring
- WhatsApp send via approved templates (currently mock path)

### P1
- Country-Admin scoped views
- CSV column-mapping wizard in Bulk Send
- Opt-out / DND list
- Spam-keyword guard enforcement on message body
- Daily send-limit enforcement per client
- Admin UI for reseller commission management (endpoints exist; build the page)

### P2
- AI fraud detection
- Voice + email channels
- Quality-aware smart routing (delivery-rate feedback)
- Multi-currency pack purchasing + FX

## Settings Hub categories (now 17, grouped into 5 groups)
- **Platform & Onboarding**: platform, onboarding
- **Economy**: credits, pricing_cfg (reseller margin), referrals, streaks
- **Messaging engine**: queue, providers →, countries →, pricing →, sender_ids →
- **Governance & lifecycle**: compliance, inactivity, notifications
- **Distribution & treasury**: wallets →, institutions →, promotions →

## Test credentials
See `/app/memory/test_credentials.md`.
