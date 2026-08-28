<?php

namespace App\Support;

use App\Models\PlatformSetting;

class GameSettings
{
    public const KEY = 'games';

    public const TYPES = ['spin', 'boxes', 'scratch'];

    public const QUALIFY = ['spend', 'visits', 'both'];

    public const FREQUENCIES = ['daily', 'transaction', 'game'];

    public const WIN_MODES = ['automatic', 'odds', 'spread'];

    public const PRIZE_KINDS = ['free_item', 'percent', 'points', 'custom'];

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
            'enabled' => true,
            'types' => self::TYPES,
            'default_qualify' => 'spend',
            'spend_multiplier' => 1.6,
            'recommended_visit_threshold' => 3,
            'recommended_win_rate' => 20,
            'max_win_rate' => 50,
            'default_play_frequency' => 'daily',
            'max_duration_days' => 90,
            'allowed_prize_kinds' => self::PRIZE_KINDS,
            'claim_days' => 7,
            'expected_plays' => 500,
            'play_reveal_ms' => 2800,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalizeInput(array $input): array
    {
        $types = $input['types'] ?? self::TYPES;
        if (is_string($types)) {
            $types = preg_split('/\s*,\s*/', $types) ?: [];
        }
        $types = array_values(array_intersect(self::TYPES, array_map('strval', (array) $types)));

        $kinds = $input['allowed_prize_kinds'] ?? self::PRIZE_KINDS;
        if (is_string($kinds)) {
            $kinds = preg_split('/\s*,\s*/', $kinds) ?: [];
        }
        $kinds = array_values(array_intersect(self::PRIZE_KINDS, array_map('strval', (array) $kinds)));

        $qualify = (string) ($input['default_qualify'] ?? 'spend');
        if (! in_array($qualify, self::QUALIFY, true)) {
            $qualify = 'spend';
        }
        $freq = (string) ($input['default_play_frequency'] ?? 'daily');
        if (! in_array($freq, self::FREQUENCIES, true)) {
            $freq = 'daily';
        }

        return [
            'enabled' => ! empty($input['enabled']),
            'types' => $types !== [] ? $types : ['spin'],
            'default_qualify' => $qualify,
            'spend_multiplier' => max(1.0, min(4.0, (float) ($input['spend_multiplier'] ?? 1.6))),
            'recommended_visit_threshold' => max(2, min(20, (int) ($input['recommended_visit_threshold'] ?? 3))),
            'recommended_win_rate' => max(5, min(50, (int) ($input['recommended_win_rate'] ?? 20))),
            'max_win_rate' => max(10, min(80, (int) ($input['max_win_rate'] ?? 50))),
            'default_play_frequency' => $freq,
            'max_duration_days' => max(1, min(365, (int) ($input['max_duration_days'] ?? 90))),
            'allowed_prize_kinds' => $kinds !== [] ? $kinds : ['free_item', 'points'],
            'claim_days' => max(1, min(30, (int) ($input['claim_days'] ?? 7))),
            'expected_plays' => max(20, min(20000, (int) ($input['expected_plays'] ?? 500))),
            'play_reveal_ms' => max(1200, min(8000, (int) ($input['play_reveal_ms'] ?? 2800))),
        ];
    }

    public static function engineOn(): bool
    {
        return FeatureFlags::enabled('games') && ! empty(self::settings()['enabled']);
    }

    public static function tablesReady(): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable('games');
    }

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return self::settings()['types'];
    }
}
