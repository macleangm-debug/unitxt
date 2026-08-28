<?php

namespace App\Http\Controllers;

use App\Support\FeatureFlags;
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
            'raffleCount' => $business->raffles()->count(),
            'gameCount' => \App\Support\GameSettings::tablesReady() ? $business->games()->count() : 0,
            'referralCount' => $business->referralsMade()->count(),
            'referralCredits' => (int) $business->referral_credit_days,
            'planKey' => $business->plan_key,
            'billingStatus' => $business->billing_status,
            'studioEnabled' => FeatureFlags::enabled('content_studio'),
            'rafflesEnabled' => FeatureFlags::enabled('raffles'),
            'gamesVisible' => \App\Support\GameSettings::engineOn(),
            'smsEnabled' => FeatureFlags::enabled('sms_messaging'),
        ]);
    }
}
