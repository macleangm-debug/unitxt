<?php

namespace App\Support;

use App\Models\PlatformSetting;

class BillingSettings
{
    public const KEY = 'billing';

    /**
     * @return array{
     *   trial_days: int,
     *   free_max_shops: int,
     *   free_max_members: int,
     *   free_max_monthly_visits: int,
     *   free_max_product_pushes: int,
     *   free_max_offers: int,
     *   block_till_when_trial_ends: bool
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
            'free_max_shops' => max(1, (int) ($stored['free_max_shops'] ?? $defaults['free_max_shops'])),
            'free_max_members' => max(1, (int) ($stored['free_max_members'] ?? $defaults['free_max_members'])),
            'free_max_monthly_visits' => max(1, (int) ($stored['free_max_monthly_visits'] ?? $defaults['free_max_monthly_visits'])),
            'free_max_product_pushes' => max(0, (int) ($stored['free_max_product_pushes'] ?? $defaults['free_max_product_pushes'])),
            'free_max_offers' => max(1, (int) ($stored['free_max_offers'] ?? $defaults['free_max_offers'])),
            'block_till_when_trial_ends' => (bool) ($stored['block_till_when_trial_ends'] ?? $defaults['block_till_when_trial_ends']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'trial_days' => 14,
            'free_max_shops' => 1,
            'free_max_members' => 50,
            'free_max_monthly_visits' => 50,
            'free_max_product_pushes' => 1,
            'free_max_offers' => 3,
            'block_till_when_trial_ends' => true,
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
            'free_max_shops' => max(1, min(5, (int) ($input['free_max_shops'] ?? 1))),
            'free_max_members' => max(1, min(500, (int) ($input['free_max_members'] ?? 50))),
            'free_max_monthly_visits' => max(1, min(500, (int) ($input['free_max_monthly_visits'] ?? 50))),
            'free_max_product_pushes' => max(0, min(50, (int) ($input['free_max_product_pushes'] ?? 1))),
            'free_max_offers' => max(1, min(200, (int) ($input['free_max_offers'] ?? 3))),
            'block_till_when_trial_ends' => ! empty($input['block_till_when_trial_ends']),
        ];
    }
}
