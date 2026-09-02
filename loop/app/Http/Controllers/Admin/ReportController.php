<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminReportService;
use App\Services\ReportExportService;
use App\Support\ReportPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, AdminReportService $reports): View
    {
        $period = ReportPeriod::fromRequest($request);

        return view('admin.reports.index', [
            'period' => $period,
            'overview' => $reports->overview($period),
            'customersBySector' => $reports->customersBySector(),
            'salesBySector' => $reports->salesBySector($period),
            'customersByBusiness' => $reports->customersByBusiness(100),
            'dailySales' => $reports->dailySales($period->dayCount(), $period),
            'exportTypes' => $this->exportTypes(),
        ]);
    }

    public function export(Request $request, AdminReportService $reports, ReportExportService $exporter): StreamedResponse|BinaryFileResponse
    {
        $allowed = array_keys($this->exportTypes());

        $reportKeys = collect($request->input('reports', []))
            ->when($request->filled('type'), fn ($c) => $c->push($request->input('type')))
            ->filter(fn ($key) => in_array($key, $allowed, true))
            ->unique()
            ->values()
            ->all();

        $formats = collect($request->input('formats', []))
            ->when($request->filled('format'), fn ($c) => $c->push($request->input('format')))
            ->map(fn ($f) => strtolower((string) $f))
            ->filter(fn ($f) => in_array($f, ['csv', 'tsv', 'json'], true))
            ->unique()
            ->values()
            ->all();

        if ($reportKeys === []) {
            $reportKeys = ['overview'];
        }
        if ($formats === []) {
            $formats = ['csv'];
        }

        $datasets = [];
        foreach ($reportKeys as $key) {
            $datasets[] = $this->datasetFor($key, $reports);
        }

        return $exporter->downloadMany($datasets, $formats);
    }

    /**
     * @return array<string, string>
     */
    private function exportTypes(): array
    {
        return [
            'overview' => __('loop.admin_overview'),
            'customers_by_sector' => __('loop.customers_by_sector'),
            'customers_by_business' => __('loop.customers_by_business'),
            'sales_by_sector' => __('loop.sales_by_sector'),
            'businesses_by_sector' => __('loop.report_businesses_by_sector'),
            'packages' => __('loop.report_packages'),
            'daily_sales' => __('loop.last_14_days'),
            'daily_signups' => __('loop.report_daily_signups'),
            'affiliates' => __('loop.report_affiliates'),
            'affiliate_referrals' => __('loop.report_affiliate_referrals'),
            'referrals' => __('loop.report_referrals'),
            'payments' => __('loop.report_payments'),
        ];
    }

    /**
     * @return array{basename: string, headers: list<string>, rows: \Illuminate\Support\Collection}
     */
    private function datasetFor(string $type, AdminReportService $reports): array
    {
        return match ($type) {
            'customers_by_sector' => [
                'basename' => 'customers-by-sector',
                'headers' => ['sector', 'unique_customers'],
                'rows' => $reports->customersBySector()->map(fn ($r) => [$r->sector_label, $r->unique_customers]),
            ],
            'customers_by_business' => [
                'basename' => 'customers-by-business',
                'headers' => ['business', 'sector', 'city', 'unique_customers', 'sales', 'revenue', 'plan', 'billing_status'],
                'rows' => $reports->customersByBusiness(500)->map(fn ($r) => [
                    $r->name,
                    $r->sector_label,
                    $r->city,
                    $r->unique_customers,
                    $r->sales_count,
                    $r->revenue,
                    $r->plan_key,
                    $r->billing_status,
                ]),
            ],
            'sales_by_sector' => [
                'basename' => 'sales-by-sector',
                'headers' => ['sector', 'sales', 'revenue', 'unique_customers', 'businesses'],
                'rows' => $reports->salesBySector()->map(fn ($r) => [
                    $r->sector_label,
                    $r->sales_count,
                    $r->revenue,
                    $r->unique_customers,
                    $r->businesses,
                ]),
            ],
            'businesses_by_sector' => [
                'basename' => 'businesses-by-sector',
                'headers' => ['sector', 'businesses', 'active_businesses', 'paid_businesses'],
                'rows' => $reports->businessesBySector()->map(fn ($r) => [
                    $r->sector_label,
                    $r->businesses,
                    $r->active_businesses,
                    $r->paid_businesses,
                ]),
            ],
            'packages' => [
                'basename' => 'packages',
                'headers' => ['plan', 'price_monthly', 'currency', 'subscribers', 'trialing', 'total', 'estimated_mrr'],
                'rows' => $reports->packagePerformance()->map(fn ($r) => [
                    $r->name,
                    $r->price_monthly,
                    $r->currency,
                    $r->subscribers,
                    $r->trialing,
                    $r->total,
                    $r->estimated_mrr,
                ]),
            ],
            'daily_sales' => [
                'basename' => 'daily-sales',
                'headers' => ['day', 'sales', 'revenue'],
                'rows' => $reports->dailySales(90)->map(fn ($r) => [$r->day, $r->sales_count, $r->revenue]),
            ],
            'daily_signups' => [
                'basename' => 'daily-signups',
                'headers' => ['day', 'signups'],
                'rows' => $reports->dailySignups(90)->map(fn ($r) => [$r->day, $r->signups]),
            ],
            'affiliates' => [
                'basename' => 'affiliates',
                'headers' => ['name', 'phone', 'status', 'promo_code', 'created_at'],
                'rows' => $reports->affiliatesExport()->map(fn ($r) => [
                    $r->name,
                    $r->full_phone,
                    $r->status,
                    $r->promo_code,
                    optional($r->created_at)?->toDateTimeString(),
                ]),
            ],
            'affiliate_referrals' => [
                'basename' => 'affiliate-referrals',
                'headers' => ['affiliate', 'business', 'status', 'commission', 'created_at'],
                'rows' => $reports->affiliateReferralsExport()->map(fn ($r) => [
                    $r->affiliate_name,
                    $r->business_name,
                    $r->status,
                    $r->commission_amount,
                    optional($r->created_at)?->toDateTimeString(),
                ]),
            ],
            'referrals' => [
                'basename' => 'business-referrals',
                'headers' => ['referrer', 'referred', 'code', 'status', 'created_at'],
                'rows' => $reports->businessReferralsExport()->map(fn ($r) => [
                    $r->referrer_name,
                    $r->referred_name,
                    $r->code_used,
                    $r->status,
                    optional($r->created_at)?->toDateTimeString(),
                ]),
            ],
            'payments' => [
                'basename' => 'payments',
                'headers' => ['reference', 'purpose', 'amount', 'currency', 'status', 'phone', 'created_at'],
                'rows' => $reports->paymentsExport()->map(fn ($r) => [
                    $r->reference,
                    $r->purpose,
                    $r->amount,
                    $r->currency,
                    $r->status,
                    $r->phone,
                    optional($r->created_at)?->toDateTimeString(),
                ]),
            ],
            default => [
                'basename' => 'overview',
                'headers' => ['metric', 'value'],
                'rows' => collect($reports->overview())
                    ->filter(fn ($v) => is_scalar($v) || $v === null)
                    ->map(fn ($v, $k) => [$k, $v]),
            ],
        };
    }
}
