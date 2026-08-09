<?php

namespace App\Support;

use App\Models\PlatformSetting;

class CountrySettings
{
    public const KEY = 'countries';

    /**
     * @return array{enabled: list<string>}
     */
    public static function settings(): array
    {
        $defaults = self::defaults();
        $stored = PlatformSetting::getValue(self::KEY, []);
        if (! is_array($stored)) {
            return $defaults;
        }

        $enabled = $stored['enabled'] ?? $defaults['enabled'];
        if (! is_array($enabled)) {
            $enabled = $defaults['enabled'];
        }

        $enabled = array_values(array_filter($enabled, fn ($code) => isset(Countries::OPTIONS[(string) $code])));

        return [
            'enabled' => $enabled !== [] ? $enabled : $defaults['enabled'],
        ];
    }

    /**
     * @return array{enabled: list<string>}
     */
    public static function defaults(): array
    {
        return [
            'enabled' => ['TZ', 'KE', 'UG', 'RW', 'BI', 'CD', 'ZW'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{enabled: list<string>}
     */
    public static function normalizeInput(array $input): array
    {
        $enabled = $input['enabled'] ?? [];
        if (! is_array($enabled)) {
            $enabled = [];
        }

        $out = [];
        foreach ($enabled as $code) {
            $code = strtoupper((string) $code);
            if (isset(Countries::OPTIONS[$code])) {
                $out[] = $code;
            }
        }

        return [
            'enabled' => $out !== [] ? array_values(array_unique($out)) : ['TZ'],
        ];
    }

    public static function isEnabled(string $code): bool
    {
        return in_array(strtoupper($code), self::settings()['enabled'], true);
    }
}
