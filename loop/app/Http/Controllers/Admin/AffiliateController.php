<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Services\AffiliateService;
use App\Support\AffiliateProgram;
use App\Support\Confirm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $tab = $request->query('tab', 'applications');
        if (! in_array($tab, ['applications', 'pending', 'approved', 'active', 'rejected', 'all', 'performance', 'settings'], true)) {
            $tab = 'applications';
        }
        if ($tab === 'pending') {
            $tab = 'applications';
        }
        if ($tab === 'performance') {
            return redirect()->route('admin.insights.affiliate-performance');
        }
        // Source of truth: Settings Hub
        if ($tab === 'settings') {
            return redirect()->route('admin.settings', ['tab' => 'affiliates']);
        }

        $query = Affiliate::query()->latest();
        if ($tab === 'applications') {
            $query->where('status', 'pending');
        } elseif ($tab !== 'all') {
            $query->where('status', $tab);
        }

        return view('admin.affiliates.index', [
            'affiliates' => $query->paginate(\App\Support\AdminPagination::perPage($request))->withQueryString(),
            'tab' => $tab,
            'counts' => [
                'pending' => Affiliate::query()->where('status', 'pending')->count(),
                'approved' => Affiliate::query()->where('status', 'approved')->count(),
                'active' => Affiliate::query()->where('status', 'active')->count(),
                'rejected' => Affiliate::query()->where('status', 'rejected')->count(),
                'all' => Affiliate::query()->count(),
            ],
            'settings' => AffiliateProgram::settings(),
            'countries' => \App\Support\Countries::enabledOptions(),
        ]);
    }

    public function create(): View
    {
        return view('admin.affiliates.create', [
            'countries' => \App\Support\Countries::enabledOptions(),
        ]);
    }

    public function store(Request $request, AffiliateService $affiliates): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'country' => ['required', 'string', 'size:2'],
            'phone' => ['required', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:120'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $data['country_code'] = \App\Support\Countries::dial($data['country']);
        $data['phone'] = preg_replace('/\D+/', '', $data['phone']) ?: $data['phone'];

        $created = $affiliates->createFromAdmin($data, $request->user());
        $affiliate = $created['affiliate'];

        return redirect()->route('admin.affiliates.show', $affiliate)->with('confirm', Confirm::make(
            __('loop.affiliate_created_title'),
            __('loop.affiliate_created_body', ['code' => $affiliate->promo_code]),
            __('loop.done'),
            route('admin.affiliates.show', $affiliate),
            true,
        ));
    }

    public function show(Affiliate $affiliate): View
    {
        return view('admin.affiliates.show', [
            'affiliate' => $affiliate->load(['referrals.business', 'reviewer']),
            'settings' => AffiliateProgram::settings(),
        ]);
    }

    public function decide(Request $request, Affiliate $affiliate, AffiliateService $affiliates): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $affiliates->decide($affiliate, $request->user(), $data['decision'], $data['decision_note'] ?? null);

        return redirect()->route('admin.affiliates.show', $affiliate)->with('confirm', Confirm::make(
            $data['decision'] === 'approved' ? __('loop.affiliate_approved_title') : __('loop.affiliate_rejected_title'),
            $data['decision'] === 'approved'
                ? __('loop.affiliate_approved_body', ['code' => $affiliate->fresh()->promo_code])
                : __('loop.affiliate_rejected_body'),
            __('loop.done'),
            route('admin.affiliates.index'),
            $data['decision'] === 'approved',
        ));
    }

    /**
     * Legacy endpoint — affiliate program settings are edited only in the Settings Hub.
     */
    public function updateSettings(): RedirectResponse
    {
        return redirect()->route('admin.settings', ['tab' => 'affiliates']);
    }
}
