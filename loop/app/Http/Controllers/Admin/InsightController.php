<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateReferral;
use App\Models\Business;
use App\Models\PaymentIntent;
use App\Services\AdminReportService;
use App\Support\AffiliateProgram;
use App\Support\Sectors;
use Illuminate\View\View;

class InsightController extends Controller
{
    public function sectors(AdminReportService $reports): View
    {
        $salesBySector = $reports->salesBySector();
        $businessesBySector = $reports->businessesBySector();
        $maxGmv = max(1, (float) $salesBySector->max('revenue'));
        $maxBiz = max(1, (int) $businessesBySector->max('businesses'));

        return view('admin.insights.sectors', [
            'salesBySector' => $salesBySector,
            'businessesBySector' => $businessesBySector,
            'maxGmv' => $maxGmv,
            'maxBiz' => $maxBiz,
            'topGmv' => $salesBySector->first(),
            'topBiz' => $businessesBySector->first(),
            'sectorCount' => count(Sectors::all()),
        ]);
    }

    public function packages(AdminReportService $reports): View
    {
        $packages = $reports->packagePerformance();
        $payments = PaymentIntent::query()
            ->with(['business', 'user'])
            ->latest()
            ->limit(50)
            ->get();

        $businesses = Business::query()
            ->with('plan')
            ->where('billing_status', 'active')
            ->whereIn('plan_key', ['starter', 'growth', 'scale'])
            ->latest()
            ->paginate(30);

        return view('admin.insights.packages', [
            'packages' => $packages,
            'payments' => $payments,
            'businesses' => $businesses,
            'estimatedMrr' => (float) $packages->sum('estimated_mrr'),
            'paidActive' => $businesses->total(),
        ]);
    }

    public function tillBusinesses(AdminReportService $reports): View
    {
        $rows = $reports->customersByBusiness(200);

        return view('admin.insights.till-businesses', [
            'rows' => $rows,
            'totalGmv' => (float) $rows->sum('revenue'),
            'totalSales' => (int) $rows->sum('sales_count'),
        ]);
    }

    public function affiliatePerformance(): View
    {
        $settings = AffiliateProgram::settings();
        $target = (int) $settings['monthly_paying_business_target'];
        $monthStart = now()->copy()->startOfMonth();

        $affiliates = Affiliate::query()
            ->withCount([
                'referrals',
                'referrals as month_referrals_count' => fn ($q) => $q->where('created_at', '>=', $monthStart),
                'referrals as month_paying_count' => fn ($q) => $q
                    ->where('created_at', '>=', $monthStart)
                    ->whereIn('status', ['qualified', 'commissioned', 'paid']),
            ])
            ->withSum(['referrals as commission_earned' => fn ($q) => $q->whereIn('status', ['commissioned', 'paid'])], 'commission_amount')
            ->latest()
            ->paginate(30);

        $totals = [
            'affiliates' => Affiliate::query()->count(),
            'active' => Affiliate::query()->where('status', 'active')->count(),
            'pending' => Affiliate::query()->where('status', 'pending')->count(),
            'month_signups' => AffiliateReferral::query()->where('created_at', '>=', $monthStart)->count(),
            'month_paying' => AffiliateReferral::query()
                ->where('created_at', '>=', $monthStart)
                ->whereIn('status', ['qualified', 'commissioned', 'paid'])
                ->count(),
            'commission' => (int) AffiliateReferral::query()
                ->whereIn('status', ['commissioned', 'paid'])
                ->sum('commission_amount'),
            'target' => $target,
        ];

        return view('admin.insights.affiliate-performance', [
            'affiliates' => $affiliates,
            'totals' => $totals,
            'settings' => $settings,
        ]);
    }
}
