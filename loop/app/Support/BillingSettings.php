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
     *   block_till_when_trial_ends: bool,
     *   hide_from_discover_when_unpaid: bool
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
            'block_till_when_trial_ends' => (bool) ($stored['block_till_when_trial_ends'] ?? $defaults['block_till_when_trial_ends']),
            'hide_from_discover_when_unpaid' => (bool) ($stored['hide_from_discover_when_unpaid'] ?? $defaults['hide_from_discover_when_unpaid']),
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
            'block_till_when_trial_ends' => true,
            'hide_from_discover_when_unpaid' => true,
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
            'block_till_when_trial_ends' => ! empty($input['block_till_when_trial_ends']),
            'hide_from_discover_when_unpaid' => ! empty($input['hide_from_discover_when_unpaid']),
        ];
    }
}
