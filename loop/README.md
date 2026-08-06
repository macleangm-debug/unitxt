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

| Who | Phone | Password / OTP |
|-----|-------|----------------|
| Owner | `+255 712000001` | `password` |
| Front desk | `+255 712000002` | `password` |
| Customer | `+255 713000001` | OTP `123456` (local) |

## Campaign examples

- Every **TZS 1,000 = 2 points**
- **100 points → 5% off** (applied at till)
- Birthday / welcome bonuses
- Proven templates in the campaign builder

## Stack

Laravel 13 · Blade · Tailwind · SQLite by default
