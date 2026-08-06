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
        ],
        'KE' => [
            'name' => 'Kenya',
            'dial' => '+254',
            'currency' => 'KES',
            'flag' => '🇰🇪',
        ],
        'UG' => [
            'name' => 'Uganda',
            'dial' => '+256',
            'currency' => 'UGX',
            'flag' => '🇺🇬',
        ],
        'RW' => [
            'name' => 'Rwanda',
            'dial' => '+250',
            'currency' => 'RWF',
            'flag' => '🇷🇼',
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

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        // Strip leading zero common in local TZ numbers (0712... → 712...)
        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }
}
