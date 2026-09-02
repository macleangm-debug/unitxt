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
        $perPage = \App\Support\AdminPagination::perPage($request, 25);

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
            ->paginate($perPage)
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
            'summary' => [
                'total' => Business::query()->count(),
                'live' => Business::query()->where('is_active', true)->count(),
                'members' => \App\Models\Membership::query()->count(),
                'sales' => \App\Models\Visit::query()->count(),
            ],
        ]);
    }

    public function show(Business $business, PlanLimitService $limits): View
    {
        $business->load([
            'owner',
            'plan',
            'shops',
            'campaigns' => fn ($q) => $q->latest()->take(10),
            'rewards' => fn ($q) => $q->latest()->take(10),
            'raffles' => fn ($q) => $q->latest()->take(10),
        ]);
        $business->loadCount(['shops', 'memberships', 'visits', 'rewards', 'campaigns', 'referralsMade', 'raffles']);

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

        $roleCounts = [
            'owner' => $business->staff()->where('role', \App\Models\User::ROLE_OWNER)->count() ?: ($business->owner_id ? 1 : 0),
            'front_desk' => $business->frontDeskStaff()->count(),
        ];

        return view('admin.businesses.show', [
            'business' => $business,
            'plans' => Plan::forCountry($business->country)->get(),
            'abuseFlag' => $limits->looksLikeMultiBranchAbuse($business),
            'sectorPeers' => $sectorPeers,
            'recentVisits' => $recentVisits,
            'revenue' => $revenue,
            'salesMonth' => $salesMonth,
            'revenueMonth' => $revenueMonth,
            'roleCounts' => $roleCounts,
        ]);
    }

    public function update(Request $request, Business $business): RedirectResponse
    {
        $data = $request->validate([
            'plan_key' => ['required', 'string', 'max:40'],
            'billing_status' => ['required', 'in:trialing,active,past_due,free,paused,suspended'],
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
