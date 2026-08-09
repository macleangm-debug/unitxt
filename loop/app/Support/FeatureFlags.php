<?php

namespace App\Support;

use App\Models\PlatformSetting;

class FeatureFlags
{
    public const KEY = 'feature_flags';

    /**
     * Product capabilities / updates admin can unbox one at a time.
     *
     * @return list<array{key: string, default: bool, category: string}>
     */
    public static function catalog(): array
    {
        return [
            ['key' => 'pay_with_points', 'default' => true, 'category' => 'till'],
            ['key' => 'premium_clients', 'default' => true, 'category' => 'insights'],
            ['key' => 'owner_daily_digest', 'default' => true, 'category' => 'comms'],
            ['key' => 'customer_unlock_hints', 'default' => true, 'category' => 'comms'],
            ['key' => 'birthday_campaigns', 'default' => true, 'category' => 'campaigns'],
            ['key' => 'welcome_campaigns', 'default' => true, 'category' => 'campaigns'],
            ['key' => 'streak_campaigns', 'default' => true, 'category' => 'campaigns'],
            ['key' => 'featured_product', 'default' => true, 'category' => 'campaigns'],
            ['key' => 'raffles', 'default' => true, 'category' => 'growth'],
            ['key' => 'content_studio', 'default' => true, 'category' => 'growth'],
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        $out = [];
        foreach (self::catalog() as $item) {
            $out[$item['key']] = $item['default'];
        }

        return $out;
    }

    /**
     * @return array<string, bool>
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
     * @param  array<string, mixed>  $input
     * @return array<string, bool>
     */
    public static function normalizeInput(array $input): array
    {
        $out = [];
        foreach (self::defaults() as $key => $default) {
            $out[$key] = ! empty($input[$key]);
        }

        return $out;
    }

    public static function enabled(string $key): bool
    {
        $settings = self::settings();

        return (bool) ($settings[$key] ?? false);
    }
}
