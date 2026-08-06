<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function show(Request $request, PlanLimitService $limits): View
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        $limits->syncTrialStatus($business->fresh());
        $business = $business->fresh();

        return view('billing.upgrade', [
            'business' => $business,
            'plans' => Plan::query()->where('is_public', true)->orderBy('sort_order')->get(),
            'currentPlan' => Plan::query()->where('key', $business->plan_key)->first(),
            'trialExpired' => $limits->trialExpired($business),
            'caps' => $limits->effectiveCaps($business),
            'daysLeft' => $business->trial_ends_at && $business->trial_ends_at->isFuture()
                ? (int) now()->diffInDays($business->trial_ends_at)
                : 0,
        ]);
    }

    public function choose(Request $request, PlanLimitService $limits): RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        $data = $request->validate([
            'plan_key' => ['required', 'in:starter,growth,scale'],
        ]);

        $plan = Plan::query()->where('key', $data['plan_key'])->where('is_public', true)->firstOrFail();

        // Mobile Money checkout comes later — activate plan immediately for now.
        $business->update([
            'plan_key' => $plan->key,
            'billing_status' => 'active',
            'trial_ends_at' => null,
        ]);

        return redirect()
            ->route('billing.show')
            ->with('status', __('loop.plan_activated', ['plan' => $plan->name]));
    }
}
