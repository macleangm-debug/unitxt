<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'business_id',
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
}
