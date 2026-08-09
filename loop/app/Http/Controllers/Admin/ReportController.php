<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminReportService;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(AdminReportService $reports): View
    {
        return view('admin.reports.index', [
            'overview' => $reports->overview(),
            'customersBySector' => $reports->customersBySector(),
            'salesBySector' => $reports->salesBySector(),
            'customersByBusiness' => $reports->customersByBusiness(100),
            'dailySales' => $reports->dailySales(30),
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
            'customers_by_sector' => __('loop.customers_by_sector'),
            'customers_by_business' => __('loop.customers_by_business'),
            'sales_by_sector' => __('loop.sales_by_sector'),
            'daily_sales' => __('loop.last_14_days'),
            'overview' => __('loop.admin_overview'),
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
                'headers' => ['sector', 'sales', 'revenue', 'unique_customers'],
                'rows' => $reports->salesBySector()->map(fn ($r) => [
                    $r->sector_label,
                    $r->sales_count,
                    $r->revenue,
                    $r->unique_customers,
                ]),
            ],
            'daily_sales' => [
                'basename' => 'daily-sales',
                'headers' => ['day', 'sales', 'revenue'],
                'rows' => $reports->dailySales(90)->map(fn ($r) => [$r->day, $r->sales_count, $r->revenue]),
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
