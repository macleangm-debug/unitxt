<?php

namespace App\Support;

use App\Models\PlatformSetting;

class BillingSettings
{
    public const KEY = 'billing';

    /**
     * @return array{
     *   trial_days: int,
     *   grace_days: int,
     *   free_max_shops: int,
     *   free_max_members: int,
     *   free_max_monthly_visits: int,
     *   free_max_product_pushes: int,
     *   free_max_offers: int,
     *   block_till_when_trial_ends: bool,
     *   discount_months_3: int,
     *   discount_months_6: int,
     *   discount_months_12: int
     * }
     */
    public static function settings(): array
    {
        $defaults = self::defaults();
        $stored = PlatformSetting::getValue(self::KEY, []);

        if (! is_array($stored)) {
            return $defaults;
        }

        return [
            'trial_days' => max(1, (int) ($stored['trial_days'] ?? $defaults['trial_days'])),
            'grace_days' => max(0, (int) ($stored['grace_days'] ?? $defaults['grace_days'])),
            'free_max_shops' => max(1, (int) ($stored['free_max_shops'] ?? $defaults['free_max_shops'])),
            'free_max_members' => max(1, (int) ($stored['free_max_members'] ?? $defaults['free_max_members'])),
            'free_max_monthly_visits' => max(1, (int) ($stored['free_max_monthly_visits'] ?? $defaults['free_max_monthly_visits'])),
            'free_max_product_pushes' => max(0, (int) ($stored['free_max_product_pushes'] ?? $defaults['free_max_product_pushes'])),
            'free_max_offers' => max(1, (int) ($stored['free_max_offers'] ?? $defaults['free_max_offers'])),
            'block_till_when_trial_ends' => (bool) ($stored['block_till_when_trial_ends'] ?? $defaults['block_till_when_trial_ends']),
            'discount_months_3' => max(0, min(80, (int) ($stored['discount_months_3'] ?? $defaults['discount_months_3']))),
            'discount_months_6' => max(0, min(80, (int) ($stored['discount_months_6'] ?? $defaults['discount_months_6']))),
            'discount_months_12' => max(0, min(80, (int) ($stored['discount_months_12'] ?? $defaults['discount_months_12']))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'trial_days' => 14,
            'grace_days' => 7,
            'free_max_shops' => 1,
            'free_max_members' => 50,
            'free_max_monthly_visits' => 50,
            'free_max_product_pushes' => 1,
            'free_max_offers' => 3,
            'block_till_when_trial_ends' => true,
            'discount_months_3' => 8,
            'discount_months_6' => 15,
            'discount_months_12' => 25,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalizeInput(array $input): array
    {
        return [
            'trial_days' => max(1, min(90, (int) ($input['trial_days'] ?? 14))),
            'grace_days' => max(0, min(30, (int) ($input['grace_days'] ?? 7))),
            'free_max_shops' => max(1, min(5, (int) ($input['free_max_shops'] ?? 1))),
            'free_max_members' => max(1, min(500, (int) ($input['free_max_members'] ?? 50))),
            'free_max_monthly_visits' => max(1, min(500, (int) ($input['free_max_monthly_visits'] ?? 50))),
            'free_max_product_pushes' => max(0, min(50, (int) ($input['free_max_product_pushes'] ?? 1))),
            'free_max_offers' => max(1, min(200, (int) ($input['free_max_offers'] ?? 3))),
            'block_till_when_trial_ends' => ! empty($input['block_till_when_trial_ends']),
            'discount_months_3' => max(0, min(80, (int) ($input['discount_months_3'] ?? 8))),
            'discount_months_6' => max(0, min(80, (int) ($input['discount_months_6'] ?? 15))),
            'discount_months_12' => max(0, min(80, (int) ($input['discount_months_12'] ?? 25))),
        ];
    }

    /**
     * @return array<int, int> months => discount percent
     */
    public static function intervalDiscounts(): array
    {
        $settings = self::settings();

        return [
            1 => 0,
            3 => (int) $settings['discount_months_3'],
            6 => (int) $settings['discount_months_6'],
            12 => (int) $settings['discount_months_12'],
        ];
    }

    public static function discountForMonths(int $months): int
    {
        return self::intervalDiscounts()[$months] ?? 0;
    }

    public static function amountForMonths(int $monthly, int $months): int
    {
        $months = in_array($months, [1, 3, 6, 12], true) ? $months : 1;
        $discount = self::discountForMonths($months);

        return (int) round($monthly * $months * (100 - $discount) / 100);
    }

    public static function graceDays(): int
    {
        return (int) self::settings()['grace_days'];
    }

    /**
     * @return array{months: int, discount: int, full: int, amount: int, save: int}
     */
    public static function quote(int $monthly, int $months): array
    {
        $months = in_array($months, [1, 3, 6, 12], true) ? $months : 1;
        $full = $monthly * $months;
        $amount = self::amountForMonths($monthly, $months);

        return [
            'months' => $months,
            'discount' => self::discountForMonths($months),
            'full' => $full,
            'amount' => $amount,
            'save' => max(0, $full - $amount),
        ];
    }
}
