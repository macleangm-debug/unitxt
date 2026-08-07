<?php

namespace App\Http\Controllers;

use App\Services\AffiliateService;
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

        return view('affiliates.dashboard', [
            'affiliate' => $affiliate,
            'referrals' => $referrals,
            'stats' => [
                'signups' => $referrals->count(),
                'qualified' => $referrals->whereIn('status', ['qualified', 'commissioned', 'paid'])->count(),
                'earned' => (int) $referrals->whereIn('status', ['commissioned', 'paid'])->sum('commission_amount'),
                'pending' => (int) $referrals->where('status', 'pending')->count(),
            ],
            'shareUrl' => route('business.register', ['ref' => $affiliate->promo_code]),
            'shareText' => __('loop.affiliate_share_text', [
                'code' => $affiliate->promo_code,
                'url' => route('business.register', ['ref' => $affiliate->promo_code]),
            ]),
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

    private function activeAffiliate(Request $request): \App\Models\Affiliate
    {
        $user = $request->user();
        abort_unless($user->isAffiliate(), 403);
        $affiliate = $user->affiliateProfile;
        abort_unless($affiliate && $affiliate->isActive(), 403);

        return $affiliate;
    }
}
