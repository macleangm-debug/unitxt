<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Support\BillingSettings;
use App\Support\Plans;
use App\Support\ReferralProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsHubController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'billing' => BillingSettings::settings(),
            'referral' => ReferralProgram::settings(),
            'plans' => Plan::query()->orderBy('sort_order')->get(),
            'integrations' => [
                ['key' => 'mobile_money', 'name' => __('loop.integration_mobile_money'), 'status' => 'coming'],
                ['key' => 'sms', 'name' => __('loop.integration_sms'), 'status' => 'coming'],
                ['key' => 'whatsapp', 'name' => __('loop.integration_whatsapp'), 'status' => 'coming'],
            ],
        ]);
    }

    public function updateBilling(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'trial_days' => ['required', 'integer', 'min:1', 'max:90'],
            'free_max_shops' => ['required', 'integer', 'min:1', 'max:5'],
            'free_max_members' => ['required', 'integer', 'min:1', 'max:500'],
            'free_max_monthly_visits' => ['required', 'integer', 'min:1', 'max:500'],
            'block_till_when_trial_ends' => ['sometimes', 'boolean'],
        ]);

        $normalized = BillingSettings::normalizeInput([
            ...$data,
            'block_till_when_trial_ends' => $request->boolean('block_till_when_trial_ends'),
        ]);

        PlatformSetting::putValue(BillingSettings::KEY, $normalized);

        // Keep free/trial plan row in sync with admin caps
        Plan::query()->where('key', Plans::FREE)->update([
            'name' => 'Trial',
            'tagline' => 'Short trial — then pick a paid plan.',
            'max_shops' => $normalized['free_max_shops'],
            'max_members' => $normalized['free_max_members'],
            'max_monthly_visits' => $normalized['free_max_monthly_visits'],
            'features' => [
                '1 physical shop + address',
                "Up to {$normalized['free_max_members']} members",
                "Up to {$normalized['free_max_monthly_visits']} sales / month",
                "{$normalized['trial_days']}-day trial then upgrade",
            ],
        ]);

        return back()->with('status', __('loop.admin_billing_saved'));
    }
}
