<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
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

    /**
     * Points that may unlock an offer right now.
     * Default: today's earn does not count, so a member cannot buy their way
     * into a redeem on the same visit/day. Businesses can opt in.
     */
    public function redeemablePoints(): int
    {
        $balance = (int) $this->points_balance;
        if ($this->business?->allow_same_day_earn_redeem) {
            return $balance;
        }

        $earnedToday = (int) $this->visits()->whereDate('created_at', today())->sum('points_earned');

        return max(0, $balance - $earnedToday);
    }

    public function availableRewards()
    {
        $balance = $this->redeemablePoints();

        return $this->business->rewards()
            ->where('is_active', true)
            ->where('points_cost', '<=', $balance)
            ->orderBy('points_cost')
            ->get()
            ->filter(fn (Reward $reward) => $reward->isAvailable());
    }

    public function nextReward(): ?Reward
    {
        $rewards = $this->relationLoaded('business') && $this->business->relationLoaded('rewards')
            ? $this->business->rewards
            : $this->business->rewards()->where('is_active', true)->orderBy('points_cost')->get();

        $balance = $this->redeemablePoints();

        return $rewards
            ->filter(fn (Reward $reward) => $reward->points_cost > $balance)
            ->sortBy('points_cost')
            ->first();
    }

    public function nearestReadyReward(): ?Reward
    {
        $rewards = $this->relationLoaded('business') && $this->business->relationLoaded('rewards')
            ? $this->business->rewards
            : $this->business->rewards()->where('is_active', true)->orderBy('points_cost')->get();

        $balance = $this->redeemablePoints();

        return $rewards
            ->filter(fn (Reward $reward) => $reward->points_cost <= $balance)
            ->sortBy('points_cost')
            ->first();
    }

    /**
     * One row per visit (net points) so members do not see every earn fragment.
     *
     * @return Collection<int, object{points: int, created_at: \Illuminate\Support\Carbon, visit_id: int|null, type: string}>
     */
    public function groupedActivity(int $limit = 20): Collection
    {
        $rows = $this->pointTransactions()->latest('id')->limit(80)->get();
        $grouped = [];
        $order = [];

        foreach ($rows as $tx) {
            $key = $tx->visit_id ? 'v:'.$tx->visit_id : 't:'.$tx->id;
            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'points' => 0,
                    'created_at' => $tx->created_at,
                    'type' => $tx->type,
                    'visit_id' => $tx->visit_id,
                ];
                $order[] = $key;
            }
            $grouped[$key]['points'] += (int) $tx->points;
            if ($tx->type === PointTransaction::TYPE_REDEEM) {
                $grouped[$key]['type'] = PointTransaction::TYPE_REDEEM;
            }
        }

        return collect($order)
            ->map(fn (string $key) => (object) $grouped[$key])
            ->take($limit)
            ->values();
    }

    public function progressTo(?Reward $reward): array
    {
        if (! $reward) {
            return ['needed' => 0, 'percent' => 100, 'ready' => true];
        }

        $balance = $this->redeemablePoints();
        $needed = max(0, $reward->points_cost - $balance);
        $percent = $reward->points_cost > 0
            ? (int) min(100, round(($balance / $reward->points_cost) * 100))
            : 100;

        return [
            'needed' => $needed,
            'percent' => $percent,
            'ready' => $needed === 0,
        ];
    }
}
