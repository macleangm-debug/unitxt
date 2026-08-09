<?php

namespace App\Models;

use App\Support\Sectors;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'owner_id',
    'name',
    'slug',
    'sector',
    'sector_other',
    'country',
    'currency',
    'city',
    'hotline',
    'branch_count',
    'description',
    'logo_path',
    'is_active',
    'onboarding_completed_at',
    'plan_key',
    'referral_code',
    'referred_by_business_id',
    'referred_by_affiliate_id',
    'billing_status',
    'trial_ends_at',
    'referral_discount_percent',
    'referral_credit_months',
    'referral_credit_days',
    'referral_milestones_applied',
])]
class Business extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'onboarding_completed_at' => 'datetime',
            'branch_count' => 'integer',
            'trial_ends_at' => 'datetime',
            'referral_discount_percent' => 'integer',
            'referral_credit_months' => 'integer',
            'referral_credit_days' => 'integer',
            'referral_milestones_applied' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Business $business): void {
            if (blank($business->slug)) {
                $business->slug = Str::slug($business->name).'-'.Str::lower(Str::random(4));
            }
            if (blank($business->plan_key)) {
                $business->plan_key = 'free';
            }
            if (blank($business->billing_status)) {
                $business->billing_status = 'trialing';
            }
            if (blank($business->referral_code)) {
                do {
                    $code = Str::upper(Str::random(8));
                } while (static::query()->where('referral_code', $code)->exists());
                $business->referral_code = $code;
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_key', 'key');
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_business_id');
    }

    public function referredByAffiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class, 'referred_by_affiliate_id');
    }

    public function referralsMade(): HasMany
    {
        return $this->hasMany(BusinessReferral::class, 'referrer_business_id');
    }

    public function referralReceived(): HasMany
    {
        return $this->hasMany(BusinessReferral::class, 'referred_business_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(User::class)->whereIn('role', [User::ROLE_OWNER, User::ROLE_FRONT_DESK]);
    }

    public function frontDeskStaff(): HasMany
    {
        return $this->hasMany(User::class)->where('role', User::ROLE_FRONT_DESK);
    }

    public function shops(): HasMany
    {
        return $this->hasMany(Shop::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    public function raffles(): HasMany
    {
        return $this->hasMany(Raffle::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function uniqueMemberCount(): int
    {
        return (int) $this->memberships()->distinct('customer_id')->count('customer_id');
    }

    public function sectorLabel(): string
    {
        return Sectors::label($this->sector, $this->sector_other);
    }

    public function logoUrl(): ?string
    {
        if (blank($this->logo_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    public function effectiveMonthlyPrice(): int
    {
        $plan = $this->plan ?? Plan::query()->where('key', $this->plan_key)->first();
        $base = (int) ($plan?->price_monthly ?? 0);

        if ($base <= 0) {
            return 0;
        }

        if (($this->referral_credit_months ?? 0) > 0) {
            return 0;
        }

        $discount = min(100, max(0, (int) $this->referral_discount_percent));

        return (int) round($base * (100 - $discount) / 100);
    }
}
