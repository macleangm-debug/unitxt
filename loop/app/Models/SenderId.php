<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'platform_sender_id_id',
    'code',
    'kind',
    'status',
    'paid_until',
    'yearly_fee',
])]
class SenderId extends Model
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_EXPIRED = 'expired';

    protected function casts(): array
    {
        return [
            'paid_until' => 'datetime',
            'yearly_fee' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function platformSenderId(): BelongsTo
    {
        return $this->belongsTo(PlatformSenderId::class);
    }

    public function isUsable(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->paid_until && $this->paid_until->isPast()) {
            return false;
        }

        return true;
    }

    public function needsRenewal(): bool
    {
        if (! $this->paid_until) {
            return true;
        }

        return $this->paid_until->lte(now()->addDays(14));
    }
}
