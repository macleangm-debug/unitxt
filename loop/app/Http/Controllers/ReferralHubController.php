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

        return view('settings.referrals', [
            'business' => $business,
            'shareUrl' => $referrals->shareUrl($business),
            'code' => $code,
            'referrals' => $business->referralsMade()->with('referred')->latest()->get(),
            'plan' => Plan::query()->where('key', $business->plan_key)->first(),
        ]);
    }
}
