<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessReferral;
use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function index(): View
    {
        return view('admin.referrals.index', [
            'referrals' => BusinessReferral::query()
                ->with(['referrer', 'referred'])
                ->latest()
                ->paginate(25),
        ]);
    }

    public function qualify(BusinessReferral $referral): RedirectResponse
    {
        if ($referral->isPending()) {
            $referral->update([
                'status' => BusinessReferral::STATUS_QUALIFIED,
                'qualified_at' => now(),
            ]);
        }

        return back()->with('status', __('loop.admin_referral_qualified'));
    }

    public function reward(BusinessReferral $referral, ReferralService $referrals): RedirectResponse
    {
        if ($referral->isPending()) {
            $referral->update([
                'status' => BusinessReferral::STATUS_QUALIFIED,
                'qualified_at' => now(),
            ]);
            $referral = $referral->fresh();
        }

        $referrals->reward($referral);

        return back()->with('status', __('loop.admin_referral_rewarded'));
    }
}
