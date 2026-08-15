<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'business_id',
    'name',
    'description',
    'product_name',
    'product_sku',
    'points_cost',
    'reward_type',
    'reward_value',
    'stock',
    'max_redemptions_per_member',
    'starts_at',
    'ends_at',
    'is_default',
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
            'max_redemptions_per_member' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class);
    }

    public function isWithinSchedule(): bool
    {
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (! $this->isWithinSchedule()) {
            return false;
        }

        return $this->stock === null || $this->stock > 0;
    }

    public function scheduleLabel(): ?string
    {
        if ($this->starts_at && $this->ends_at) {
            return $this->starts_at->format('d M').' – '.$this->ends_at->format('d M Y');
        }
        if ($this->ends_at) {
            return __('loop.offer_ends_on', ['date' => $this->ends_at->format('d M Y')]);
        }
        if ($this->starts_at) {
            return __('loop.offer_starts_on', ['date' => $this->starts_at->format('d M Y')]);
        }

        return null;
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

    /**
     * True when the proposed edit makes the offer worse for members.
     *
     * @param  array{points_cost?: int, reward_value?: float|int|string|null, is_active?: bool, ends_at?: mixed, stock?: mixed}  $incoming
     */
    public function wouldWorsen(array $incoming): bool
    {
        $newPoints = (int) ($incoming['points_cost'] ?? $this->points_cost);
        if ($newPoints > $this->points_cost) {
            return true;
        }

        $oldValue = (float) $this->reward_value;
        $newValue = array_key_exists('reward_value', $incoming)
            ? (float) $incoming['reward_value']
            : $oldValue;
        if (in_array($this->reward_type, [self::TYPE_PERCENT_OFF, self::TYPE_FIXED_OFF], true) && $newValue < $oldValue) {
            return true;
        }

        if (array_key_exists('is_active', $incoming) && $this->is_active && ! $incoming['is_active']) {
            return true;
        }

        if (array_key_exists('stock', $incoming)) {
            $newStock = $incoming['stock'];
            if ($this->stock === null && $newStock !== null && $newStock !== '') {
                return true;
            }
            if ($this->stock !== null && $newStock !== null && $newStock !== '' && (int) $newStock < $this->stock) {
                return true;
            }
        }

        return false;
    }
}
