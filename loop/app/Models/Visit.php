<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'business_id',
    'shop_id',
    'customer_id',
    'membership_id',
    'campaign_id',
    'recorded_by',
    'amount_spent',
    'points_earned',
    'reward_id',
    'points_redeemed',
    'discount_amount',
    'receipt_ref',
    'channel',
    'notes',
])]
class Visit extends Model
{
    protected function casts(): array
    {
        return [
            'amount_spent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'points_earned' => 'integer',
            'points_redeemed' => 'integer',
        ];
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

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    public function pointTransaction(): HasOne
    {
        return $this->hasOne(PointTransaction::class);
    }
}
