<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessReferral;
use App\Support\Plans;
use Illuminate\Support\Str;

class ReferralService
{
    public function ensureReferralCode(Business $business): string
    {
        if ($business->referral_code) {
            return $business->referral_code;
        }

        do {
            $code = Str::upper(Str::random(8));
        } while (Business::query()->where('referral_code', $code)->exists());

        $business->update(['referral_code' => $code]);

        return $code;
    }

    public function findReferrer(?string $code): ?Business
    {
        if (blank($code)) {
            return null;
        }

        return Business::query()
            ->where('referral_code', Str::upper(trim($code)))
            ->where('is_active', true)
            ->first();
    }

    public function attachReferral(Business $newBusiness, ?string $code): ?BusinessReferral
    {
        $referrer = $this->findReferrer($code);
        if (! $referrer || $referrer->id === $newBusiness->id) {
            return null;
        }

        $newBusiness->update(['referred_by_business_id' => $referrer->id]);

        return BusinessReferral::create([
            'referrer_business_id' => $referrer->id,
            'referred_business_id' => $newBusiness->id,
            'code_used' => $referrer->referral_code,
            'status' => BusinessReferral::STATUS_PENDING,
        ]);
    }

    /**
     * Qualify when the referred business completes onboarding (first real setup).
     */
    public function qualifyForBusiness(Business $business): ?BusinessReferral
    {
        $referral = BusinessReferral::query()
            ->where('referred_business_id', $business->id)
            ->where('status', BusinessReferral::STATUS_PENDING)
            ->first();

        if (! $referral || ! $business->onboarding_completed_at) {
            return $referral;
        }

        $referral->update([
            'status' => BusinessReferral::STATUS_QUALIFIED,
            'qualified_at' => now(),
        ]);

        return $this->reward($referral->fresh());
    }

    /**
     * Grant reward to referrer (free month by default).
     */
    public function reward(BusinessReferral $referral): BusinessReferral
    {
        if ($referral->isRewarded()) {
            return $referral;
        }

        $months = Plans::referralFreeMonths();
        $referrer = $referral->referrer;

        $referrer->update([
            'referral_credit_months' => ($referrer->referral_credit_months ?? 0) + $months,
            'referral_discount_percent' => max(
                (int) $referrer->referral_discount_percent,
                Plans::referralDiscountPercent()
            ),
        ]);

        $referral->update([
            'status' => BusinessReferral::STATUS_REWARDED,
            'rewarded_at' => now(),
            'reward_type' => 'free_month',
            'reward_value' => $months,
        ]);

        return $referral->fresh();
    }

    public function shareUrl(Business $business): string
    {
        $code = $this->ensureReferralCode($business);

        return route('business.register', ['ref' => $code]);
    }
}
