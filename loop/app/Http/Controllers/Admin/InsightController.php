<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateReferral;
use App\Models\Business;
use App\Models\PaymentIntent;
use App\Models\User;
use App\Services\AdminReportService;
use App\Support\AffiliateProgram;
use App\Support\Sectors;
use Illuminate\Http\Request;
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

    public function customers(Request $request): View
    {
        $monthStart = now()->copy()->startOfMonth();
        $perPage = \App\Support\AdminPagination::perPage($request, 25);

        $customers = \App\Models\User::query()
            ->where('role', 'customer')
            ->withCount([
                'memberships',
                'visits',
                'visits as month_visits_count' => fn ($q) => $q->where('created_at', '>=', $monthStart),
            ])
            ->withSum('visits as lifetime_spend', 'amount_spent')
            ->withSum(['visits as month_spend' => fn ($q) => $q->where('created_at', '>=', $monthStart)], 'amount_spent')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $totals = [
            'customers' => \App\Models\User::query()->where('role', 'customer')->count(),
            'active_month' => \App\Models\User::query()
                ->where('role', 'customer')
                ->whereHas('visits', fn ($q) => $q->where('created_at', '>=', $monthStart))
                ->count(),
            'memberships' => \App\Models\Membership::query()->count(),
            'avg_shops' => round((float) \App\Models\Membership::query()
                ->selectRaw('COUNT(*) * 1.0 / NULLIF(COUNT(DISTINCT customer_id), 0) as avg_shops')
                ->value('avg_shops') ?? 0, 1),
            'month_gmv' => (float) \App\Models\Visit::query()->where('created_at', '>=', $monthStart)->sum('amount_spent'),
            'scouts' => \App\Models\BusinessInvite::query()->count(),
        ];

        return view('admin.insights.customers', [
            'customers' => $customers,
            'totals' => $totals,
            'bySector' => app(AdminReportService::class)->customersBySector(),
        ]);
    }

    public function customer(User $customer): View
    {
        abort_unless($customer->isCustomer() || $customer->memberships()->exists(), 404);

        $memberships = $customer->memberships()
            ->with(['business', 'shop'])
            ->latest('joined_at')
            ->get();

        $visits = $customer->visits()
            ->with(['shop', 'business', 'campaign', 'reward'])
            ->latest()
            ->take(20)
            ->get();

        $raffleWins = \App\Models\RaffleWinner::query()
            ->with('raffle')
            ->where('customer_id', $customer->id)
            ->latest('drawn_at')
            ->get();

        $interests = collect($customer->interests ?? [])
            ->map(fn ($key) => Sectors::label((string) $key))
            ->filter()
            ->values();

        return view('admin.insights.customer', [
            'customer' => $customer,
            'memberships' => $memberships,
            'visits' => $visits,
            'raffleWins' => $raffleWins,
            'interests' => $interests,
            'points' => (int) $memberships->sum('points_balance'),
            'lifetime' => (int) $memberships->sum('lifetime_points'),
            'visitCount' => $customer->visits()->count(),
            'totalSpend' => (float) $customer->visits()->sum('amount_spent'),
        ]);
    }
}
