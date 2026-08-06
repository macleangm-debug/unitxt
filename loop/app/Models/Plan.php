<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'key',
    'name',
    'tagline',
    'price_monthly',
    'currency',
    'max_shops',
    'max_members',
    'max_monthly_visits',
    'is_public',
    'sort_order',
    'features',
])]
class Plan extends Model
{
    protected function casts(): array
    {
        return [
            'price_monthly' => 'integer',
            'max_shops' => 'integer',
            'max_members' => 'integer',
            'max_monthly_visits' => 'integer',
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
