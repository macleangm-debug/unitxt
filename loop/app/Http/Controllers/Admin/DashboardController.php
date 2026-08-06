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
        $overview = $reports->overview();

        return view('admin.dashboard', [
            ...$overview,
            'referralPending' => BusinessReferral::query()->where('status', BusinessReferral::STATUS_PENDING)->count(),
            'referralQualified' => BusinessReferral::query()->where('status', BusinessReferral::STATUS_QUALIFIED)->count(),
            'referralRewarded' => BusinessReferral::query()->where('status', BusinessReferral::STATUS_REWARDED)->count(),
            'salesBySector' => $reports->salesBySector(),
            'dailySales' => $reports->dailySales(14),
            'topBusinesses' => $reports->customersByBusiness(8),
            'plans' => Plan::query()->orderBy('sort_order')->get(),
        ]);
    }
}
