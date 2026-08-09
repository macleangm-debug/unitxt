<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessReferral;
use App\Models\Plan;
use App\Services\AdminReportService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(AdminReportService $reports): View
    {
        $tab = request('tab', 'pulse');
        $allowed = ['pulse', 'till', 'subscriptions', 'sectors', 'growth'];
        if (! in_array($tab, $allowed, true)) {
            $tab = 'pulse';
        }

        $overview = $reports->overview();
        $packages = $reports->packagePerformance();
        $salesBySector = $reports->salesBySector();
        $businessesBySector = $reports->businessesBySector();
        $dailySales = $reports->dailySales(14);
        $dailySignups = $reports->dailySignups(14);

        return view('admin.dashboard', [
            'tab' => $tab,
            ...$overview,
            'packages' => $packages,
            'salesBySector' => $salesBySector,
            'businessesBySector' => $businessesBySector,
            'dailySales' => $dailySales,
            'dailySignups' => $dailySignups,
            'topBusinesses' => $reports->customersByBusiness(8),
            'referralPending' => $overview['referral_pending'],
            'referralQualified' => BusinessReferral::query()->where('status', BusinessReferral::STATUS_QUALIFIED)->count(),
            'referralRewarded' => $overview['referral_rewarded'],
            'plans' => Plan::query()->orderBy('sort_order')->get(),
            'insights' => $this->insights($overview, $packages, $salesBySector, $businessesBySector),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overview
     * @param  \Illuminate\Support\Collection  $packages
     * @param  \Illuminate\Support\Collection  $salesBySector
     * @param  \Illuminate\Support\Collection  $businessesBySector
     * @return list<array{tone: string, title: string, body: string}>
     */
    private function insights($overview, $packages, $salesBySector, $businessesBySector): array
    {
        $out = [];

        $topSectorGmv = $salesBySector->first();
        if ($topSectorGmv && (float) $topSectorGmv->revenue > 0) {
            $out[] = [
                'tone' => 'mint',
                'title' => __('loop.insight_top_sector_gmv_title'),
                'body' => __('loop.insight_top_sector_gmv_body', [
                    'sector' => $topSectorGmv->sector_label,
                    'amount' => number_format((float) $topSectorGmv->revenue),
                    'sales' => number_format((int) $topSectorGmv->sales_count),
                ]),
            ];
        }

        $topSectorBiz = $businessesBySector->first();
        if ($topSectorBiz) {
            $out[] = [
                'tone' => 'violet',
                'title' => __('loop.insight_top_sector_biz_title'),
                'body' => __('loop.insight_top_sector_biz_body', [
                    'sector' => $topSectorBiz->sector_label,
                    'count' => (int) $topSectorBiz->businesses,
                ]),
            ];
        }

        $topPkg = $packages->sortByDesc('subscribers')->first();
        if ($topPkg && (int) $topPkg->subscribers > 0) {
            $out[] = [
                'tone' => 'ink',
                'title' => __('loop.insight_top_package_title'),
                'body' => __('loop.insight_top_package_body', [
                    'plan' => $topPkg->name,
                    'count' => (int) $topPkg->subscribers,
                    'mrr' => number_format((float) $topPkg->estimated_mrr),
                ]),
            ];
        }

        if ((int) $overview['trialing'] > 0) {
            $out[] = [
                'tone' => 'coral',
                'title' => __('loop.insight_trials_title'),
                'body' => __('loop.insight_trials_body', [
                    'count' => (int) $overview['trialing'],
                    'pct' => (int) $overview['trial_conversion_pct'],
                ]),
            ];
        }

        if ((int) $overview['affiliate_pending'] > 0) {
            $out[] = [
                'tone' => 'violet',
                'title' => __('loop.insight_affiliates_title'),
                'body' => __('loop.insight_affiliates_body', ['count' => (int) $overview['affiliate_pending']]),
            ];
        }

        if ((int) $overview['features_off'] > 0) {
            $out[] = [
                'tone' => 'ink',
                'title' => __('loop.insight_features_title'),
                'body' => __('loop.insight_features_body', [
                    'on' => (int) $overview['features_on'],
                    'off' => (int) $overview['features_off'],
                ]),
            ];
        }

        if ($out === []) {
            $out[] = [
                'tone' => 'mint',
                'title' => __('loop.insight_empty_title'),
                'body' => __('loop.insight_empty_body'),
            ];
        }

        return $out;
    }
}
