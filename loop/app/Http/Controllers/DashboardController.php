<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Membership;
use App\Models\Visit;
use App\Support\Sectors;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->isStaff()) {
            $business = $user->workplace();

            if (! $business) {
                return view('business.setup-missing');
            }

            return view('dashboard.business', [
                'business' => $business,
                'shopCount' => $business->shops()->count(),
                'campaignCount' => $business->campaigns()->count(),
                'memberCount' => $business->memberships()->count(),
                'visitCount' => $business->visits()->count(),
                'recentVisits' => $business->visits()->with(['customer', 'shop', 'recorder'])->latest()->take(8)->get(),
                'activeCampaigns' => $business->campaigns()->active()->withCount('shops')->latest()->take(5)->get(),
                'isOwner' => $user->isOwner(),
            ]);
        }

        $memberships = Membership::query()
            ->with('business')
            ->where('customer_id', $user->id)
            ->latest()
            ->get();

        $grouped = $memberships->groupBy(fn ($m) => $m->business->sector);

        return view('dashboard.customer', [
            'memberships' => $memberships,
            'grouped' => $grouped,
            'sectors' => Sectors::OPTIONS,
            'totalPoints' => $memberships->sum('points_balance'),
            'discover' => Business::query()
                ->where('is_active', true)
                ->where('country', 'TZ')
                ->withCount('shops')
                ->latest()
                ->take(8)
                ->get()
                ->groupBy('sector'),
        ]);
    }
}
