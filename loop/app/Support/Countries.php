<?php

namespace App\Support;

class Countries
{
    public const OPTIONS = [
        'TZ' => [
            'name' => 'Tanzania',
            'dial' => '+255',
            'currency' => 'TZS',
            'flag' => '🇹🇿',
            'cities' => ['Dar es Salaam', 'Arusha', 'Mwanza', 'Dodoma', 'Zanzibar'],
        ],
        'KE' => [
            'name' => 'Kenya',
            'dial' => '+254',
            'currency' => 'KES',
            'flag' => '🇰🇪',
            'cities' => ['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru'],
        ],
        'UG' => [
            'name' => 'Uganda',
            'dial' => '+256',
            'currency' => 'UGX',
            'flag' => '🇺🇬',
            'cities' => ['Kampala', 'Entebbe', 'Jinja', 'Gulu'],
        ],
        'RW' => [
            'name' => 'Rwanda',
            'dial' => '+250',
            'currency' => 'RWF',
            'flag' => '🇷🇼',
            'cities' => ['Kigali', 'Butare', 'Gisenyi'],
        ],
        'BI' => [
            'name' => 'Burundi',
            'dial' => '+257',
            'currency' => 'BIF',
            'flag' => '🇧🇮',
            'cities' => ['Bujumbura', 'Gitega'],
        ],
        'CD' => [
            'name' => 'DR Congo',
            'dial' => '+243',
            'currency' => 'CDF',
            'flag' => '🇨🇩',
            'cities' => ['Kinshasa', 'Lubumbashi', 'Goma', 'Bukavu'],
        ],
        'ZW' => [
            'name' => 'Zimbabwe',
            'dial' => '+263',
            'currency' => 'USD',
            'flag' => '🇿🇼',
            'cities' => ['Harare', 'Bulawayo', 'Mutare'],
        ],
    ];

    public static function dial(string $country = 'TZ'): string
    {
        return self::OPTIONS[$country]['dial'] ?? '+255';
    }

    public static function currency(string $country = 'TZ'): string
    {
        return self::OPTIONS[$country]['currency'] ?? 'TZS';
    }

    public static function cities(string $country = 'TZ'): array
    {
        return self::OPTIONS[$country]['cities'] ?? [];
    }

    public static function fromDial(string $dial): ?string
    {
        foreach (self::OPTIONS as $code => $meta) {
            if ($meta['dial'] === $dial) {
                return $code;
            }
        }

        return null;
    }

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }
}
