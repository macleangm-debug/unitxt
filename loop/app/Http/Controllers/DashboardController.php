<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Membership;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->isBusiness()) {
            $business = $user->business;

            if (! $business) {
                return view('business.setup');
            }

            return view('dashboard.business', [
                'business' => $business,
                'shopCount' => $business->shops()->count(),
                'campaignCount' => $business->campaigns()->count(),
                'memberCount' => $business->memberships()->count(),
                'visitCount' => $business->visits()->count(),
                'recentVisits' => $business->visits()->with(['customer', 'shop', 'campaign'])->latest()->take(8)->get(),
                'activeCampaigns' => $business->campaigns()->active()->withCount('shops')->latest()->take(5)->get(),
            ]);
        }

        $memberships = Membership::query()
            ->with('business')
            ->where('customer_id', $user->id)
            ->latest()
            ->get();

        $recentVisits = Visit::query()
            ->with(['shop', 'business', 'campaign'])
            ->where('customer_id', $user->id)
            ->latest()
            ->take(8)
            ->get();

        $discover = Business::query()
            ->where('is_active', true)
            ->withCount(['shops', 'campaigns'])
            ->latest()
            ->take(6)
            ->get();

        return view('dashboard.customer', [
            'memberships' => $memberships,
            'recentVisits' => $recentVisits,
            'discover' => $discover,
            'totalPoints' => $memberships->sum('points_balance'),
        ]);
    }
}
