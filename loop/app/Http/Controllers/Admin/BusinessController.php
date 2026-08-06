<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessController extends Controller
{
    public function index(Request $request): View
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

        return view('admin.businesses.index', [
            'businesses' => $businesses,
            'plans' => Plan::query()->orderBy('sort_order')->get(),
            'q' => $q,
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
