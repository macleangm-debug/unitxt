<?php

namespace App\Support;

use App\Models\PlatformSetting;

class ReferralProgram
{
    public const KEY = 'referral_program';

    /**
     * @return array{
     *   goal_count: int,
     *   referrer_months_per_referral: int,
     *   referrer_discount_percent: int,
     *   referred_extra_trial_days: int,
     *   referred_bonus_months: int,
     *   milestones: list<array{count: int, bonus_months: int}>
     * }
     */
    public static function settings(): array
    {
        $defaults = self::defaults();
        $stored = PlatformSetting::getValue(self::KEY, []);

        if (! is_array($stored)) {
            return $defaults;
        }

        $milestones = $stored['milestones'] ?? $defaults['milestones'];
        if (! is_array($milestones)) {
            $milestones = $defaults['milestones'];
        }

        $normalized = [];
        foreach ($milestones as $row) {
            if (! is_array($row)) {
                continue;
            }
            $count = (int) ($row['count'] ?? 0);
            $bonus = (int) ($row['bonus_months'] ?? 0);
            if ($count > 0 && $bonus > 0) {
                $normalized[] = ['count' => $count, 'bonus_months' => $bonus];
            }
        }
        usort($normalized, fn ($a, $b) => $a['count'] <=> $b['count']);

        return [
            'goal_count' => max(1, (int) ($stored['goal_count'] ?? $defaults['goal_count'])),
            'referrer_months_per_referral' => max(0, (int) ($stored['referrer_months_per_referral'] ?? $defaults['referrer_months_per_referral'])),
            'referrer_discount_percent' => min(100, max(0, (int) ($stored['referrer_discount_percent'] ?? $defaults['referrer_discount_percent']))),
            'referred_extra_trial_days' => max(0, (int) ($stored['referred_extra_trial_days'] ?? $defaults['referred_extra_trial_days'])),
            'referred_bonus_months' => max(0, (int) ($stored['referred_bonus_months'] ?? $defaults['referred_bonus_months'])),
            'milestones' => $normalized !== [] ? $normalized : $defaults['milestones'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'goal_count' => 3,
            'referrer_months_per_referral' => 1,
            'referrer_discount_percent' => 50,
            'referred_extra_trial_days' => 30,
            'referred_bonus_months' => 1,
            'milestones' => [
                ['count' => 3, 'bonus_months' => 2],
                ['count' => 5, 'bonus_months' => 3],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalizeInput(array $input): array
    {
        $milestones = [];
        $counts = $input['milestone_count'] ?? [];
        $bonuses = $input['milestone_bonus'] ?? [];
        if (is_array($counts)) {
            foreach ($counts as $i => $count) {
                $c = (int) $count;
                $b = (int) ($bonuses[$i] ?? 0);
                if ($c > 0 && $b > 0) {
                    $milestones[] = ['count' => $c, 'bonus_months' => $b];
                }
            }
        }

        return [
            'goal_count' => max(1, (int) ($input['goal_count'] ?? 3)),
            'referrer_months_per_referral' => max(0, (int) ($input['referrer_months_per_referral'] ?? 1)),
            'referrer_discount_percent' => min(100, max(0, (int) ($input['referrer_discount_percent'] ?? 50))),
            'referred_extra_trial_days' => max(0, (int) ($input['referred_extra_trial_days'] ?? 30)),
            'referred_bonus_months' => max(0, (int) ($input['referred_bonus_months'] ?? 1)),
            'milestones' => $milestones !== [] ? $milestones : self::defaults()['milestones'],
        ];
    }
}
