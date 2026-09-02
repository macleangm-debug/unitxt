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
    'district',
    'address',
    'payout_method',
    'payout_phone',
    'bank_name',
    'payout_account_name',
    'tracking_code',
    'promo_code',
    'status',
    'decision_note',
    'reviewed_by',
    'reviewed_at',
    'activated_at',
    'setup_completed_at',
])]
class Affiliate extends Model
{
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'activated_at' => 'datetime',
            'setup_completed_at' => 'datetime',
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

    public function needsSetup(): bool
    {
        return $this->isActive() && ! $this->setup_completed_at;
    }

    public function hasPayoutAccount(): bool
    {
        if (! filled($this->payout_method) || ! filled($this->payout_account_name)) {
            return false;
        }

        if ($this->payout_method === 'phone') {
            return filled($this->payout_phone);
        }

        return filled($this->bank_name);
    }

    public function availableCommission(): int
    {
        return (int) $this->referrals()->where('status', 'commissioned')->sum('commission_amount');
    }

    public static function normalizePromoCode(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
    }

    public static function promoCodeAvailable(string $code, ?int $ignoreAffiliateId = null): bool
    {
        $code = self::normalizePromoCode($code);
        if (strlen($code) < 4 || strlen($code) > 12) {
            return false;
        }

        $takenByAffiliate = static::query()
            ->where('promo_code', $code)
            ->when($ignoreAffiliateId, fn ($q) => $q->where('id', '!=', $ignoreAffiliateId))
            ->exists();

        if ($takenByAffiliate) {
            return false;
        }

        return ! Business::query()->where('referral_code', $code)->exists();
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
