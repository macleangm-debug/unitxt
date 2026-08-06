<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        return view('settings.index', [
            'business' => $business,
            'shopCount' => $business->shops()->count(),
            'campaignCount' => $business->campaigns()->count(),
            'offerCount' => $business->rewards()->count(),
            'staffCount' => $business->staff()->count(),
            'referralCount' => $business->referralsMade()->count(),
            'referralCredits' => (int) $business->referral_credit_months,
        ]);
    }
}
