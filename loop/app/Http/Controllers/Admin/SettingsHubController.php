<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Support\AffiliateProgram;
use App\Support\BillingSettings;
use App\Support\Confirm;
use App\Support\CountrySettings;
use App\Support\FeatureFlags;
use App\Support\GrowthSettings;
use App\Support\MarketingSettings;
use App\Support\NotificationSettings;
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
            'affiliate' => AffiliateProgram::settings(),
            'salesVisibility' => SalesVisibility::settings(),
            'platformUrl' => PlatformUrl::settings(),
            'featureFlags' => FeatureFlags::settings(),
            'featureCatalog' => FeatureFlags::catalog(),
            'sectors' => Sectors::list(),
            'plans' => Plan::query()->orderBy('sort_order')->get(),
            'countries' => CountrySettings::settings(),
            'notifications' => NotificationSettings::settings(),
            'marketing' => MarketingSettings::settings(),
            'countryCatalog' => \App\Support\Countries::OPTIONS,
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

    public function updateReferrals(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'goal_count' => ['required', 'integer', 'min:1', 'max:50'],
            'referrer_extra_days_per_referral' => ['required', 'integer', 'min:0', 'max:90'],
            'referred_extra_trial_days' => ['required', 'integer', 'min:0', 'max:180'],
            'referrer_discount_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'referrer_months_per_referral' => ['nullable', 'integer', 'min:0', 'max:12'],
            'referred_bonus_months' => ['nullable', 'integer', 'min:0', 'max:12'],
        ]);

        PlatformSetting::putValue(ReferralProgram::KEY, ReferralProgram::normalizeInput([
            ...$data,
            'referrer_months_per_referral' => $data['referrer_months_per_referral'] ?? 0,
            'referred_bonus_months' => $data['referred_bonus_months'] ?? 0,
        ]));

        return redirect()->route('admin.settings', ['tab' => 'referrals'])->with('confirm', Confirm::make(
            __('loop.admin_referral_program_saved_title'),
            __('loop.admin_referral_program_saved'),
            __('loop.done'),
            route('admin.settings', ['tab' => 'referrals']),
            false,
        ));
    }

    public function updateAffiliates(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'commission_percent' => ['required', 'integer', 'min:1', 'max:50'],
            'referred_discount_percent' => ['required', 'integer', 'min:0', 'max:50'],
            'attribution_months' => ['required', 'integer', 'min:1', 'max:36'],
            'cookie_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'pin_length' => ['required', 'integer', 'min:4', 'max:6'],
            'monthly_paying_business_target' => ['nullable', 'integer', 'min:1', 'max:100'],
            'min_payout_amount' => ['nullable', 'integer', 'min:0'],
            'payout_schedule' => ['nullable', 'in:weekly,biweekly,monthly'],
            'tax_withholding_percent' => ['nullable', 'integer', 'min:0', 'max:40'],
            'fraud_hold_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'terms_url' => ['nullable', 'string', 'max:255'],
            'enabled' => ['sometimes', 'boolean'],
            'attribution_enabled' => ['sometimes', 'boolean'],
            'kpi_enabled' => ['sometimes', 'boolean'],
            'show_kpis_to_affiliates' => ['sometimes', 'boolean'],
            'block_self_referral' => ['sometimes', 'boolean'],
            'require_tax_id' => ['sometimes', 'boolean'],
        ]);

        PlatformSetting::putValue(AffiliateProgram::KEY, AffiliateProgram::normalizeInput([
            ...$data,
            'enabled' => $request->boolean('enabled'),
            'attribution_enabled' => $request->boolean('attribution_enabled'),
            'kpi_enabled' => $request->boolean('kpi_enabled'),
            'show_kpis_to_affiliates' => $request->boolean('show_kpis_to_affiliates'),
            'block_self_referral' => $request->boolean('block_self_referral'),
            'require_tax_id' => $request->boolean('require_tax_id'),
        ]));

        return redirect()->route('admin.settings', ['tab' => 'affiliates'])->with('confirm', Confirm::make(
            __('loop.affiliate_settings_saved_title'),
            __('loop.affiliate_settings_saved'),
            __('loop.done'),
            route('admin.settings', ['tab' => 'affiliates']),
            false,
        ));
    }

    public function updateCountries(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'array'],
            'enabled.*' => ['string', 'size:2'],
        ]);

        PlatformSetting::putValue(CountrySettings::KEY, CountrySettings::normalizeInput($data));

        return redirect()->route('admin.settings', ['tab' => 'countries'])->with('confirm', Confirm::make(
            __('loop.countries_saved_title'),
            __('loop.countries_saved'),
            __('loop.done'),
            route('admin.settings', ['tab' => 'countries']),
            false,
        ));
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        PlatformSetting::putValue(NotificationSettings::KEY, NotificationSettings::normalizeInput($request->all()));

        return redirect()->route('admin.settings', ['tab' => 'notifications'])->with('confirm', Confirm::make(
            __('loop.notifications_saved_title'),
            __('loop.notifications_saved'),
            __('loop.done'),
            route('admin.settings', ['tab' => 'notifications']),
            false,
        ));
    }

    public function updatePlan(Request $request, Plan $plan): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'price_monthly' => ['required', 'integer', 'min:0', 'max:100000000'],
            'currency' => ['required', 'string', 'size:3'],
            'max_shops' => ['nullable', 'integer', 'min:1', 'max:500'],
            'max_members' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'max_monthly_visits' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100'],
            'is_public' => ['sometimes', 'boolean'],
            'features_text' => ['nullable', 'string', 'max:4000'],
        ]);

        $features = collect(preg_split('/\r\n|\r|\n/', (string) ($data['features_text'] ?? '')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $plan->update([
            'name' => $data['name'],
            'tagline' => $data['tagline'] ?? '',
            'price_monthly' => $data['price_monthly'],
            'currency' => strtoupper($data['currency']),
            'max_shops' => $data['max_shops'] ?? null,
            'max_members' => $data['max_members'] ?? null,
            'max_monthly_visits' => $data['max_monthly_visits'] ?? null,
            'sort_order' => $data['sort_order'],
            'is_public' => $request->boolean('is_public'),
            'features' => $features,
        ]);

        return redirect()
            ->route('admin.settings', ['tab' => 'packages'])
            ->with('confirm', Confirm::make(
                __('loop.admin_plan_saved_title'),
                __('loop.admin_plan_saved', ['name' => $plan->name]),
                __('loop.done'),
                route('admin.settings', ['tab' => 'packages']),
                false,
            ));
    }

    public function updateMarketing(Request $request): RedirectResponse
    {
        PlatformSetting::putValue(MarketingSettings::KEY, MarketingSettings::normalizeInput($request->all()));

        return redirect()->route('admin.settings', ['tab' => 'marketing'])->with('confirm', Confirm::make(
            __('loop.marketing_saved_title'),
            __('loop.marketing_saved'),
            __('loop.done'),
            route('admin.settings', ['tab' => 'marketing']),
            false,
        ));
    }
}
