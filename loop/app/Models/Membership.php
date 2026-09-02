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
     * Points that may unlock an offer at Till right now.
     * The member's current balance is what the till can spend.
     * Same-ticket earn cannot be spent until the sale is recorded — lookup
     * always sees the balance from earlier visits, including earlier today.
     */
    public function redeemablePoints(): int
    {
        return (int) $this->points_balance;
    }

    /**
     * Whether this membership can spend this offer at Till right now.
     * Campaign type that earned the points (earn, product push, birthday,
     * welcome, streak) does not matter — the balance is one pool.
     */
    public function canRedeemReward(Reward $reward): bool
    {
        if (! $reward->isAvailable()) {
            return false;
        }
        if ((int) $reward->points_cost > $this->redeemablePoints()) {
            return false;
        }
        if ($reward->max_redemptions_per_member) {
            $used = $this->redemptions()->where('reward_id', $reward->id)->count();
            if ($used >= $reward->max_redemptions_per_member) {
                return false;
            }
        }

        return true;
    }

    public function catalogRewards()
    {
        if ($this->relationLoaded('business') && $this->business->relationLoaded('rewards')) {
            return $this->business->rewards->where('is_active', true)->sortBy('points_cost')->values();
        }

        return $this->business->rewards()->where('is_active', true)->orderBy('points_cost')->get();
    }

    public function availableRewards()
    {
        return $this->catalogRewards()
            ->filter(fn (Reward $reward) => $this->canRedeemReward($reward))
            ->values();
    }

    public function nextReward(): ?Reward
    {
        $balance = $this->redeemablePoints();

        return $this->catalogRewards()
            ->filter(fn (Reward $reward) => $reward->isAvailable() && $reward->points_cost > $balance)
            ->sortBy('points_cost')
            ->first();
    }

    public function nearestReadyReward(): ?Reward
    {
        return $this->availableRewards()->sortBy('points_cost')->first();
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
