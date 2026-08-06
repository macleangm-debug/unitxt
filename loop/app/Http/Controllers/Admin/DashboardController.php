<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessReferral;
use App\Models\Plan;
use App\Models\User;
use App\Models\Visit;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'businessCount' => Business::query()->count(),
            'activeBusinesses' => Business::query()->where('is_active', true)->count(),
            'ownerCount' => User::query()->where('role', User::ROLE_OWNER)->count(),
            'customerCount' => User::query()->where('role', User::ROLE_CUSTOMER)->count(),
            'visitCount' => Visit::query()->count(),
            'referralPending' => BusinessReferral::query()->where('status', BusinessReferral::STATUS_PENDING)->count(),
            'referralQualified' => BusinessReferral::query()->where('status', BusinessReferral::STATUS_QUALIFIED)->count(),
            'referralRewarded' => BusinessReferral::query()->where('status', BusinessReferral::STATUS_REWARDED)->count(),
            'recentBusinesses' => Business::query()->with('owner')->latest()->take(8)->get(),
            'plans' => Plan::query()->orderBy('sort_order')->get(),
        ]);
    }
}
