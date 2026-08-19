<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Plan;
use App\Support\BillingSettings;
use App\Support\Plans;

class PlanLimitService
{
    public function planFor(Business $business): ?Plan
    {
        return Plan::locate($business->plan_key ?: Plans::FREE, $business->country);
    }

    /**
     * Effective caps — free/trial plan uses admin BillingSettings overrides.
     *
     * @return array{max_shops: ?int, max_members: ?int, max_monthly_visits: ?int, max_product_pushes: ?int, max_offers: ?int}
     */
    public function effectiveCaps(Business $business): array
    {
        $plan = $this->planFor($business);
        $billing = BillingSettings::settings();

        if (! $plan || $plan->key === Plans::FREE || ! Plans::isPaidPlan($business->plan_key)) {
            return [
                'max_shops' => $billing['free_max_shops'],
                'max_members' => $billing['free_max_members'],
                'max_monthly_visits' => $billing['free_max_monthly_visits'],
                'max_product_pushes' => $billing['free_max_product_pushes'],
                'max_offers' => $billing['free_max_offers'],
            ];
        }

        return [
            'max_shops' => $plan->max_shops,
            'max_members' => $plan->max_members,
            'max_monthly_visits' => $plan->max_monthly_visits,
            'max_product_pushes' => $plan->max_product_pushes,
            'max_offers' => $plan->max_offers,
        ];
    }

    public function trialExpired(Business $business): bool
    {
        if (Plans::isPaidPlan($business->plan_key) && in_array($business->billing_status, ['active', 'free'], true)) {
            return false;
        }

        if (! $business->trial_ends_at) {
            return false;
        }

        return $business->trial_ends_at->isPast();
    }

    public function canUseTill(Business $business): bool
    {
        $billing = BillingSettings::settings();
        if (! $billing['block_till_when_trial_ends']) {
            return true;
        }

        if ($this->trialExpired($business) && ! Plans::isPaidPlan($business->plan_key)) {
            return false;
        }

        if ($this->trialExpired($business) && $business->billing_status === 'past_due') {
            return false;
        }

        return true;
    }

    public function trialExpiredMessage(): string
    {
        return __('loop.trial_expired_till');
    }

    public function canAddShop(Business $business): bool
    {
        $caps = $this->effectiveCaps($business);
        if ($caps['max_shops'] === null) {
            return true;
        }

        return $business->shops()->count() < $caps['max_shops'];
    }

    public function shopLimitMessage(Business $business): string
    {
        $caps = $this->effectiveCaps($business);
        $plan = $this->planFor($business);

        return __('loop.shop_limit_reached', [
            'plan' => $plan?->name ?? 'Trial',
            'max' => $caps['max_shops'] ?? 1,
        ]);
    }

    public function canAddProductPush(Business $business): bool
    {
        $caps = $this->effectiveCaps($business);
        if ($caps['max_product_pushes'] === null) {
            return true;
        }

        return $business->campaigns()->where('type', 'product_push')->count() < $caps['max_product_pushes'];
    }

    public function productPushLimitMessage(Business $business): string
    {
        $caps = $this->effectiveCaps($business);
        $plan = $this->planFor($business);

        return __('loop.product_push_limit_reached', [
            'plan' => $plan?->name ?? 'Trial',
            'max' => $caps['max_product_pushes'] ?? 0,
        ]);
    }

    public function canAddOffer(Business $business): bool
    {
        $caps = $this->effectiveCaps($business);
        if ($caps['max_offers'] === null) {
            return true;
        }

        return $business->rewards()->count() < $caps['max_offers'];
    }

    public function offerLimitMessage(Business $business): string
    {
        $caps = $this->effectiveCaps($business);
        $plan = $this->planFor($business);

        return __('loop.offer_limit_reached', [
            'plan' => $plan?->name ?? 'Trial',
            'max' => $caps['max_offers'] ?? 0,
        ]);
    }

    public function canAcceptMember(Business $business): bool
    {
        $caps = $this->effectiveCaps($business);
        if ($caps['max_members'] === null) {
            return true;
        }

        return $business->memberships()->count() < $caps['max_members'];
    }

    public function memberLimitMessage(Business $business): string
    {
        $caps = $this->effectiveCaps($business);
        $plan = $this->planFor($business);

        return __('loop.member_limit_reached', [
            'plan' => $plan?->name ?? 'Trial',
            'max' => $caps['max_members'] ?? 0,
        ]);
    }

    public function canRecordVisit(Business $business): bool
    {
        if (! $this->canUseTill($business)) {
            return false;
        }

        $caps = $this->effectiveCaps($business);
        if ($caps['max_monthly_visits'] === null) {
            return true;
        }

        $used = $business->visits()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return $used < $caps['max_monthly_visits'];
    }

    public function visitLimitMessage(Business $business): string
    {
        if (! $this->canUseTill($business)) {
            return $this->trialExpiredMessage();
        }

        $caps = $this->effectiveCaps($business);
        $plan = $this->planFor($business);

        return __('loop.visit_limit_reached', [
            'plan' => $plan?->name ?? 'Trial',
            'max' => $caps['max_monthly_visits'] ?? 0,
        ]);
    }

    public function looksLikeMultiBranchAbuse(Business $business): bool
    {
        $caps = $this->effectiveCaps($business);
        if (($caps['max_shops'] ?? 1) > 1) {
            return false;
        }

        $monthVisits = $business->visits()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $cap = $caps['max_monthly_visits'] ?? 50;

        return $monthVisits >= (int) floor($cap * 0.8);
    }

    public function syncTrialStatus(Business $business): void
    {
        if (! $this->trialExpired($business)) {
            return;
        }

        if (Plans::isPaidPlan($business->plan_key) && $business->billing_status === 'active') {
            return;
        }

        if ($business->billing_status !== 'past_due') {
            $business->update(['billing_status' => 'past_due']);
        }
    }

    public function rafflesEnabled(Business $business): bool
    {
        if (! \App\Support\FeatureFlags::enabled('raffles')) {
            return false;
        }

        $plan = $this->planFor($business);
        if ($plan && $plan->exists) {
            return (bool) $plan->has_raffles;
        }

        $catalog = Plans::catalog()[$business->plan_key] ?? [];

        return (bool) ($catalog['has_raffles'] ?? false);
    }

    public function smsEnabled(Business $business): bool
    {
        if (! \App\Support\FeatureFlags::enabled('sms_messaging')) {
            return false;
        }

        $plan = $this->planFor($business);
        if ($plan && $plan->exists) {
            return (bool) $plan->has_sms;
        }

        $catalog = Plans::catalog()[$business->plan_key] ?? [];

        return (bool) ($catalog['has_sms'] ?? false);
    }
}
