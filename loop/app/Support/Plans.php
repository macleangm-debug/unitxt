<?php

namespace App\Support;

class Plans
{
    public const FREE = 'free';

    public const STARTER = 'starter';

    public const GROWTH = 'growth';

    public const SCALE = 'scale';

    /**
     * Paid plans + a tight trial/free lane. Caps for free come from BillingSettings in runtime.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function catalog(): array
    {
        $billing = BillingSettings::defaults();

        return [
            self::FREE => [
                'name' => 'Trial',
                'tagline' => 'Short trial — then pick a paid plan. People value what they pay for.',
                'price_monthly' => 0,
                'currency' => 'TZS',
                'max_shops' => $billing['free_max_shops'],
                'max_members' => $billing['free_max_members'],
                'max_monthly_visits' => $billing['free_max_monthly_visits'],
                'sort_order' => 1,
                'features' => [
                    '1 physical shop + address',
                    'Tight member & sales caps',
                    'Full till during trial',
                    'Upgrade to keep looping',
                ],
            ],
            self::STARTER => [
                'name' => 'Starter',
                'tagline' => 'For a busy single location.',
                'price_monthly' => 25000,
                'currency' => 'TZS',
                'max_shops' => 1,
                'max_members' => null,
                'max_monthly_visits' => null,
                'sort_order' => 2,
                'features' => [
                    '1 physical shop',
                    'Unlimited members & sales',
                    'Priority support',
                    'Remove Loop branding on receipts',
                ],
            ],
            self::GROWTH => [
                'name' => 'Growth',
                'tagline' => 'Multi-branch brands that want to scale.',
                'price_monthly' => 60000,
                'currency' => 'TZS',
                'max_shops' => 5,
                'max_members' => null,
                'max_monthly_visits' => null,
                'sort_order' => 3,
                'features' => [
                    'Up to 5 shop locations',
                    'Unlimited members & sales',
                    'Referral rewards unlocked',
                    'Campaign templates + analytics',
                ],
            ],
            self::SCALE => [
                'name' => 'Scale',
                'tagline' => 'City-wide chains and franchise groups.',
                'price_monthly' => 120000,
                'currency' => 'TZS',
                'max_shops' => null,
                'max_members' => null,
                'max_monthly_visits' => null,
                'sort_order' => 4,
                'features' => [
                    'Unlimited shop locations',
                    'Dedicated success check-ins',
                    'Custom sector offer packs',
                    'Highest referral rewards',
                ],
            ],
        ];
    }

    public static function trialDays(): int
    {
        return BillingSettings::settings()['trial_days'];
    }

    public static function isPaidPlan(?string $planKey): bool
    {
        return in_array($planKey, [self::STARTER, self::GROWTH, self::SCALE], true);
    }

    /**
     * Public plans for pricing surfaces. Falls back to the catalog when the table is empty/missing.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Plan>
     */
    public static function publicPlans()
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('plans')) {
            $plans = \App\Models\Plan::query()
                ->where('is_public', true)
                ->orderBy('sort_order')
                ->get();

            if ($plans->isNotEmpty()) {
                return $plans;
            }
        }

        return collect(self::catalog())
            ->map(function (array $plan, string $key) {
                $model = new \App\Models\Plan([
                    'key' => $key,
                    'name' => $plan['name'],
                    'tagline' => $plan['tagline'],
                    'price_monthly' => $plan['price_monthly'],
                    'currency' => $plan['currency'],
                    'max_shops' => $plan['max_shops'],
                    'max_members' => $plan['max_members'],
                    'max_monthly_visits' => $plan['max_monthly_visits'] ?? null,
                    'is_public' => true,
                    'sort_order' => $plan['sort_order'],
                    'features' => $plan['features'],
                ]);
                $model->exists = false;

                return $model;
            })
            ->values();
    }
}
