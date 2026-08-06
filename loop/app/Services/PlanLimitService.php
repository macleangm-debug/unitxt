<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Plan;

class PlanLimitService
{
    public function planFor(Business $business): ?Plan
    {
        return $business->relationLoaded('plan')
            ? $business->plan
            : Plan::query()->where('key', $business->plan_key ?: 'free')->first();
    }

    public function canAddShop(Business $business): bool
    {
        $plan = $this->planFor($business);
        if (! $plan || $plan->max_shops === null) {
            return true;
        }

        return $business->shops()->count() < $plan->max_shops;
    }

    public function shopLimitMessage(Business $business): string
    {
        $plan = $this->planFor($business);
        $max = $plan?->max_shops ?? 1;

        return __('loop.shop_limit_reached', [
            'plan' => $plan?->name ?? 'Free',
            'max' => $max,
        ]);
    }

    public function canAcceptMember(Business $business): bool
    {
        $plan = $this->planFor($business);
        if (! $plan || $plan->max_members === null) {
            return true;
        }

        return $business->memberships()->count() < $plan->max_members;
    }

    public function memberLimitMessage(Business $business): string
    {
        $plan = $this->planFor($business);

        return __('loop.member_limit_reached', [
            'plan' => $plan?->name ?? 'Free',
            'max' => $plan?->max_members ?? 0,
        ]);
    }

    public function canRecordVisit(Business $business): bool
    {
        $plan = $this->planFor($business);
        if (! $plan || $plan->max_monthly_visits === null) {
            return true;
        }

        $used = $business->visits()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return $used < $plan->max_monthly_visits;
    }

    public function visitLimitMessage(Business $business): string
    {
        $plan = $this->planFor($business);

        return __('loop.visit_limit_reached', [
            'plan' => $plan?->name ?? 'Free',
            'max' => $plan?->max_monthly_visits ?? 0,
        ]);
    }

    /**
     * Soft signal for admin: free/single-shop brands with heavy traffic may be multi-branch.
     */
    public function looksLikeMultiBranchAbuse(Business $business): bool
    {
        $plan = $this->planFor($business);
        if (! $plan || ($plan->max_shops ?? 1) > 1) {
            return false;
        }

        $monthVisits = $business->visits()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $cap = $plan->max_monthly_visits ?? 300;

        return $monthVisits >= (int) floor($cap * 0.8);
    }
}
