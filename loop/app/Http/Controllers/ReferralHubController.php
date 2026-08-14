<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralHubController extends Controller
{
    public function __invoke(Request $request, ReferralService $referrals): View
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business, 403);

        $code = $referrals->ensureReferralCode($business);

        $connected = $business->referralsMade()
            ->with('referred')
            ->whereIn('status', [
                \App\Models\BusinessReferral::STATUS_QUALIFIED,
                \App\Models\BusinessReferral::STATUS_REWARDED,
            ])
            ->latest()
            ->get();

        return view('settings.referrals', [
            'business' => $business,
            'shareUrl' => $referrals->shareUrl($business),
            'code' => $code,
            'referrals' => $connected,
            'plan' => Plan::query()->where('key', $business->plan_key)->first(),
            'program' => $referrals->progress($business)['program'],
            'progress' => $referrals->progress($business),
        ]);
    }
}
