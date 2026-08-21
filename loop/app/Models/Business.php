<?php

namespace App\Models;

use App\Support\Plans;
use App\Support\Sectors;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    'presence',
    'description',
    'logo_path',
    'is_active',
    'allow_pay_with_points',
    'pay_spend_step',
    'pay_points_per_step',
    'pay_points_max_percent',
    'allow_same_day_earn_redeem',
    'onboarding_completed_at',
    'plan_key',
    'referral_code',
    'referred_by_business_id',
    'referred_by_affiliate_id',
    'billing_status',
    'trial_ends_at',
    'plan_renews_at',
    'plan_interval_months',
    'paused_at',
    'grace_started_at',
    'price_locked_until',
    'price_locked_monthly',
    'price_locked_plan_key',
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
            'allow_pay_with_points' => 'boolean',
            'allow_same_day_earn_redeem' => 'boolean',
            'pay_spend_step' => 'integer',
            'pay_points_per_step' => 'integer',
            'pay_points_max_percent' => 'integer',
            'onboarding_completed_at' => 'datetime',
            'branch_count' => 'integer',
            'trial_ends_at' => 'datetime',
            'plan_renews_at' => 'datetime',
            'plan_interval_months' => 'integer',
            'paused_at' => 'datetime',
            'grace_started_at' => 'datetime',
            'price_locked_until' => 'datetime',
            'price_locked_monthly' => 'integer',
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

    public function senderIds(): HasMany
    {
        return $this->hasMany(SenderId::class);
    }

    public function memberGroups(): HasMany
    {
        return $this->hasMany(MemberGroup::class);
    }

    public function messageBroadcasts(): HasMany
    {
        return $this->hasMany(MessageBroadcast::class);
    }

    public function syncCurrencyFromCountry(): void
    {
        if (filled($this->country)) {
            $this->currency = \App\Support\Countries::currency($this->country);
        }
    }

    public function loopBackRequests(): HasMany
    {
        return $this->hasMany(LoopBackRequest::class);
    }

    public function subscriptionBanner(): ?array
    {
        return app(\App\Services\LoopAccess::class)->banner($this);
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

        // Always relative so Cloudflare / tunnel hosts still load the file.
        return '/storage/'.ltrim($this->logo_path, '/');
    }

    public function isOnline(): bool
    {
        return ($this->presence ?? 'physical') === 'online';
    }

    public function hasPhysicalLocation(): bool
    {
        return ($this->presence ?? 'physical') !== 'online';
    }

    public function payWithPointsEnabled(): bool
    {
        if (! \App\Support\FeatureFlags::enabled('pay_with_points')) {
            return false;
        }

        return (bool) $this->allow_pay_with_points
            && (int) $this->pay_spend_step > 0
            && (int) $this->pay_points_per_step > 0;
    }

    /** Currency value of one point when paying (worse rate than earn is typical). */
    public function payCurrencyPerPoint(): float
    {
        if (! $this->payWithPointsEnabled()) {
            return 0.0;
        }

        return (float) $this->pay_spend_step / (float) $this->pay_points_per_step;
    }

    public function payPointsMaxPercent(): int
    {
        return max(1, min(100, (int) ($this->pay_points_max_percent ?: 50)));
    }

    public function effectiveMonthlyPrice(): int
    {
        $plan = Plan::locate($this->plan_key ?: \App\Support\Plans::FREE, $this->country);
        $base = (int) ($plan?->price_monthly ?? 0);

        if ($this->price_locked_until?->isFuture()
            && $this->price_locked_plan_key === ($this->plan_key ?: \App\Support\Plans::FREE)
            && (int) $this->price_locked_monthly > 0) {
            $base = (int) $this->price_locked_monthly;
        }

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
