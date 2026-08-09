<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Plan;
use App\Services\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessController extends Controller
{
    public function index(Request $request, PlanLimitService $limits): View
    {
        $q = trim((string) $request->query('q', ''));

        $businesses = Business::query()
            ->with(['owner', 'plan'])
            ->withCount(['shops', 'memberships', 'visits', 'referralsMade'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('referral_code', 'like', "%{$q}%")
                        ->orWhere('city', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $flags = [];
        foreach ($businesses as $business) {
            $flags[$business->id] = $limits->looksLikeMultiBranchAbuse($business);
        }

        return view('admin.businesses.index', [
            'businesses' => $businesses,
            'plans' => Plan::query()->orderBy('sort_order')->get(),
            'q' => $q,
            'abuseFlags' => $flags,
        ]);
    }

    public function show(Business $business, PlanLimitService $limits): View
    {
        $business->load(['owner', 'plan', 'shops']);
        $business->loadCount(['shops', 'memberships', 'visits', 'rewards', 'campaigns', 'referralsMade']);

        $sectorPeers = Business::query()
            ->where('sector', $business->sector)
            ->where('id', '!=', $business->id)
            ->where('is_active', true)
            ->withCount('visits')
            ->orderByDesc('visits_count')
            ->limit(5)
            ->get();

        $recentVisits = $business->visits()
            ->with(['customer', 'shop'])
            ->latest()
            ->limit(12)
            ->get();

        $revenue = (float) $business->visits()->sum('amount_spent');
        $salesMonth = $business->visits()->where('created_at', '>=', now()->startOfMonth())->count();
        $revenueMonth = (float) $business->visits()->where('created_at', '>=', now()->startOfMonth())->sum('amount_spent');

        return view('admin.businesses.show', [
            'business' => $business,
            'plans' => Plan::query()->orderBy('sort_order')->get(),
            'abuseFlag' => $limits->looksLikeMultiBranchAbuse($business),
            'sectorPeers' => $sectorPeers,
            'recentVisits' => $recentVisits,
            'revenue' => $revenue,
            'salesMonth' => $salesMonth,
            'revenueMonth' => $revenueMonth,
        ]);
    }

    public function update(Request $request, Business $business): RedirectResponse
    {
        $data = $request->validate([
            'plan_key' => ['required', 'exists:plans,key'],
            'billing_status' => ['required', 'in:trialing,active,past_due,free,suspended'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $business->update([
            'plan_key' => $data['plan_key'],
            'billing_status' => $data['billing_status'],
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $business->is_active,
        ]);

        return back()->with('status', __('loop.admin_business_updated'));
    }
}
