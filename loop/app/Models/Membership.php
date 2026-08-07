<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'business_id',
    'shop_id',
    'customer_id',
    'points_balance',
    'lifetime_points',
    'member_code',
    'joined_at',
])]
class Membership extends Model
{
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'points_balance' => 'integer',
            'lifetime_points' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Membership $membership): void {
            if (blank($membership->member_code)) {
                $membership->member_code = 'LP-'.Str::upper(Str::random(8));
            }

            if (blank($membership->joined_at)) {
                $membership->joined_at = now();
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class);
    }

    public function availableRewards()
    {
        return $this->business->rewards()
            ->where('is_active', true)
            ->where('points_cost', '<=', $this->points_balance)
            ->orderBy('points_cost')
            ->get()
            ->filter(fn (Reward $reward) => $reward->isAvailable());
    }

    public function nextReward(): ?Reward
    {
        $rewards = $this->relationLoaded('business') && $this->business->relationLoaded('rewards')
            ? $this->business->rewards
            : $this->business->rewards()->where('is_active', true)->orderBy('points_cost')->get();

        return $rewards
            ->filter(fn (Reward $reward) => $reward->points_cost > $this->points_balance)
            ->sortBy('points_cost')
            ->first();
    }

    public function nearestReadyReward(): ?Reward
    {
        $rewards = $this->relationLoaded('business') && $this->business->relationLoaded('rewards')
            ? $this->business->rewards
            : $this->business->rewards()->where('is_active', true)->orderBy('points_cost')->get();

        return $rewards
            ->filter(fn (Reward $reward) => $reward->points_cost <= $this->points_balance)
            ->sortBy('points_cost')
            ->first();
    }

    public function progressTo(?Reward $reward): array
    {
        if (! $reward) {
            return ['needed' => 0, 'percent' => 100, 'ready' => true];
        }

        $needed = max(0, $reward->points_cost - $this->points_balance);
        $percent = $reward->points_cost > 0
            ? (int) min(100, round(($this->points_balance / $reward->points_cost) * 100))
            : 100;

        return [
            'needed' => $needed,
            'percent' => $percent,
            'ready' => $needed === 0,
        ];
    }
}
