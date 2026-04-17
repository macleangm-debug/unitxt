# unitxt — Product Requirements Document

**Last updated**: 2026-04-17
**Version**: 1.0 (MVP live)

## Vision
A configurable global bulk SMS + WhatsApp operating system. Multi-tenant, multi-country, multi-provider. Most platform behaviour is driven from a central Settings Hub — not hardcoded. Three portals (Client, Reseller, Admin) share a pluggable routing engine, wallet/billing layer, and notification spine.

## Original problem statement
The user specified:
- Multi-role: Super Admin, Country Admin, Reseller, Client, Staff/Support/Finance/Compliance
- Settings Hub with Global, Country, Provider, Pricing, Wallet, Sender ID, Institution, Notification, Compliance, Promotion settings
- Routing engine (country → provider priority, failover)
- Wallet, billing, pricing engine
- Messaging engine (quick/bulk/scheduled/personalized)
- Institution integrations (banks, mobile money, fintechs, CRMs)
- Provider adapter pattern (local TZ telco + Twilio + Infobip etc.)
- "World-class" UI, notification bell, configurable promotions
- Settings Hub should feel "exquisite"

## Personas
1. **Super Admin** — platform owner. Configures countries, providers, pricing, settings. Approves sender IDs. Sees KPIs.
2. **Reseller** — white-label operator. Funds clients, earns margin.
3. **Client** — end business. Sends SMS/WhatsApp campaigns.
4. **Staff/Support/Finance/Compliance** — scoped admin roles (role exists; specific UIs can be added).

## Architecture
- **Backend**: FastAPI (one `server.py`), MongoDB (UUID ids), JWT auth with httpOnly cookies + Bearer fallback, RBAC via `require_roles` dependency, pluggable `ProviderAdapter` (MockAdapter + TwilioAdapter stub), async campaign execution via `asyncio.create_task`.
- **Frontend**: React + react-router-dom v7, TailwindCSS + shadcn/ui primitives, recharts for charts, sonner for toasts. Custom dark "command-center" design per `/app/design_guidelines.json` (Manrope + IBM Plex Sans + JetBrains Mono; obsidian palette with signal colors).
- **Design**: dark, high-contrast Swiss grid. Tracing-beam active sidebar. Sharp bordered cards (no heavy shadows). Mono for all numbers.

## What's implemented (v1.0 — 2026-04-17)
### Backend (100% backend tests pass)
- Auth: register / login / logout / me / refresh / forgot / reset. Bcrypt, JWT, brute-force lockout, httpOnly cookies, seeded admin + demo reseller + demo client.
- Wallet: balance, transactions, top-up (MOCKED — applies promo codes WELCOME10 / BONUS25).
- Messaging: quick-send, bulk-send (with merge tags), campaigns list + detail, messages list, stats aggregation. Routing engine picks highest-priority active provider per country+channel. Segment counting. Async execution.
- Provider adapter: MockAdapter (95% delivery), TwilioAdapter (stub, returns failure without creds).
- Contacts (CRUD + import), Templates (CRUD), Sender ID requests (submit + admin review), API keys.
- Reseller: list clients with wallet balance, transfer credits, earnings, referral code.
- Admin: overview KPIs, users CRUD + credit, sender ID approval, countries CRUD, providers CRUD, pricing CRUD, institutions CRUD, promotions CRUD, settings key/value upsert, audit logs, wallets list, campaigns list.
- Notifications: list + mark read. Auto-created for signups, transfers, SID reviews, topups, campaign completions, etc.
- Seed: 12 countries, 3 providers (TZ Direct, Twilio, Infobip), 3 pricing plans, 2 promotions, 2 institutions, 1 approved sender ID, 10 default settings keys.

### Frontend (98% frontend tests pass)
- Landing page (hero, portal cards, features, CTA).
- Login / Register with demo account fill.
- AppShell with role-aware sidebar (tracing-beam active), topbar with wallet display + notification bell + user menu.
- **Client portal (10 pages)**: Dashboard, Quick Send, Bulk Send, Campaigns, Contacts, Sender IDs, Templates, Wallet, Reports (bar + pie charts), API Keys.
- **Reseller portal**: Dashboard, Clients (transfer modal), Earnings + reuses client pages for sending.
- **Admin portal (13 pages)**: Overview, Users, Providers, **Routing engine** visualization, Countries, Pricing, Sender IDs queue, Wallets, Campaigns, Institutions, Promotions, Audit logs, **Settings Hub**.
- **Settings Hub**: left rail of 11 categories (Platform, Onboarding, Compliance, Notifications, Providers, Countries, Pricing, Wallets, Sender IDs, Institutions, Promotions). Auto-renders editors based on value type. Each module category links out to its dedicated workspace.
- Notification bell with pulsing badge, dropdown, tabs-style unread count.

## Core requirements (static)
- Configurable country onboarding (no code changes)
- Pluggable provider adapter
- Multi-role auth + RBAC
- Pluggable promotion engine
- Wallet-based billing with reseller float
- Sender ID approval workflow
- Settings-driven behaviour

## Prioritized backlog
### P0 (next)
- Real Twilio credentials wiring (user to provide) — right now adapter is stub
- Real Stripe wallet top-up (currently mocked) — will need user to pick flow (Stripe Checkout recommended)
- Scheduled campaign background worker (currently schedule_at is stored but no cron — campaigns only run if `schedule_at` is falsy)

### P1
- Institution linking flow (users link their CRM / bank via the institution config)
- Country Admin scoped views
- Reseller "set client pricing" UI
- CSV bulk import preview with column mapping wizard
- Opt-out / DND list handling
- Rate limiting per client per day per country

### P2
- Smart routing optimization (quality-aware)
- AI fraud detection
- Omnichannel expansion (WhatsApp templates, voice, email)
- Webhook callbacks for delivery reports to client systems
- Multi-currency wallets + FX

## Next tasks
1. Ask user for Twilio credentials and Stripe go-live decision.
2. Wire Scheduled-campaign worker (APScheduler or asyncio periodic task).
3. Add "Set client pricing" UI in reseller portal.
4. Harden: rate limiting, spam keyword check on messages, retention cleanup on old campaigns.
