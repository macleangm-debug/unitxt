<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\PlatformSetting;
use App\Services\AffiliateService;
use App\Support\AffiliateProgram;
use App\Support\Confirm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'pending');
        if (! in_array($tab, ['pending', 'approved', 'active', 'rejected', 'all'], true)) {
            $tab = 'pending';
        }

        $query = Affiliate::query()->latest();
        if ($tab !== 'all') {
            $query->where('status', $tab === 'approved' ? 'approved' : $tab);
            if ($tab === 'approved') {
                // show approved awaiting activation too — already filtered
            }
        }

        return view('admin.affiliates.index', [
            'affiliates' => $query->paginate(20)->withQueryString(),
            'tab' => $tab,
            'counts' => [
                'pending' => Affiliate::query()->where('status', 'pending')->count(),
                'approved' => Affiliate::query()->where('status', 'approved')->count(),
                'active' => Affiliate::query()->where('status', 'active')->count(),
                'rejected' => Affiliate::query()->where('status', 'rejected')->count(),
                'all' => Affiliate::query()->count(),
            ],
            'settings' => AffiliateProgram::settings(),
        ]);
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

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'commission_percent' => ['required', 'integer', 'min:1', 'max:50'],
            'referred_discount_percent' => ['required', 'integer', 'min:0', 'max:50'],
            'attribution_months' => ['required', 'integer', 'min:1', 'max:36'],
            'pin_length' => ['required', 'integer', 'min:4', 'max:6'],
            'enabled' => ['sometimes', 'boolean'],
            'attribution_enabled' => ['sometimes', 'boolean'],
        ]);

        $normalized = AffiliateProgram::normalizeInput([
            ...$data,
            'enabled' => $request->boolean('enabled'),
            'attribution_enabled' => $request->boolean('attribution_enabled'),
        ]);

        PlatformSetting::putValue(AffiliateProgram::KEY, $normalized);

        return back()->with('confirm', Confirm::make(
            __('loop.affiliate_settings_saved_title'),
            __('loop.affiliate_settings_saved'),
            __('loop.done'),
            route('admin.affiliates.index'),
            false,
        ));
    }
}
