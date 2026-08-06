# Loop

Loyalty without the card — for SMEs, starting in Tanzania.

Customers use their **phone number**. Shops record sales at the **till** (in store or phone order). Points follow flexible campaigns. Rewards are applied when the customer buys.

## Product flows

1. **Entry** — “I’m a business” or “I’m a customer”
2. **Business** — register with sector + first shop (TZ / TZS default) · owner phone + password
3. **Front desk** — owner adds staff (phone + password) · till only
4. **Till** — look up phone → register if new (name, birth date, optional email) → enter amount → award points / apply reward
5. **Customer** — phone + OTP → wallets grouped by sector · see rewards (redeemed at till)
6. **Discover** — browse live campaigns by sector without an account

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

### Demo logins

| Who | Phone | Password / PIN |
|-----|-------|----------------|
| Platform admin | `+255 710000000` | `password` |
| Owner | `+255 712000001` | `password` |
| Front desk | `+255 712000002` | `password` |
| Customer | `+255 713000001` | PIN `1234` |

Harbor Beans referral code (demo): `HARBOR01`

## Pricing (TZS / month)

| Plan | Price | Fit |
|------|-------|-----|
| Trial | 0 | 14 days · 1 shop · 50 members · 50 sales/mo — then pay |
| Starter | 25,000 | 1 shop, unlimited |
| Growth | 60,000 | up to 5 shops |
| Scale | 120,000 | unlimited shops |

Admin configures trial length and free caps in **Settings**. Till locks when trial ends if still unpaid.

Business referrals: share invite link → finish onboarding → free months (both sides). Admin sets milestones.

## Campaign examples

- Every **TZS 1,000 = 2 points** (campaign = earn)
- **100 points → 5% off** or free coffee (offer = redeem at till)
- Birthday / welcome bonuses
- Sector offer templates in onboarding

## Stack

Laravel · Blade · Tailwind · SQLite by default
