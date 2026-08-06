# Loop

Loyalty platform for small and medium enterprises. Businesses launch visit campaigns across their shops; customers earn points on every check-in and redeem rewards that bring them back.

## What it does

- **Businesses** set up a brand, add shop locations, and create point campaigns
- **Campaigns** define points per visit, bonuses, daily limits, and participating shops
- **Customers** check in with a shop code, join membership wallets, and redeem rewards
- **Points ledger** tracks earns and redemptions per business membership

## Stack

- Laravel 13
- Blade + Tailwind CSS (Breeze auth)
- SQLite by default (swap to MySQL/Postgres via `.env`)

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

Demo accounts after seeding:

| Role | Email | Password |
|------|-------|----------|
| Business | business@loop.test | password |
| Customer | customer@loop.test | password |

Demo shop codes: `SHOP-HBDOWN`, `SHOP-HBWAVE`

## Core flows

1. Register as a **business** → create business profile → add shops → launch a campaign
2. Register as a **customer** → enter a shop code on Check in → earn campaign points
3. Redeem points for business rewards from the membership wallet

## Tests

```bash
php artisan test
```
