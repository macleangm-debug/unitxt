<?php

namespace App\Support;

use App\Models\PlatformSetting;

class GrowthSettings
{
    public const KEY = 'growth';

    /**
     * @return array<string, mixed>
     */
    public static function settings(): array
    {
        $defaults = self::defaults();
        $stored = PlatformSetting::getValue(self::KEY, []);
        if (! is_array($stored)) {
            return $defaults;
        }

        return array_merge($defaults, array_intersect_key($stored, $defaults));
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'raffle_min_members' => 50,
            'banner_member_milestones' => [25, 50, 100, 250],
            'banner_show_campaign_up' => true,
            'banner_show_campaign_down' => true,
            'banner_show_retention_up' => true,
            'banner_show_retention_down' => true,
            'banner_show_raffle_unlock' => true,
            'banner_show_add_offers_cta' => true,
            'raffle_remind_days_before' => 2,
            'raffle_default_claim_days' => 7,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalizeInput(array $input): array
    {
        $milestones = $input['banner_member_milestones'] ?? self::defaults()['banner_member_milestones'];
        if (is_string($milestones)) {
            $milestones = collect(explode(',', $milestones))
                ->map(fn ($v) => (int) trim($v))
                ->filter(fn ($v) => $v > 0)
                ->values()
                ->all();
        }

        return [
            'raffle_min_members' => max(5, min(5000, (int) ($input['raffle_min_members'] ?? 50))),
            'banner_member_milestones' => $milestones ?: [50],
            'banner_show_campaign_up' => ! empty($input['banner_show_campaign_up']),
            'banner_show_campaign_down' => ! empty($input['banner_show_campaign_down']),
            'banner_show_retention_up' => ! empty($input['banner_show_retention_up']),
            'banner_show_retention_down' => ! empty($input['banner_show_retention_down']),
            'banner_show_raffle_unlock' => ! empty($input['banner_show_raffle_unlock']),
            'banner_show_add_offers_cta' => ! empty($input['banner_show_add_offers_cta']),
            'raffle_remind_days_before' => max(1, min(14, (int) ($input['raffle_remind_days_before'] ?? 2))),
            'raffle_default_claim_days' => max(1, min(30, (int) ($input['raffle_default_claim_days'] ?? 7))),
        ];
    }

    public static function raffleMinMembers(): int
    {
        return (int) self::settings()['raffle_min_members'];
    }
}
