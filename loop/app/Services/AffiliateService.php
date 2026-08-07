<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateReferral;
use App\Models\Business;
use App\Models\Plan;
use App\Models\User;
use App\Support\AffiliateProgram;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AffiliateService
{
    public function findByPromo(?string $code): ?Affiliate
    {
        if (blank($code) || ! AffiliateProgram::isEnabled()) {
            return null;
        }

        return Affiliate::query()
            ->where('promo_code', strtoupper(trim($code)))
            ->whereIn('status', ['approved', 'active'])
            ->first();
    }

    public function findByPhone(string $countryCode, string $phone): ?Affiliate
    {
        return Affiliate::query()
            ->where('country_code', $countryCode)
            ->where('phone', $phone)
            ->first();
    }

    public function apply(array $data): Affiliate
    {
        if (! AffiliateProgram::isEnabled()) {
            throw ValidationException::withMessages(['affiliate' => __('loop.affiliate_program_off')]);
        }

        $exists = Affiliate::query()
            ->where('country_code', $data['country_code'])
            ->where('phone', $data['phone'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['phone' => __('loop.affiliate_phone_exists')]);
        }

        if (User::query()->where('country_code', $data['country_code'])->where('phone', $data['phone'])->exists()) {
            throw ValidationException::withMessages(['phone' => __('loop.affiliate_phone_taken')]);
        }

        return Affiliate::create([
            ...$data,
            'status' => 'pending',
        ]);
    }

    public function decide(Affiliate $affiliate, User $admin, string $decision, ?string $note = null): Affiliate
    {
        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['decision' => __('loop.invalid_decision')]);
        }

        if ($affiliate->status !== 'pending') {
            throw ValidationException::withMessages(['status' => __('loop.affiliate_already_reviewed')]);
        }

        $payload = [
            'status' => $decision,
            'decision_note' => $note,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ];

        if ($decision === 'approved') {
            $payload['tracking_code'] = Affiliate::generateTrackingCode();
            $payload['promo_code'] = Affiliate::generatePromoCode();
        }

        $affiliate->update($payload);

        return $affiliate->fresh();
    }

    public function activate(Affiliate $affiliate, string $password, string $pin): User
    {
        if (! $affiliate->canActivate()) {
            throw ValidationException::withMessages(['affiliate' => __('loop.affiliate_cannot_activate')]);
        }

        $pinLength = AffiliateProgram::pinLength();
        if (strlen($pin) !== $pinLength || ! ctype_digit($pin)) {
            throw ValidationException::withMessages([
                'pin' => __('loop.affiliate_pin_length', ['length' => $pinLength]),
            ]);
        }

        return DB::transaction(function () use ($affiliate, $password, $pin) {
            $user = User::create([
                'first_name' => $affiliate->first_name,
                'last_name' => $affiliate->last_name,
                'country_code' => $affiliate->country_code,
                'country' => $affiliate->country,
                'phone' => $affiliate->phone,
                'email' => $affiliate->email,
                'password' => Hash::make($password),
                'pin_hash' => Hash::make($pin),
                'role' => User::ROLE_AFFILIATE,
                'phone_verified_at' => now(),
                'is_active' => true,
                'profile_completed' => true,
            ]);

            $affiliate->update([
                'user_id' => $user->id,
                'status' => 'active',
                'activated_at' => now(),
            ]);

            return $user;
        });
    }

    public function completeSetup(Affiliate $affiliate, string $promoCode): Affiliate
    {
        if (! $affiliate->isActive()) {
            throw ValidationException::withMessages(['affiliate' => __('loop.affiliate_cannot_activate')]);
        }

        $code = Affiliate::normalizePromoCode($promoCode);
        if (! Affiliate::promoCodeAvailable($code, $affiliate->id)) {
            throw ValidationException::withMessages(['promo_code' => __('loop.promo_code_unavailable')]);
        }

        $affiliate->update([
            'promo_code' => $code,
            'setup_completed_at' => now(),
        ]);

        return $affiliate->fresh();
    }

    public function updatePromoCode(Affiliate $affiliate, string $promoCode): Affiliate
    {
        if (! $affiliate->isActive()) {
            throw ValidationException::withMessages(['affiliate' => __('loop.affiliate_cannot_activate')]);
        }

        $code = Affiliate::normalizePromoCode($promoCode);
        if (! Affiliate::promoCodeAvailable($code, $affiliate->id)) {
            throw ValidationException::withMessages(['promo_code' => __('loop.promo_code_unavailable')]);
        }

        $affiliate->update([
            'promo_code' => $code,
            'setup_completed_at' => $affiliate->setup_completed_at ?? now(),
        ]);

        return $affiliate->fresh();
    }

    public function attachToBusiness(Business $business, ?string $code): ?AffiliateReferral
    {
        $affiliate = $this->findByPromo($code);
        if (! $affiliate) {
            return null;
        }

        if ($business->referred_by_affiliate_id || $business->referred_by_business_id) {
            return null;
        }

        $settings = AffiliateProgram::settings();
        $planAmount = (int) (Plan::query()->where('key', $business->plan_key)->value('price_monthly') ?? 0);
        $math = AffiliateProgram::commissionOn($planAmount);

        $business->update([
            'referred_by_affiliate_id' => $affiliate->id,
            'referral_discount_percent' => max(
                (int) $business->referral_discount_percent,
                AffiliateProgram::referredDiscountPercent()
            ),
        ]);

        return AffiliateReferral::create([
            'affiliate_id' => $affiliate->id,
            'business_id' => $business->id,
            'code_used' => $affiliate->promo_code,
            'status' => 'pending',
            'plan_amount' => $math['plan_amount'],
            'discount_amount' => $math['discount_amount'],
            'net_amount' => $math['net_amount'],
            'commission_percent' => $math['commission_percent'],
            'commission_amount' => $math['commission_amount'],
            'attribution_ends_at' => $settings['attribution_enabled']
                ? now()->addMonths((int) $settings['attribution_months'])
                : null,
        ]);
    }

    public function qualifyForBusiness(Business $business): void
    {
        $referral = AffiliateReferral::query()
            ->where('business_id', $business->id)
            ->where('status', 'pending')
            ->first();

        if (! $referral) {
            return;
        }

        $planAmount = (int) (Plan::query()->where('key', $business->plan_key)->value('price_monthly') ?? 0);
        $math = AffiliateProgram::commissionOn(
            $planAmount,
            (int) $business->referral_discount_percent ?: AffiliateProgram::referredDiscountPercent(),
            (int) $referral->commission_percent
        );

        $referral->update([
            'status' => $math['commission_amount'] > 0 ? 'commissioned' : 'qualified',
            'plan_amount' => $math['plan_amount'],
            'discount_amount' => $math['discount_amount'],
            'net_amount' => $math['net_amount'],
            'commission_amount' => $math['commission_amount'],
            'qualified_at' => now(),
        ]);
    }
}
