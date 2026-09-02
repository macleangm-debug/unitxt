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
            'raffle_min_members' => 10,
            'raffle_max_winners_percent' => 30,
            'raffle_remind_days_before' => 2,
            'raffle_default_claim_days' => 7,
            'raffle_spin_seconds' => 50,
            'banner_member_milestones' => [10, 25, 50, 100],
            'banner_max_count' => 2,
            'campaign_delta_threshold_pct' => 15,
            'retention_delta_threshold_pct' => 8,
            'banner_show_campaign_up' => true,
            'banner_show_campaign_down' => true,
            'banner_show_retention_up' => true,
            'banner_show_retention_down' => true,
            'banner_show_raffle_unlock' => true,
            'banner_show_add_offers_cta' => true,
            'banner_show_member_milestones' => true,
            'onboarding_celebrate' => true,
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
            'raffle_min_members' => max(10, min(5000, (int) ($input['raffle_min_members'] ?? 10))),
            'raffle_max_winners_percent' => max(5, min(50, (int) ($input['raffle_max_winners_percent'] ?? 30))),
            'raffle_remind_days_before' => max(1, min(14, (int) ($input['raffle_remind_days_before'] ?? 2))),
            'raffle_default_claim_days' => max(1, min(30, (int) ($input['raffle_default_claim_days'] ?? 7))),
            'raffle_spin_seconds' => max(45, min(60, (int) ($input['raffle_spin_seconds'] ?? 50))),
            'banner_member_milestones' => $milestones ?: [10, 25, 50, 100],
            'banner_max_count' => max(1, min(5, (int) ($input['banner_max_count'] ?? 2))),
            'campaign_delta_threshold_pct' => max(5, min(100, (int) ($input['campaign_delta_threshold_pct'] ?? 15))),
            'retention_delta_threshold_pct' => max(3, min(50, (int) ($input['retention_delta_threshold_pct'] ?? 8))),
            'banner_show_campaign_up' => ! empty($input['banner_show_campaign_up']),
            'banner_show_campaign_down' => ! empty($input['banner_show_campaign_down']),
            'banner_show_retention_up' => ! empty($input['banner_show_retention_up']),
            'banner_show_retention_down' => ! empty($input['banner_show_retention_down']),
            'banner_show_raffle_unlock' => ! empty($input['banner_show_raffle_unlock']),
            'banner_show_add_offers_cta' => ! empty($input['banner_show_add_offers_cta']),
            'banner_show_member_milestones' => ! empty($input['banner_show_member_milestones']),
            'onboarding_celebrate' => ! empty($input['onboarding_celebrate']),
        ];
    }

    public static function raffleMinMembers(): int
    {
        return (int) self::settings()['raffle_min_members'];
    }

    public static function raffleMaxWinnersPercent(): int
    {
        return (int) self::settings()['raffle_max_winners_percent'];
    }

    public static function raffleSpinSeconds(): int
    {
        return max(45, min(60, (int) self::settings()['raffle_spin_seconds']));
    }

    public static function raffleSpinMs(): int
    {
        return self::raffleSpinSeconds() * 1000;
    }

    /**
     * Max winners for a raffle = floor(members × percent / 100), at least 1.
     */
    public static function maxWinnersForMembers(int $memberCount): int
    {
        $percent = self::raffleMaxWinnersPercent();
        $max = (int) floor(($memberCount * $percent) / 100);

        return max(1, $max);
    }
}
