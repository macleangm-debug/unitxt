<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isAffiliate(), 403);

        $affiliate = $user->affiliateProfile;
        abort_unless($affiliate && $affiliate->isActive(), 403);

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
        ]);
    }
}
