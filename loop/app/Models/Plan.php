<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'key',
    'country',
    'name',
    'tagline',
    'price_monthly',
    'currency',
    'max_shops',
    'max_members',
    'max_monthly_visits',
    'max_product_pushes',
    'max_offers',
    'has_raffles',
    'has_sms',
    'is_public',
    'sort_order',
    'features',
])]
class Plan extends Model
{
    public static function locate(string $key, ?string $country = null): ?self
    {
        $country = $country ?: 'TZ';

        return static::query()->where('key', $key)->where('country', $country)->first()
            ?? static::query()->where('key', $key)->where('country', 'TZ')->first()
            ?? static::query()->where('key', $key)->orderBy('id')->first();
    }

    public static function forCountry(?string $country = null)
    {
        $country = $country ?: 'TZ';
        $query = static::query()->where('country', $country)->orderBy('sort_order');
        if ($query->clone()->exists()) {
            return $query;
        }

        return static::query()->where('country', 'TZ')->orderBy('sort_order');
    }

    public static function countriesInUse(): array
    {
        return static::query()->orderBy('country')->distinct()->pluck('country')->filter()->values()->all();
    }

    protected function casts(): array
    {
        return [
            'price_monthly' => 'integer',
            'max_shops' => 'integer',
            'max_members' => 'integer',
            'max_monthly_visits' => 'integer',
            'max_product_pushes' => 'integer',
            'max_offers' => 'integer',
            'has_raffles' => 'boolean',
            'has_sms' => 'boolean',
            'is_public' => 'boolean',
            'sort_order' => 'integer',
            'features' => 'array',
        ];
    }

    public function priceLabel(): string
    {
        if ($this->price_monthly <= 0) {
            return __('loop.plan_free_price');
        }

        return $this->currency.' '.number_format($this->price_monthly).'/'.__('loop.mo');
    }
}
