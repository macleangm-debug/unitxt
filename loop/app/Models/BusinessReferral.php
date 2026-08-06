<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'referrer_business_id',
    'referred_business_id',
    'code_used',
    'status',
    'qualified_at',
    'rewarded_at',
    'reward_type',
    'reward_value',
])]
class BusinessReferral extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_QUALIFIED = 'qualified';

    public const STATUS_REWARDED = 'rewarded';

    protected function casts(): array
    {
        return [
            'qualified_at' => 'datetime',
            'rewarded_at' => 'datetime',
            'reward_value' => 'integer',
        ];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'referrer_business_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'referred_business_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isQualified(): bool
    {
        return $this->status === self::STATUS_QUALIFIED;
    }

    public function isRewarded(): bool
    {
        return $this->status === self::STATUS_REWARDED;
    }
}
