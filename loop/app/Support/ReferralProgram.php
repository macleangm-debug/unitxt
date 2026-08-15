<?php

namespace App\Support;

use App\Models\PlatformSetting;

class ReferralProgram
{
    public const KEY = 'referral_program';

    /**
     * Preset incentive models for A/B style experimentation.
     *
     * @return array<string, array{label: string, goal_count: int, referrer_extra_days_per_referral: int, referred_extra_trial_days: int}>
     */
    public static function models(): array
    {
        return [
            'standard' => [
                'label' => 'Standard · 3 / 5',
                'goal_count' => 3,
                'referrer_extra_days_per_referral' => 3,
                'referred_extra_trial_days' => 5,
            ],
            'balanced' => [
                'label' => 'Balanced · 4 / 4',
                'goal_count' => 3,
                'referrer_extra_days_per_referral' => 4,
                'referred_extra_trial_days' => 4,
            ],
            'generous' => [
                'label' => 'Generous · 5 / 7',
                'goal_count' => 3,
                'referrer_extra_days_per_referral' => 5,
                'referred_extra_trial_days' => 7,
            ],
            'referrer_heavy' => [
                'label' => 'Referrer boost · 7 / 3',
                'goal_count' => 4,
                'referrer_extra_days_per_referral' => 7,
                'referred_extra_trial_days' => 3,
            ],
            'custom' => [
                'label' => 'Custom',
                'goal_count' => 3,
                'referrer_extra_days_per_referral' => 3,
                'referred_extra_trial_days' => 5,
            ],
        ];
    }

    /**
     * @return array{
     *   incentive_model: string,
     *   goal_count: int,
     *   referrer_extra_days_per_referral: int,
     *   referred_extra_trial_days: int,
     *   referrer_months_per_referral: int,
     *   referrer_discount_percent: int,
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
            $bonus = (int) ($row['bonus_months'] ?? $row['bonus_days'] ?? 0);
            if ($count > 0 && $bonus > 0) {
                $normalized[] = ['count' => $count, 'bonus_months' => $bonus];
            }
        }
        usort($normalized, fn ($a, $b) => $a['count'] <=> $b['count']);

        $model = (string) ($stored['incentive_model'] ?? $defaults['incentive_model']);
        if (! array_key_exists($model, self::models())) {
            $model = 'standard';
        }

        $referrerDays = array_key_exists('referrer_extra_days_per_referral', $stored)
            ? (int) $stored['referrer_extra_days_per_referral']
            : (int) ($defaults['referrer_extra_days_per_referral']);

        $referredDays = array_key_exists('referred_extra_trial_days', $stored)
            ? (int) $stored['referred_extra_trial_days']
            : (int) $defaults['referred_extra_trial_days'];

        return [
            'incentive_model' => $model,
            'goal_count' => max(1, (int) ($stored['goal_count'] ?? $defaults['goal_count'])),
            'referrer_extra_days_per_referral' => max(0, $referrerDays),
            'referred_extra_trial_days' => max(0, $referredDays),
            'referrer_months_per_referral' => max(0, (int) ($stored['referrer_months_per_referral'] ?? 0)),
            'referrer_discount_percent' => min(100, max(0, (int) ($stored['referrer_discount_percent'] ?? 0))),
            'referred_bonus_months' => max(0, (int) ($stored['referred_bonus_months'] ?? 0)),
            'milestones' => $normalized,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        $standard = self::models()['standard'];

        return [
            'incentive_model' => 'standard',
            'goal_count' => $standard['goal_count'],
            'referrer_extra_days_per_referral' => $standard['referrer_extra_days_per_referral'],
            'referred_extra_trial_days' => $standard['referred_extra_trial_days'],
            'referrer_months_per_referral' => 0,
            'referrer_discount_percent' => 0,
            'referred_bonus_months' => 0,
            'milestones' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalizeInput(array $input): array
    {
        $model = (string) ($input['incentive_model'] ?? 'custom');
        $models = self::models();
        if (! array_key_exists($model, $models)) {
            $model = 'custom';
        }

        if ($model !== 'custom') {
            $preset = $models[$model];

            return [
                'incentive_model' => $model,
                'goal_count' => max(1, (int) $preset['goal_count']),
                'referrer_extra_days_per_referral' => max(0, (int) $preset['referrer_extra_days_per_referral']),
                'referred_extra_trial_days' => max(0, (int) $preset['referred_extra_trial_days']),
                'referrer_months_per_referral' => 0,
                'referrer_discount_percent' => min(100, max(0, (int) ($input['referrer_discount_percent'] ?? 0))),
                'referred_bonus_months' => 0,
                'milestones' => [],
            ];
        }

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
            'incentive_model' => 'custom',
            'goal_count' => max(1, (int) ($input['goal_count'] ?? 3)),
            'referrer_extra_days_per_referral' => max(0, (int) ($input['referrer_extra_days_per_referral'] ?? 3)),
            'referred_extra_trial_days' => max(0, (int) ($input['referred_extra_trial_days'] ?? 5)),
            'referrer_months_per_referral' => max(0, (int) ($input['referrer_months_per_referral'] ?? 0)),
            'referrer_discount_percent' => min(100, max(0, (int) ($input['referrer_discount_percent'] ?? 0))),
            'referred_bonus_months' => max(0, (int) ($input['referred_bonus_months'] ?? 0)),
            'milestones' => $milestones,
        ];
    }
}
