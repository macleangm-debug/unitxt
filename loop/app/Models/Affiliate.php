<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'first_name',
    'last_name',
    'country_code',
    'country',
    'phone',
    'email',
    'id_type',
    'id_number',
    'city',
    'address',
    'payout_phone',
    'bank_name',
    'tracking_code',
    'promo_code',
    'status',
    'decision_note',
    'reviewed_by',
    'reviewed_at',
    'activated_at',
])]
class Affiliate extends Model
{
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(AffiliateReferral::class);
    }

    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getFullPhoneAttribute(): string
    {
        return $this->country_code.' '.$this->phone;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'active'], true);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canActivate(): bool
    {
        return $this->status === 'approved' && ! $this->activated_at;
    }

    public static function generateTrackingCode(): string
    {
        do {
            $code = 'AFF-'.Str::upper(Str::random(8));
        } while (static::query()->where('tracking_code', $code)->exists());

        return $code;
    }

    public static function generatePromoCode(): string
    {
        do {
            $code = 'LP'.Str::upper(Str::random(6));
        } while (
            static::query()->where('promo_code', $code)->exists()
            || Business::query()->where('referral_code', $code)->exists()
        );

        return $code;
    }
}
