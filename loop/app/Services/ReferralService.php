<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessReferral;
use App\Support\Plans;
use App\Support\ReferralProgram;
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

        $program = ReferralProgram::settings();
        $extraDays = (int) $program['referred_extra_trial_days'];

        $newBusiness->update([
            'referred_by_business_id' => $referrer->id,
            'trial_ends_at' => now()->addDays(Plans::trialDays() + $extraDays),
            'referral_credit_months' => ($newBusiness->referral_credit_months ?? 0) + (int) $program['referred_bonus_months'],
            'referral_credit_days' => ($newBusiness->referral_credit_days ?? 0) + $extraDays,
        ]);

        return BusinessReferral::create([
            'referrer_business_id' => $referrer->id,
            'referred_business_id' => $newBusiness->id,
            'code_used' => $referrer->referral_code,
            'status' => BusinessReferral::STATUS_PENDING,
        ]);
    }

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

    public function reward(BusinessReferral $referral): BusinessReferral
    {
        if ($referral->isRewarded()) {
            return $referral;
        }

        $program = ReferralProgram::settings();
        $days = (int) $program['referrer_extra_days_per_referral'];
        $months = (int) $program['referrer_months_per_referral'];
        $referrer = $referral->referrer;

        $updates = [
            'referral_credit_days' => ($referrer->referral_credit_days ?? 0) + $days,
            'referral_credit_months' => ($referrer->referral_credit_months ?? 0) + $months,
            'referral_discount_percent' => max(
                (int) $referrer->referral_discount_percent,
                (int) $program['referrer_discount_percent']
            ),
        ];

        if ($days > 0) {
            $base = $referrer->trial_ends_at && $referrer->trial_ends_at->isFuture()
                ? $referrer->trial_ends_at
                : now();
            $updates['trial_ends_at'] = $base->copy()->addDays($days);
        }

        $referrer->update($updates);

        $referral->update([
            'status' => BusinessReferral::STATUS_REWARDED,
            'rewarded_at' => now(),
            'reward_type' => 'extra_days',
            'reward_value' => $days,
        ]);

        $this->applyMilestones($referrer->fresh());

        return $referral->fresh();
    }

    public function applyMilestones(Business $referrer): void
    {
        $program = ReferralProgram::settings();
        $rewardedCount = $referrer->referralsMade()
            ->where('status', BusinessReferral::STATUS_REWARDED)
            ->count();

        $applied = collect($referrer->referral_milestones_applied ?? [])
            ->map(fn ($v) => (int) $v)
            ->all();

        $toAdd = 0;
        foreach ($program['milestones'] as $milestone) {
            $count = (int) $milestone['count'];
            if ($rewardedCount >= $count && ! in_array($count, $applied, true)) {
                $toAdd += (int) $milestone['bonus_months'];
                $applied[] = $count;
            }
        }

        if ($toAdd <= 0) {
            return;
        }

        $referrer->update([
            'referral_credit_months' => ($referrer->referral_credit_months ?? 0) + $toAdd,
            'referral_milestones_applied' => array_values(array_unique($applied)),
        ]);
    }

    /**
     * @return array{goal: int, joined: int, pending: int, remaining: int, percent: int, program: array<string, mixed>}
     */
    public function progress(Business $business): array
    {
        $program = ReferralProgram::settings();
        $goal = (int) $program['goal_count'];
        $joined = $business->referralsMade()
            ->whereIn('status', [BusinessReferral::STATUS_QUALIFIED, BusinessReferral::STATUS_REWARDED])
            ->count();
        $pending = $business->referralsMade()
            ->where('status', BusinessReferral::STATUS_PENDING)
            ->count();

        return [
            'goal' => $goal,
            'joined' => $joined,
            'pending' => $pending,
            'remaining' => max(0, $goal - $joined),
            'percent' => $goal > 0 ? min(100, (int) round(($joined / $goal) * 100)) : 0,
            'program' => $program,
        ];
    }

    public function shareUrl(Business $business): string
    {
        $code = $this->ensureReferralCode($business);

        return \App\Support\PlatformUrl::route('business.register', ['ref' => $code]);
    }
}
