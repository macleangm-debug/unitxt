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

    public function publicName(): string
    {
        $first = trim((string) ($this->customer?->first_name ?? ''));
        $last = trim((string) ($this->customer?->last_name ?? ''));
        $initial = $last !== '' ? mb_strtoupper(mb_substr($last, 0, 1)).'.' : '';

        return trim(($first !== '' ? $first : '—').' '.$initial);
    }

    public function publicMemberTag(): string
    {
        $code = preg_replace('/\D+/', '', (string) ($this->membership?->member_code ?? $this->membership_id ?? $this->id)) ?: (string) $this->id;
        $tail = str_pad(substr($code, -3), 3, '0', STR_PAD_LEFT);

        return 'Member •••'.$tail;
    }

    public function daysUntilClaim(): ?int
    {
        if (! $this->claim_by) {
            return null;
        }

        $today = now()->startOfDay();
        $claim = $this->claim_by->copy()->startOfDay();
        if ($today->gt($claim)) {
            return -((int) $today->diffInDays($claim));
        }

        return (int) $today->diffInDays($claim);
    }

    public function claimHeadline(): string
    {
        if ($this->status === 'claimed') {
            return __('loop.raffle_winner_status_claimed');
        }

        $days = $this->daysUntilClaim();
        if ($days === null) {
            return '';
        }
        if ($days < 0) {
            return __('loop.claim_expired');
        }
        if ($days === 0) {
            return __('loop.expires_today');
        }
        if ($days === 1) {
            return __('loop.expires_tomorrow');
        }

        return trans_choice('loop.days_left', $days, ['count' => $days]);
    }

    public function publicBoard(): array
    {
        return [
            'id' => $this->id,
            'order' => $this->draw_order,
            'name' => $this->publicName(),
            'tag' => $this->publicMemberTag(),
        ];
    }
}
