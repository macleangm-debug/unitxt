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
        $total = BusinessReferral::query()->count();
        $pending = BusinessReferral::query()->where('status', BusinessReferral::STATUS_PENDING)->count();
        $qualified = BusinessReferral::query()->where('status', BusinessReferral::STATUS_QUALIFIED)->count();
        $rewarded = BusinessReferral::query()->where('status', BusinessReferral::STATUS_REWARDED)->count();
        $converted = $qualified + $rewarded;

        return view('admin.referrals.index', [
            'referrals' => BusinessReferral::query()
                ->with(['referrer', 'referred'])
                ->latest()
                ->paginate(25),
            'counts' => [
                'pending' => $pending,
                'qualified' => $qualified,
                'rewarded' => $rewarded,
                'total' => $total,
                'conversion_pct' => $total > 0 ? (int) round(($converted / $total) * 100) : 0,
                'active_referrers' => (int) BusinessReferral::query()
                    ->whereNotNull('referrer_business_id')
                    ->selectRaw('count(distinct referrer_business_id) as aggregate')
                    ->value('aggregate'),
                'last_7_days' => BusinessReferral::query()->where('created_at', '>=', now()->subDays(7))->count(),
            ],
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
