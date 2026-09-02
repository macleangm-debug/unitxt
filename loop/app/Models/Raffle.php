<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'business_id',
    'created_by',
    'name',
    'description',
    'prize_name',
    'prize_type',
    'prize_value',
    'winners_count',
    'frequency',
    'draw_at',
    'claim_days',
    'status',
    'reminded_at',
    'drawn_at',
    'is_active',
])]
class Raffle extends Model
{
    protected function casts(): array
    {
        return [
            'draw_at' => 'date',
            'prize_value' => 'decimal:2',
            'winners_count' => 'integer',
            'claim_days' => 'integer',
            'reminded_at' => 'datetime',
            'drawn_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function winners(): HasMany
    {
        return $this->hasMany(RaffleWinner::class)->orderBy('draw_order');
    }

    public function isUnlockedFor(Business $business): bool
    {
        return $business->memberships()->distinct('customer_id')->count('customer_id')
            >= \App\Support\GrowthSettings::raffleMinMembers();
    }

    public function needsReminder(): bool
    {
        if ($this->status !== 'scheduled' || $this->reminded_at) {
            return false;
        }

        $days = (int) \App\Support\GrowthSettings::settings()['raffle_remind_days_before'];

        return $this->draw_at->lte(now()->addDays($days));
    }

    public function remainingWinnerSlots(): int
    {
        return max(0, $this->winners_count - $this->winners()->count());
    }

    public function canDrawNow(?\Illuminate\Support\Carbon $at = null): bool
    {
        if (! in_array($this->status, ['scheduled', 'live'], true)) {
            return false;
        }
        if ($this->remainingWinnerSlots() <= 0) {
            return false;
        }

        $at = ($at ?? now())->copy()->startOfDay();

        return $this->nextDrawDate($at)->lte($at);
    }

    public function nextDrawDate(?\Illuminate\Support\Carbon $from = null): \Illuminate\Support\Carbon
    {
        $from = ($from ?? now())->copy()->startOfDay();
        $start = $this->draw_at->copy()->startOfDay();
        if ($this->frequency === 'once' || $start->gte($from)) {
            return $start;
        }

        $cursor = $start->copy();
        while ($cursor->lt($from)) {
            if ($this->frequency === 'monthly') {
                $cursor->addMonth();
            } elseif ($this->frequency === 'yearly') {
                $cursor->addYear();
            } else {
                $cursor->addWeek();
            }
        }

        return $cursor;
    }
}
