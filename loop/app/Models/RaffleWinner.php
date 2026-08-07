<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'raffle_id',
    'customer_id',
    'membership_id',
    'draw_order',
    'status',
    'drawn_at',
    'claim_by',
    'claimed_at',
])]
class RaffleWinner extends Model
{
    protected function casts(): array
    {
        return [
            'draw_order' => 'integer',
            'drawn_at' => 'datetime',
            'claim_by' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    public function raffle(): BelongsTo
    {
        return $this->belongsTo(Raffle::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function isExpired(): bool
    {
        return $this->status === 'pending'
            && $this->claim_by
            && $this->claim_by->isPast();
    }

    public function displayFirstName(): string
    {
        return $this->customer?->first_name ?? '—';
    }

    public function displayLastBlurred(): string
    {
        $last = $this->customer?->last_name ?? '';
        if ($last === '') {
            return '••••';
        }

        return mb_substr($last, 0, 1).str_repeat('•', max(2, mb_strlen($last) - 1));
    }
}
