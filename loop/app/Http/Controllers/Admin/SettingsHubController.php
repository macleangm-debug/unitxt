<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Support\BillingSettings;
use App\Support\Confirm;
use App\Support\FeatureFlags;
use App\Support\GrowthSettings;
use App\Support\Plans;
use App\Support\PlatformUrl;
use App\Support\ReferralProgram;
use App\Support\SalesVisibility;
use App\Support\Sectors;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsHubController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'billing' => BillingSettings::settings(),
            'growth' => GrowthSettings::settings(),
            'referral' => ReferralProgram::settings(),
            'salesVisibility' => SalesVisibility::settings(),
            'platformUrl' => PlatformUrl::settings(),
            'featureFlags' => FeatureFlags::settings(),
            'featureCatalog' => FeatureFlags::catalog(),
            'sectors' => Sectors::list(),
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

        return back()->with('confirm', Confirm::make(
            __('loop.admin_billing_saved_title'),
            __('loop.admin_billing_saved'),
            __('loop.done'),
            route('admin.settings', ['tab' => 'billing']),
            false,
        ));
    }

    public function updateGrowth(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'raffle_min_members' => ['required', 'integer', 'min:10', 'max:5000'],
            'raffle_max_winners_percent' => ['required', 'integer', 'min:5', 'max:50'],
            'banner_member_milestones' => ['nullable', 'string', 'max:120'],
            'banner_max_count' => ['required', 'integer', 'min:1', 'max:5'],
            'campaign_delta_threshold_pct' => ['required', 'integer', 'min:5', 'max:100'],
            'retention_delta_threshold_pct' => ['required', 'integer', 'min:3', 'max:50'],
            'raffle_remind_days_before' => ['required', 'integer', 'min:1', 'max:14'],
            'raffle_default_claim_days' => ['required', 'integer', 'min:1', 'max:30'],
            'banner_show_campaign_up' => ['sometimes', 'boolean'],
            'banner_show_campaign_down' => ['sometimes', 'boolean'],
            'banner_show_retention_up' => ['sometimes', 'boolean'],
            'banner_show_retention_down' => ['sometimes', 'boolean'],
            'banner_show_raffle_unlock' => ['sometimes', 'boolean'],
            'banner_show_add_offers_cta' => ['sometimes', 'boolean'],
            'banner_show_member_milestones' => ['sometimes', 'boolean'],
            'onboarding_celebrate' => ['sometimes', 'boolean'],
        ]);

        $normalized = GrowthSettings::normalizeInput([
            ...$data,
            'banner_show_campaign_up' => $request->boolean('banner_show_campaign_up'),
            'banner_show_campaign_down' => $request->boolean('banner_show_campaign_down'),
            'banner_show_retention_up' => $request->boolean('banner_show_retention_up'),
            'banner_show_retention_down' => $request->boolean('banner_show_retention_down'),
            'banner_show_raffle_unlock' => $request->boolean('banner_show_raffle_unlock'),
            'banner_show_add_offers_cta' => $request->boolean('banner_show_add_offers_cta'),
            'banner_show_member_milestones' => $request->boolean('banner_show_member_milestones'),
            'onboarding_celebrate' => $request->boolean('onboarding_celebrate'),
        ]);

        PlatformSetting::putValue(GrowthSettings::KEY, $normalized);

        return back()->with('confirm', Confirm::make(
            __('loop.admin_growth_saved_title'),
            __('loop.admin_growth_saved'),
            __('loop.done'),
            route('admin.settings', ['tab' => 'growth']),
            false,
        ));
    }

    public function updateSectors(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sectors' => ['required', 'array', 'min:1'],
            'sectors.*.key' => ['required', 'string', 'max:40'],
            'sectors.*.label' => ['required', 'string', 'max:80'],
            'new_key' => ['nullable', 'string', 'max:40'],
            'new_label' => ['nullable', 'string', 'max:80'],
        ]);

        $rows = $data['sectors'];
        if (filled($data['new_key'] ?? null) && filled($data['new_label'] ?? null)) {
            $rows[] = [
                'key' => $data['new_key'],
                'label' => $data['new_label'],
            ];
        }

        PlatformSetting::putValue(Sectors::KEY, Sectors::normalizeInput($rows));

        return back()->with('confirm', Confirm::make(
            __('loop.admin_sectors_saved_title'),
            __('loop.admin_sectors_saved'),
            __('loop.done'),
            route('admin.settings', ['tab' => 'sectors']),
            false,
        ));
    }

    public function updateSalesVisibility(Request $request): RedirectResponse
    {
        $normalized = SalesVisibility::normalizeInput([
            'customers_see_sales' => $request->boolean('customers_see_sales'),
            'front_desk_see_sales' => $request->boolean('front_desk_see_sales'),
        ]);

        PlatformSetting::putValue(SalesVisibility::KEY, $normalized);

        return back()->with('confirm', Confirm::make(
            __('loop.admin_sales_visibility_saved_title'),
            __('loop.admin_sales_visibility_saved'),
            __('loop.done'),
            route('admin.settings', ['tab' => 'visibility']),
            false,
        ));
    }

    public function updatePlatformUrl(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'base_url' => ['required', 'string', 'max:255'],
        ]);

        PlatformSetting::putValue(PlatformUrl::KEY, PlatformUrl::normalizeInput($data));

        return back()->with('confirm', Confirm::make(
            __('loop.admin_base_url_saved_title'),
            __('loop.admin_base_url_saved'),
            __('loop.done'),
            route('admin.settings', ['tab' => 'platform']),
            false,
        ));
    }

    public function updateFeatureFlags(Request $request): RedirectResponse
    {
        $input = [];
        foreach (FeatureFlags::defaults() as $key => $_) {
            $input[$key] = $request->boolean($key);
        }

        PlatformSetting::putValue(FeatureFlags::KEY, FeatureFlags::normalizeInput($input));

        return back()->with('confirm', Confirm::make(
            __('loop.admin_features_saved_title'),
            __('loop.admin_features_saved'),
            __('loop.done'),
            route('admin.settings', ['tab' => 'product']),
            false,
        ));
    }
}
