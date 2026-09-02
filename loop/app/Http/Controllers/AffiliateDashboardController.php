<?php

namespace App\Http\Controllers;

use App\Services\AffiliateService;
use App\Support\AffiliateProgram;
use App\Support\Confirm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateDashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAffiliate(), 403);

        $affiliate = $user->affiliateProfile;
        abort_unless($affiliate && $affiliate->isActive(), 403);

        if ($affiliate->needsSetup()) {
            return redirect()->route('affiliate.setup');
        }

        $referrals = $affiliate->referrals()->with('business')->latest()->get();
        $settings = AffiliateProgram::settings();
        $monthStart = now()->copy()->startOfMonth();
        $monthPaying = (int) $affiliate->referrals()
            ->where('created_at', '>=', $monthStart)
            ->whereIn('status', ['qualified', 'commissioned', 'paid'])
            ->count();
        $target = (int) $settings['monthly_paying_business_target'];

        return view('affiliates.dashboard', [
            'affiliate' => $affiliate,
            'referrals' => $referrals,
            'stats' => [
                'signups' => $referrals->count(),
                'qualified' => $referrals->whereIn('status', ['qualified', 'commissioned', 'paid'])->count(),
                'earned' => (int) $referrals->whereIn('status', ['commissioned', 'paid'])->sum('commission_amount'),
                'pending' => (int) $referrals->where('status', 'pending')->count(),
            ],
            'kpi' => [
                'enabled' => (bool) $settings['kpi_enabled'] && (bool) $settings['show_kpis_to_affiliates'],
                'target' => $target,
                'month_paying' => $monthPaying,
                'met' => $monthPaying >= $target,
            ],
            'shareUrl' => \App\Support\PlatformUrl::route('business.register', ['ref' => $affiliate->promo_code]),
            'shareText' => __('loop.affiliate_share_text', [
                'code' => $affiliate->promo_code,
                'url' => \App\Support\PlatformUrl::route('business.register', ['ref' => $affiliate->promo_code]),
            ]),
            'currency' => \App\Support\Countries::currency($affiliate->country ?? 'TZ'),
            'available' => $affiliate->availableCommission(),
            'minPayout' => (int) $settings['min_payout_amount'],
            'hasPayoutAccount' => $affiliate->hasPayoutAccount(),
        ]);
    }

    public function setupForm(Request $request): View|RedirectResponse
    {
        $affiliate = $this->activeAffiliate($request);
        if (! $affiliate->needsSetup()) {
            return redirect()->route('affiliate.dashboard');
        }

        return view('affiliates.setup', [
            'affiliate' => $affiliate,
            'suggested' => $affiliate->promo_code,
        ]);
    }

    public function setup(Request $request, AffiliateService $affiliates): RedirectResponse
    {
        $affiliate = $this->activeAffiliate($request);

        $data = $request->validate([
            'promo_code' => ['required', 'string', 'min:4', 'max:12', 'regex:/^[A-Za-z0-9]+$/'],
        ]);

        $affiliates->completeSetup($affiliate, $data['promo_code']);
        $fresh = $affiliate->fresh();

        return redirect()->route('affiliate.dashboard')->with('confirm', Confirm::make(
            __('loop.affiliate_setup_done_title'),
            __('loop.affiliate_setup_done_body', ['code' => $fresh->promo_code]),
            __('loop.start_sharing'),
            route('affiliate.dashboard'),
        ));
    }

    public function updatePromo(Request $request, AffiliateService $affiliates): RedirectResponse
    {
        $affiliate = $this->activeAffiliate($request);

        $data = $request->validate([
            'promo_code' => ['required', 'string', 'min:4', 'max:12', 'regex:/^[A-Za-z0-9]+$/'],
        ]);

        $affiliates->updatePromoCode($affiliate, $data['promo_code']);

        return back()->with('confirm', Confirm::make(
            __('loop.promo_updated_title'),
            __('loop.promo_updated_body', ['code' => $affiliate->fresh()->promo_code]),
            __('loop.start_sharing'),
            route('affiliate.dashboard'),
            false,
        ));
    }

    public function payoutForm(Request $request): RedirectResponse
    {
        $this->activeAffiliate($request);

        return redirect()->route('affiliate.withdraw');
    }

    public function withdrawForm(Request $request): View|RedirectResponse
    {
        $affiliate = $this->activeAffiliate($request);
        if ($affiliate->needsSetup()) {
            return redirect()->route('affiliate.setup');
        }

        $settings = AffiliateProgram::settings();

        return view('affiliates.withdraw', [
            'affiliate' => $affiliate,
            'available' => $affiliate->availableCommission(),
            'minPayout' => (int) $settings['min_payout_amount'],
            'currency' => \App\Support\Countries::currency($affiliate->country ?? 'TZ'),
            'hasAccount' => $affiliate->hasPayoutAccount(),
        ]);
    }

    public function withdraw(Request $request): RedirectResponse
    {
        $affiliate = $this->activeAffiliate($request);
        if ($affiliate->needsSetup()) {
            return redirect()->route('affiliate.setup');
        }

        $settings = AffiliateProgram::settings();

        $data = $request->validate([
            'payout_method' => ['required', 'in:phone,bank'],
            'payout_phone' => ['nullable', 'required_if:payout_method,phone', 'string', 'max:40'],
            'bank_name' => ['nullable', 'required_if:payout_method,bank', 'string', 'max:120'],
            'payout_account_name' => ['required', 'string', 'max:120'],
        ]);

        $affiliate->update([
            'payout_method' => $data['payout_method'],
            'payout_phone' => $data['payout_method'] === 'phone' ? $data['payout_phone'] : null,
            'bank_name' => $data['payout_method'] === 'bank' ? $data['bank_name'] : null,
            'payout_account_name' => $data['payout_account_name'],
        ]);

        $available = $affiliate->fresh()->availableCommission();
        $min = (int) $settings['min_payout_amount'];

        if ($available < $min) {
            return redirect()->route('affiliate.withdraw')->with('confirm', Confirm::make(
                __('loop.payout_account_saved_title'),
                __('loop.payout_account_saved_wait', [
                    'min' => number_format($min),
                    'available' => number_format($available),
                ]),
                __('loop.done'),
                route('affiliate.dashboard'),
                false,
            ));
        }

        $affiliate->referrals()->where('status', 'commissioned')->update(['status' => 'paid']);

        return redirect()->route('affiliate.dashboard')->with('confirm', Confirm::make(
            __('loop.withdraw_requested_title'),
            __('loop.withdraw_requested_body', [
                'amount' => number_format($available),
            ]),
            __('loop.done'),
            route('affiliate.dashboard'),
            false,
        ));
    }

    /**
     * @deprecated Payout details are confirmed during withdraw.
     */
    public function updatePayout(Request $request): RedirectResponse
    {
        return $this->withdraw($request);
    }

    private function activeAffiliate(Request $request): \App\Models\Affiliate
    {
        $user = $request->user();
        abort_unless($user->isAffiliate(), 403);
        $affiliate = $user->affiliateProfile;
        abort_unless($affiliate && $affiliate->isActive(), 403);

        return $affiliate;
    }
}
