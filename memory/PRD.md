# unitxt — PRD

**Last updated**: 2026-04-25 (iteration 13)
**Version**: 1.12 (Corporate redesign · Grouped sidebars · Blue accent system)

## Implemented so far (cumulative)
### v1.0 → v1.4
Three portals · JWT+RBAC · Credits economy · smart batching · scheduled worker · operator-aware routing · referrals · streaks · DLR webhook push · WhatsApp templates · Excel import · reseller commission · Settings Hub v2 · Admin Reseller Workspace.

### v1.5 → v1.10
Country Hub + Integration Health · Ops-only admin + unified approvals + public apply pages · Number lookup + auto-clean + delivery dashboard + column wizard · Country packs + FX + promotions · Bank transfers + Pre-flight + Saved CSV mappings.

### v1.11 — Routes split + customer copy + Settings-Hub tunables
- Routes refactor phase 1: extracted `routes/banks.py`, `routes/topups.py`, `routes/messaging_extras.py` from monolithic `server.py`.
- Settings Hub tunables: `messaging.preflight_free_sample`, `messaging.preflight_red_threshold`, `messaging.preflight_amber_threshold`, `topups.max_proof_kb`.
- Public copy rewrite (Landing + Login) toward customer-acquisition tone.
- 'Made with Emergent' badge removed.
- Iteration 12: 25/25 + 43/43 tests pass.

### v1.12 — Corporate UI redesign + Grouped sidebars — **this iteration**
- **Visual system overhaul** — moved from "robotic / terminal-style" aesthetic (mono fonts everywhere, sharp 0-radius corners, neon green accent, all-caps tracked labels) to **corporate B2B SaaS** look:
  - Typography: **Plus Jakarta Sans** primary, **Inter** fallback, **JetBrains Mono** reserved strictly for tabular numbers.
  - Color palette: **zinc-based dark** (#0B0D10 page, #14171C surface) with a single **blue-500 accent** (#3B82F6) for CTA, active states and key data.
  - Geometry: rounded-md (6px) buttons/inputs, rounded-lg (10px) cards, rounded-full pills.
  - Depth: subtle borders + 1px inset highlight + soft drop shadow on cards (no flat 1px borders on raw black).
  - Status pills: capitalize, restrained palette (emerald / amber / red / blue / zinc), no neon.
- **Sidebar grouping** — all three portals now have named section headers:
  - **Client** (6 groups): Workspace · Send · Audience · Messaging assets · Reports & integration · Account
  - **Reseller** (5 groups, was flat): Workspace · Network · Send · Assets · Pricing & insights
  - **Admin** (3 groups, polished): Platform ops · Insights · Configuration
- **Topbar polish** — search input with ⌘K hint and blue focus ring, wallet chip (client/reseller only) with rounded-md border, notification bell, user pill with rounded-full gradient avatar + chevron.
- **Brand mark** — gradient blue square (blue-500 → blue-600) with subtle blue-tinted shadow.
- **Data integrity** — every `data-testid` preserved (14 client + 12 reseller + 8 admin nav items still resolve to the same routes); bank/topup/preflight/CSV-mappings/approvals flows all intact.
- **Testing (iteration 13)**: full frontend regression — 100% pass on all sidebar groups, all nav items, and 5 critical end-to-end flows. See `/app/test_reports/iteration_13.json`.

## What's still mocked / pending user input
- **Tigo TZ SMS API** — adapter stub; waiting on HTTP spec.
- **Twilio real REST** — needs Account SID + Auth Token.
- **Stripe Checkout** — credit-pack purchase wiring; user to confirm hosted vs embedded.

## Backlog
### P0 (blocked on creds / decisions)
- Real Twilio wiring · Real Tigo TZ wiring · Real Stripe Checkout · WhatsApp send via approved templates

### P1
- Per-Contact-Group send stats · Automated failure-reason email alerts (15-min loop)
- Opt-out / DND list enforcement · Spam-keyword guard enforcement · Daily send-limit enforcement
- Country-Admin scoped views

### P2
- AI fraud detection · Voice + email channels · Quality-aware smart routing · Real-time provider health pings · Institutions API integrations (Phase 2)

### Refactor (Phase 2 of routes split)
Remaining ~20 routers to extract from `server.py` into `/app/backend/routes/`: auth, wallet, contacts, numbers, messaging, sender_ids, templates, wa_templates, admin (further sub-split), country_hub, approvals/applications, credits, dlr, profile, referrals, reseller, notifications, api_keys, prefixes. Then `core.py` for shared helpers/db/auth/scheduler/workers.

## Settings Hub layout (current)
- **Platform & Onboarding** — platform, onboarding
- **Economy** — credits, pricing_cfg, credit_packs →, referrals, streaks
- **Geographies** — country_hub →, integrations →, routing →, providers →, prefixes →, countries →, pricing →
- **Messaging engine** — queue (incl. preflight tunables), sender_ids →
- **Governance & lifecycle** — compliance (incl. topups.max_proof_kb), inactivity, notifications
- **Distribution & treasury** — resellers →, reseller_policy, wallets →, banks →, institutions →, promotions →

## Test credentials
See `/app/memory/test_credentials.md`.
