<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\LoopAccess;
use App\Services\Payments\PaymentService;
use App\Services\PlanLimitService;
use App\Support\BillingSettings;
use App\Support\Confirm;
use App\Support\Countries;
use App\Support\Plans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function show(Request $request, PlanLimitService $limits): View
    {
        return view('billing.upgrade', $this->journey($request, $limits));
    }

    public function plans(Request $request, PlanLimitService $limits): View
    {
        return view('billing.plans', $this->journey($request, $limits));
    }

    public function choose(Request $request, PlanLimitService $limits, PaymentService $payments): RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        $data = $request->validate([
            'plan_key' => ['required', 'in:starter,growth,scale'],
            'phone' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2'],
            'months' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $country = strtoupper($data['country']);
        abort_unless(isset(Countries::OPTIONS[$country]), 422);
        $plan = Plan::locate($data['plan_key'], $country);
        abort_unless($plan && $plan->is_public, 404);
        $months = (int) ($data['months'] ?? 1);

        $monthly = $business->effectiveMonthlyPrice();
        if ($monthly <= 0) {
            $monthly = (int) $plan->price_monthly;
        }
        $amount = BillingSettings::amountForMonths($monthly, $months);

        if ($amount <= 0) {
            app(LoopAccess::class)->activate($business, $plan->key, $months, $monthly);

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

    /**
     * @return array<string, mixed>
     */
    private function journey(Request $request, PlanLimitService $limits): array
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        $limits->syncTrialStatus($business->fresh());
        $business = $business->fresh();
        $access = app(LoopAccess::class);
        $phase = $access->phase($business);
        $paused = $phase === LoopAccess::PHASE_PAUSED;
        $grace = $phase === LoopAccess::PHASE_GRACE;
        $trialExpired = $limits->trialExpired($business);
        $daysLeft = $business->trial_ends_at && $business->trial_ends_at->isFuture()
            ? (int) now()->diffInDays($business->trial_ends_at)
            : 0;
        $daysUntilDue = $access->daysUntilDue($business);
        $country = session('preferred_country', $business->country ?? 'TZ');
        $monthly = $business->effectiveMonthlyPrice();
        $growth = Plan::locate('growth', $country);
        $quoteMonthly = $monthly > 0 ? $monthly : (int) ($growth?->price_monthly ?? 0);
        $payPlanKey = Plans::isPaidPlan($business->plan_key) ? $business->plan_key : Plans::GROWTH;
        $requested = (int) $request->query('months', 0);
        if ($requested >= 1 && $requested <= 12) {
            $selectedMonths = $requested;
        } elseif ($paused || $grace) {
            $selectedMonths = 12;
        } else {
            $selectedMonths = 6;
        }

        $untils = [];
        $monthLabels = [];
        for ($months = 1; $months <= 12; $months++) {
            $untils[$months] = BillingSettings::expiresAt($months)->translatedFormat('j F Y');
            $monthLabels[$months] = trans_choice('loop.interval_month_count', $months, ['count' => $months]);
        }

        $plans = Plan::forCountry($country)->where('is_public', true)->get();
        $planPrices = $plans
            ->where('key', '!=', 'free')
            ->mapWithKeys(fn (Plan $plan) => [$plan->key => (int) $plan->price_monthly])
            ->all();

        $coverageEnds = $access->coverageEndsAt($business);
        $showValue = $paused || $grace || $trialExpired
            || ($daysUntilDue !== null && $daysUntilDue <= 14)
            || ($business->billing_status === 'trialing' && $daysLeft <= 14);

        return [
            'business' => $business,
            'plans' => $plans,
            'currentPlan' => Plan::locate($business->plan_key, $country),
            'payPlan' => Plan::locate($payPlanKey, $country),
            'payPlanKey' => $payPlanKey,
            'trialExpired' => $trialExpired,
            'phase' => $phase,
            'paused' => $paused,
            'grace' => $grace,
            'graceDaysLeft' => $access->graceDaysLeft($business),
            'momentum' => $access->momentum($business),
            'showValue' => $showValue,
            'caps' => $limits->effectiveCaps($business),
            'daysLeft' => $daysLeft,
            'daysUntilDue' => $daysUntilDue,
            'country' => $country,
            'dial' => Countries::dial($country),
            'currency' => Countries::currency($country),
            'quoteMonthly' => $quoteMonthly,
            'selectedMonths' => $selectedMonths,
            'discounts' => BillingSettings::monthDiscounts(),
            'untils' => $untils,
            'monthLabels' => $monthLabels,
            'coverageUntil' => $coverageEnds?->translatedFormat('j F Y'),
            'planPrices' => $planPrices,
            'planNames' => $plans
                ->where('key', '!=', 'free')
                ->mapWithKeys(fn (Plan $plan) => [$plan->key => $plan->name])
                ->all(),
            'history' => $business->paymentIntents()
                ->where('purpose', 'plan_upgrade')
                ->latest('id')
                ->limit(20)
                ->get(),
            'payPhone' => $request->user()->phone,
        ];
    }
}
