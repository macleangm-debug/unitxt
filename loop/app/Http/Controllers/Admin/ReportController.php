<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminReportService;
use Illuminate\View\View;

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
        ]);
    }
}
