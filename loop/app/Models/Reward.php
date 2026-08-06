<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'business_id',
    'name',
    'description',
    'points_cost',
    'reward_type',
    'reward_value',
    'stock',
    'is_active',
])]
class Reward extends Model
{
    public const TYPE_PERCENT_OFF = 'percent_off';

    public const TYPE_FIXED_OFF = 'fixed_off';

    public const TYPE_FREE_ITEM = 'free_item';

    public const TYPE_CUSTOM = 'custom';

    protected function casts(): array
    {
        return [
            'points_cost' => 'integer',
            'reward_value' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class);
    }

    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return $this->stock === null || $this->stock > 0;
    }

    public function label(): string
    {
        return match ($this->reward_type) {
            self::TYPE_PERCENT_OFF => rtrim(rtrim(number_format((float) $this->reward_value, 2), '0'), '.').'% off',
            self::TYPE_FIXED_OFF => number_format((float) $this->reward_value, 0).' off',
            self::TYPE_FREE_ITEM => $this->name,
            default => $this->name,
        };
    }

    public function discountForAmount(float $amount): float
    {
        return match ($this->reward_type) {
            self::TYPE_PERCENT_OFF => round($amount * ((float) $this->reward_value / 100), 2),
            self::TYPE_FIXED_OFF => min($amount, (float) $this->reward_value),
            default => 0,
        };
    }
}
