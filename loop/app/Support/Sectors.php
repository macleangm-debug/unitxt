<?php

namespace App\Support;

class Sectors
{
    public const OPTIONS = [
        'restaurants' => 'Restaurants',
        'coffee' => 'Coffee & cafés',
        'fast_food' => 'Fast food',
        'fashion' => 'Fashion & apparel',
        'beauty' => 'Beauty & salon',
        'health' => 'Health & pharmacy',
        'ppe' => 'PPE & safety',
        'grocery' => 'Grocery & minimart',
        'electronics' => 'Electronics',
        'retail' => 'General retail',
        'automotive' => 'Automotive',
        'fitness' => 'Fitness & wellness',
        'hospitality' => 'Hotels & lodging',
        'education' => 'Education & tutoring',
        'services' => 'Professional services',
        'other' => 'Other',
    ];

    public static function label(?string $key, ?string $custom = null): string
    {
        if ($key === 'other' && filled($custom)) {
            return $custom;
        }

        return self::OPTIONS[$key] ?? 'Other';
    }
}
