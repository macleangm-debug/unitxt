<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'game_id',
    'business_id',
    'customer_id',
    'membership_id',
    'visit_id',
    'token',
    'status',
    'outcome',
    'prize_id',
    'eligible_at',
    'played_at',
    'claimed_at',
    'claim_by',
])]
class GamePlay extends Model
{
    protected function casts(): array
    {
        return [
            'eligible_at' => 'datetime',
            'played_at' => 'datetime',
            'claimed_at' => 'datetime',
            'claim_by' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $play) {
            if (! $play->token) {
                $play->token = (string) Str::ulid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(GamePrize::class, 'prize_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isWin(): bool
    {
        return $this->outcome === 'win';
    }
}
