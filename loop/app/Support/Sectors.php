<?php

namespace App\Support;

class Sectors
{
    public const OPTIONS = [
        'restaurants' => 'Restaurants',
        'coffee' => 'Coffee shops',
        'fast_food' => 'Fast food',
        'fashion' => 'Fashion',
        'retail' => 'Retail',
        'beauty' => 'Beauty & salon',
        'grocery' => 'Grocery',
        'other' => 'Other',
    ];

    public static function label(?string $key): string
    {
        return self::OPTIONS[$key] ?? 'Other';
    }
}
