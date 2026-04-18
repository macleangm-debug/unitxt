# unitxt — Product Requirements Document

**Last updated**: 2026-04-18
**Version**: 1.1 (Credits OS + routing intelligence)

## Vision
A configurable global bulk SMS + WhatsApp operating system. Multi-tenant, multi-country, multi-provider. Platform behaviour driven from a central Settings Hub. Three portals share a pluggable routing engine, **credits-based billing**, and notification spine.

## What's implemented

### v1.0 — 2026-04-17 (first ship)
- Three portals (Client, Reseller, Admin) with premium dark UI
- JWT auth + RBAC (7 role types)
- Messaging engine (quick send, bulk with merge tags, scheduled, campaign tracking)
- Pluggable provider adapters (Mock + Twilio stub)
- Wallet, transfers, promo codes (USD-based)
- Sender ID request/approve workflow
- 12 admin pages incl. Settings Hub (11 categories)
- 12 countries, 3 providers, 3 pricing plans seeded
- 100% backend / 98% frontend tests passing

### v1.1 — 2026-04-18 (credits & intelligence)
- **Credits economy** — wallet balance is integer credits, not USD. TZ=1cr, KE=2cr, US=5cr, WhatsApp=3cr, sender ID creation=500cr, renewal=500cr. All configurable in Settings Hub → Credits.
- **Credit packs** — 4 seeded retail tiers (Starter 1k/$15, Growth 10k/$120, Scale 100k/$1k, Enterprise 1M/$8.5k). Client can buy any pack. Admin manages full catalog.
- **Mobile prefixes** — 65 auto-seeded prefixes across 14 African + global countries (TZ: Vodacom, Tigo, Airtel, Halotel, TTCL, Zantel; KE: Safaricom, Airtel, Telkom; UG, RW, ZM, GH, NG, ZA, SN, CI, ET, EG, MA + US/UK/IN/AE). Admin UI: list, filter, lookup, add, bulk CSV import.
- **Tigo Tanzania adapter** — structured stub ready for VPN credentials. Seeded as priority-1 provider for TZ.
- **Conversational Quick Send** — 4-step wizard ("Hi {name} — let's send something.", "Who are we reaching?", etc.) with name personalization and success celebration screen.
- **Delivery-status lifecycle** — messages move through queued→sent→delivered/failed via adapter. DLR webhook at `POST /api/dlr/{provider_id}` updates statuses asynchronously.
- **Queue engine** — per-provider concurrency (default 50) via asyncio Semaphore, up to 2 retries with backoff, per-message USD cost tracking into `platform_revenue_log`.
- **Margin & revenue report** — Admin → Margin page shows revenue (pack USD) vs provider cost vs margin %, with daily line chart and by-country / by-provider breakdowns.
- **Inactivity policy** — background loop flags users inactive after 60 days (warn at 30). Inactive users pay 1,000 credits via `/api/credits/recover` to restore access. All thresholds in Settings Hub → Inactivity policy.
- **Sender ID expiry** — default 365 days. Background loop flags expired. `POST /api/sender-ids/{id}/renew` charges 500 credits.
- **Admin credit packs CRUD** at `/admin/credit-packs`.
- 41/41 new backend tests pass.

## Architecture notes
- **Backend**: one `server.py` (~2.1kLOC), FastAPI, MongoDB. Routers: auth, wallet, credits, contacts, sender_ids, templates, messaging, reseller, admin, notifications, api_keys, prefixes, dlr, admin (credit packs + margin).
- **Frontend**: React + react-router v7 + Tailwind + shadcn/ui + recharts + sonner. Custom dark aesthetic (Manrope + IBM Plex Sans + JetBrains Mono).
- **Provider pattern**: `ProviderAdapter.send()` → MockAdapter | TwilioAdapter | TigoTZAdapter.
- **Routing**: `pick_provider(country, channel)` returns highest-priority active provider. Next step: operator-aware routing using mobile prefixes.

## What's still mocked
- **SMS send** goes through MockAdapter unless provider.name contains "twilio" or "tigo". Real Twilio/Tigo calls are stubs until credentials provided.
- **Pack purchase** credits the wallet directly (no Stripe Checkout yet).

## Prioritized backlog
### P0 — unlock go-live
- Real Twilio REST integration (when user provides Account SID + Auth Token)
- Real Tigo TZ VPN integration (when user shares docs)
- Stripe Checkout for pack purchase (webhook credits wallet)
- Scheduled campaign worker (drain `schedule_at <= now` every minute)

### P1 — depth
- Operator-aware routing (use mobile prefixes to choose provider per operator)
- Excel import for prefixes (today: CSV)
- Reseller "set client pricing" UI
- Bulk send CSV column-mapping wizard
- Opt-out / DND list handling
- WhatsApp template (HSM) approval workflow
- Webhook delivery reports pushed to client-configured URLs
- Country Admin scoped views

### P2 — frontier
- AI fraud detection
- Omnichannel voice + email
- Quality-aware smart routing
- Multi-currency pack purchasing + FX

## Next tasks
1. User to share Tigo TZ VPN docs → wire real adapter body.
2. User to provide Twilio credentials → wire Twilio REST.
3. Build Stripe Checkout flow for pack purchases.
4. Add scheduled campaign background worker.
5. Add operator-aware routing (use `phone_to_operator` inside `pick_provider`).

## Test credentials
See `/app/memory/test_credentials.md` — demo reseller has 500,000 credits, demo client has ~129k after self-tests.
