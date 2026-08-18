<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\Payments\PaymentService;
use App\Services\PlanLimitService;
use App\Support\BillingSettings;
use App\Support\Confirm;
use App\Support\Countries;
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
        $country = session('preferred_country', $business->country ?? 'TZ');

        return view('billing.upgrade', [
            'business' => $business,
            'plans' => Plan::query()->where('is_public', true)->orderBy('sort_order')->get(),
            'currentPlan' => Plan::query()->where('key', $business->plan_key)->first(),
            'trialExpired' => $limits->trialExpired($business),
            'caps' => $limits->effectiveCaps($business),
            'daysLeft' => $business->trial_ends_at && $business->trial_ends_at->isFuture()
                ? (int) now()->diffInDays($business->trial_ends_at)
                : 0,
            'country' => $country,
            'dial' => Countries::dial($country),
            'currency' => Countries::currency($country),
            'intervals' => BillingSettings::intervalDiscounts(),
        ]);
    }

    public function choose(Request $request, PlanLimitService $limits, PaymentService $payments): RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        $data = $request->validate([
            'plan_key' => ['required', 'in:starter,growth,scale'],
            'phone' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2'],
            'months' => ['nullable', 'integer', 'in:1,3,6,12'],
        ]);

        $plan = Plan::query()->where('key', $data['plan_key'])->where('is_public', true)->firstOrFail();
        $country = strtoupper($data['country']);
        abort_unless(isset(Countries::OPTIONS[$country]), 422);
        $months = (int) ($data['months'] ?? 1);

        $monthly = $business->effectiveMonthlyPrice();
        if ($monthly <= 0) {
            $monthly = (int) $plan->price_monthly;
        }
        $amount = BillingSettings::amountForMonths($monthly, $months);

        if ($amount <= 0) {
            $business->update([
                'plan_key' => $plan->key,
                'billing_status' => 'active',
                'trial_ends_at' => null,
                'plan_interval_months' => $months,
                'plan_renews_at' => now()->addMonths($months),
            ]);

            return redirect()->route('billing.show')->with('confirm', Confirm::make(
                __('loop.plan_activated_title', ['plan' => $plan->name]),
                __('loop.plan_activated', ['plan' => $plan->name]),
                __('loop.done'),
                route('billing.show'),
                true,
            ));
        }

        $intent = $payments->startPlanPayment($business, $request->user(), $plan, $data['phone'], $country, $months);

        return redirect()->route('payments.wait', $intent);
    }
}
