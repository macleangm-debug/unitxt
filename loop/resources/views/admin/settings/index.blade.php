@php
    $tab = request('tab', 'overview');
    $allowed = ['overview', 'packages', 'billing', 'growth', 'marketing', 'platform', 'sectors', 'countries', 'visibility', 'referrals', 'affiliates', 'notifications', 'product', 'links'];
    if (! in_array($tab, $allowed, true)) {
        $tab = 'overview';
    }
    $tabs = [
        'overview' => __('loop.settings_tab_overview'),
        'packages' => __('loop.settings_tab_packages'),
        'billing' => __('loop.settings_tab_billing'),
        'growth' => __('loop.settings_tab_growth'),
        'marketing' => __('loop.settings_tab_marketing'),
        'platform' => __('loop.settings_tab_platform'),
        'sectors' => __('loop.settings_tab_sectors'),
        'countries' => __('loop.settings_tab_countries'),
        'visibility' => __('loop.settings_tab_visibility'),
        'referrals' => __('loop.settings_tab_referrals'),
        'affiliates' => __('loop.settings_tab_affiliates'),
        'notifications' => __('loop.settings_tab_notifications'),
        'product' => __('loop.settings_tab_product'),
        'links' => __('loop.settings_tab_links'),
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.source_of_truth') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.admin_settings_hub') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.admin_settings_hub_blurb') }}</p>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.settings_hub_and_integrations') }}</p>
        </div>
    </x-slot>

    @include('admin.partials.nav')

    <div class="loop-admin-tabs" role="tablist">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.settings', ['tab' => $key]) }}"
               class="loop-admin-tab {{ $tab === $key ? 'is-active' : '' }}"
               role="tab"
               aria-selected="{{ $tab === $key ? 'true' : 'false' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($tab === 'overview')
        <section class="loop-glass p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.settings_hub_quick') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_hub_quick_blurb') }}</p>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    'packages' => [__('loop.settings_tab_packages'), __('loop.settings_tab_packages_blurb')],
                    'billing' => [__('loop.settings_tab_billing'), __('loop.billing_trial_settings_blurb')],
                    'growth' => [__('loop.settings_tab_growth'), __('loop.growth_banners_settings_blurb')],
                    'marketing' => [__('loop.settings_tab_marketing'), __('loop.settings_tab_marketing_blurb')],
                    'platform' => [__('loop.settings_tab_platform'), __('loop.admin_base_url_blurb')],
                    'sectors' => [__('loop.settings_tab_sectors'), __('loop.admin_sectors_blurb')],
                    'countries' => [__('loop.settings_tab_countries'), __('loop.settings_tab_countries_blurb')],
                    'visibility' => [__('loop.settings_tab_visibility'), __('loop.admin_sales_visibility_blurb')],
                    'referrals' => [__('loop.settings_tab_referrals'), __('loop.settings_tab_referrals_blurb')],
                    'affiliates' => [__('loop.settings_tab_affiliates'), __('loop.settings_tab_affiliates_blurb')],
                    'notifications' => [__('loop.settings_tab_notifications'), __('loop.settings_tab_notifications_blurb')],
                    'product' => [__('loop.settings_tab_product'), __('loop.admin_product_updates_blurb')],
                    'links' => [__('loop.settings_tab_links'), __('loop.settings_tab_links_blurb')],
                ] as $key => [$title, $blurb])
                    <a href="{{ route('admin.settings', ['tab' => $key]) }}" class="rounded-2xl border border-ink/8 bg-white/70 p-4 transition hover:border-violet/30 hover:bg-white">
                        <p class="font-semibold">{{ $title }}</p>
                        <p class="mt-1 text-xs text-ink-muted">{{ $blurb }}</p>
                    </a>
                @endforeach
                <a href="{{ route('admin.integrations.index') }}" class="rounded-2xl border border-ink/8 bg-white/70 p-4 transition hover:border-violet/30 hover:bg-white">
                    <p class="font-semibold">{{ __('loop.integrations_hub') }}</p>
                    <p class="mt-1 text-xs text-ink-muted">{{ __('loop.integrations_hub_blurb') }}</p>
                </a>
            </div>
        </section>
    @endif

    @if ($tab === 'packages')
        <section class="space-y-5">
            <div class="loop-glass p-6">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.settings_tab_packages') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_packages_edit_blurb') }}</p>
            </div>
            @forelse ($plans as $plan)
                <form method="POST" action="{{ route('admin.settings.plans.update', $plan) }}" class="loop-glass space-y-4 p-6">
                    @csrf
                    @method('PUT')
                    <x-admin.settings-lock>
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ $plan->key }}</p>
                                <h3 class="mt-1 font-display text-xl font-semibold">{{ $plan->name }}</h3>
                            </div>
                            <label class="flex items-center gap-2 text-sm font-semibold">
                                <input type="checkbox" name="is_public" value="1" class="rounded border-ink/20 text-mint focus:ring-mint" @checked(old('is_public', $plan->is_public))>
                                {{ __('loop.show_on_pricing') }}
                            </label>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="loop-label">{{ __('loop.plan_name') }}</label>
                                <input name="name" value="{{ old('name', $plan->name) }}" class="loop-input" required>
                            </div>
                            <div>
                                <label class="loop-label">{{ __('loop.price_monthly') }}</label>
                                <input type="number" min="0" name="price_monthly" value="{{ old('price_monthly', $plan->price_monthly) }}" class="loop-input" required>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="loop-label">{{ __('loop.tagline') }}</label>
                                <input name="tagline" value="{{ old('tagline', $plan->tagline) }}" class="loop-input">
                            </div>
                            <div>
                                <label class="loop-label">{{ __('loop.currency') }}</label>
                                <input name="currency" value="{{ old('currency', $plan->currency) }}" maxlength="3" class="loop-input uppercase" required>
                            </div>
                            <div>
                                <label class="loop-label">{{ __('loop.sort_order') }}</label>
                                <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $plan->sort_order) }}" class="loop-input" required>
                            </div>
                            <div>
                                <label class="loop-label">{{ __('loop.max_shops') }}</label>
                                <input type="number" min="1" name="max_shops" value="{{ old('max_shops', $plan->max_shops) }}" class="loop-input" placeholder="{{ __('loop.unlimited') }}">
                            </div>
                            <div>
                                <label class="loop-label">{{ __('loop.max_members') }}</label>
                                <input type="number" min="1" name="max_members" value="{{ old('max_members', $plan->max_members) }}" class="loop-input" placeholder="{{ __('loop.unlimited') }}">
                            </div>
                            <div>
                                <label class="loop-label">{{ __('loop.max_monthly_visits') }}</label>
                                <input type="number" min="1" name="max_monthly_visits" value="{{ old('max_monthly_visits', $plan->max_monthly_visits) }}" class="loop-input" placeholder="{{ __('loop.unlimited') }}">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="loop-label">{{ __('loop.plan_features') }}</label>
                                <textarea name="features_text" rows="4" class="loop-input" placeholder="{{ __('loop.plan_features_help') }}">{{ old('features_text', implode("\n", $plan->features ?? [])) }}</textarea>
                                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.plan_features_help') }}</p>
                            </div>
                        </div>
                        <button class="loop-btn-mint">{{ __('loop.save_package') }}</button>
                    </x-admin.settings-lock>
                </form>
            @empty
                <x-admin.empty-state :empty="true" :title="__('loop.settings_tab_packages')" />
            @endforelse
        </section>
    @endif

    @if ($tab === 'billing')
        <div class="loop-glass p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.billing_trial_settings') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.billing_trial_settings_blurb') }}</p>
            <p class="mt-2 text-xs text-ink-muted">{{ __('loop.billing_front_sync_hint') }}</p>
            <form method="POST" action="{{ route('admin.settings.billing') }}" class="mt-4">
                @csrf
                @method('PUT')
                <x-admin.settings-lock>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="loop-label">{{ __('loop.trial_days') }}</label>
                            <input type="number" min="1" max="90" name="trial_days" value="{{ old('trial_days', $billing['trial_days']) }}" class="loop-input" required>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.grace_days') }}</label>
                            <input type="number" min="0" max="30" name="grace_days" value="{{ old('grace_days', $billing['grace_days']) }}" class="loop-input" required>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.grace_days_help') }}</p>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.free_max_shops') }}</label>
                            <input type="number" min="1" max="5" name="free_max_shops" value="{{ old('free_max_shops', $billing['free_max_shops']) }}" class="loop-input" required>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.free_max_members') }}</label>
                            <input type="number" min="1" name="free_max_members" value="{{ old('free_max_members', $billing['free_max_members']) }}" class="loop-input" required>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.free_max_monthly_visits') }}</label>
                            <input type="number" min="1" name="free_max_monthly_visits" value="{{ old('free_max_monthly_visits', $billing['free_max_monthly_visits']) }}" class="loop-input" required>
                        </div>
                    </div>
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="block_till_when_trial_ends" value="1" class="mt-1 rounded border-ink/20 text-mint focus:ring-mint" @checked(old('block_till_when_trial_ends', $billing['block_till_when_trial_ends']))>
                        <span>
                            <span class="font-semibold">{{ __('loop.block_till_when_trial_ends') }}</span>
                            <span class="mt-1 block text-ink-muted">{{ __('loop.block_till_when_trial_ends_help') }}</span>
                        </span>
                    </label>
                    <label class="mt-3 flex items-start gap-3 text-sm">
                        <input type="checkbox" name="hide_from_discover_when_unpaid" value="1" class="mt-1 rounded border-ink/20 text-mint focus:ring-mint" @checked(old('hide_from_discover_when_unpaid', $billing['hide_from_discover_when_unpaid'] ?? true))>
                        <span>
                            <span class="font-semibold">{{ __('loop.hide_from_discover_when_unpaid') }}</span>
                            <span class="mt-1 block text-ink-muted">{{ __('loop.hide_from_discover_when_unpaid_help') }}</span>
                        </span>
                    </label>
                    <button class="loop-btn-mint">{{ __('loop.save') }}</button>
                </x-admin.settings-lock>
            </form>
        </div>
    @endif

    @if ($tab === 'growth')
        <form method="POST" action="{{ route('admin.settings.growth') }}" class="loop-glass space-y-5 p-6">
            @csrf
            @method('PUT')
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.growth_banners_settings') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.growth_banners_settings_blurb') }}</p>
            </div>
            <x-admin.settings-lock>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.admin_section_raffles') }}</p>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="loop-label">{{ __('loop.raffle_min_members') }}</label>
                            <input type="number" min="10" name="raffle_min_members" value="{{ old('raffle_min_members', $growth['raffle_min_members']) }}" class="loop-input" required>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.raffle_min_members_help') }}</p>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.raffle_max_winners_percent') }}</label>
                            <input type="number" min="5" max="50" name="raffle_max_winners_percent" value="{{ old('raffle_max_winners_percent', $growth['raffle_max_winners_percent']) }}" class="loop-input" required>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.raffle_max_winners_percent_help') }}</p>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.raffle_remind_days_before') }}</label>
                            <input type="number" min="1" max="14" name="raffle_remind_days_before" value="{{ old('raffle_remind_days_before', $growth['raffle_remind_days_before']) }}" class="loop-input" required>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.raffle_default_claim_days') }}</label>
                            <input type="number" min="1" max="30" name="raffle_default_claim_days" value="{{ old('raffle_default_claim_days', $growth['raffle_default_claim_days']) }}" class="loop-input" required>
                        </div>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.admin_section_banners') }}</p>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="loop-label">{{ __('loop.banner_member_milestones') }}</label>
                            <input name="banner_member_milestones" value="{{ old('banner_member_milestones', implode(',', $growth['banner_member_milestones'])) }}" class="loop-input" placeholder="10,25,50,100">
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.banner_member_milestones_help') }}</p>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.banner_max_count') }}</label>
                            <input type="number" min="1" max="5" name="banner_max_count" value="{{ old('banner_max_count', $growth['banner_max_count']) }}" class="loop-input" required>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.banner_max_count_help') }}</p>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.campaign_delta_threshold_pct') }}</label>
                            <input type="number" min="5" max="100" name="campaign_delta_threshold_pct" value="{{ old('campaign_delta_threshold_pct', $growth['campaign_delta_threshold_pct']) }}" class="loop-input" required>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.campaign_delta_threshold_pct_help') }}</p>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.retention_delta_threshold_pct') }}</label>
                            <input type="number" min="3" max="50" name="retention_delta_threshold_pct" value="{{ old('retention_delta_threshold_pct', $growth['retention_delta_threshold_pct']) }}" class="loop-input" required>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.retention_delta_threshold_pct_help') }}</p>
                        </div>
                    </div>
                </div>
                <div class="space-y-2 text-sm">
                    @foreach ([
                        'banner_show_campaign_up' => __('loop.banner_show_campaign_up'),
                        'banner_show_campaign_down' => __('loop.banner_show_campaign_down'),
                        'banner_show_retention_up' => __('loop.banner_show_retention_up'),
                        'banner_show_retention_down' => __('loop.banner_show_retention_down'),
                        'banner_show_raffle_unlock' => __('loop.banner_show_raffle_unlock'),
                        'banner_show_add_offers_cta' => __('loop.banner_show_add_offers_cta'),
                        'banner_show_member_milestones' => __('loop.banner_show_member_milestones'),
                        'onboarding_celebrate' => __('loop.onboarding_celebrate'),
                    ] as $key => $label)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $growth[$key]))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <button class="loop-btn-mint">{{ __('loop.save') }}</button>
            </x-admin.settings-lock>
        </form>
    @endif

    @if ($tab === 'platform')
        <form method="POST" action="{{ route('admin.settings.base-url') }}" class="loop-glass space-y-5 p-6">
            @csrf
            @method('PUT')
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.admin_base_url') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.admin_base_url_blurb') }}</p>
            </div>
            <x-admin.settings-lock>
                <div>
                    <label class="loop-label">{{ __('loop.base_url_field') }}</label>
                    <input type="url" name="base_url" value="{{ old('base_url', $platformUrl['base_url']) }}" class="loop-input" placeholder="https://loop.example.com" required>
                    <p class="mt-1 text-xs text-ink-muted">{{ __('loop.base_url_help') }}</p>
                </div>
                <button class="loop-btn-mint">{{ __('loop.save') }}</button>
            </x-admin.settings-lock>
        </form>
    @endif

    @if ($tab === 'sectors')
        <div class="loop-glass p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.admin_sectors') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.admin_sectors_blurb') }}</p>
            <form method="POST" action="{{ route('admin.settings.sectors') }}" class="mt-4">
                @csrf
                @method('PUT')
                <x-admin.settings-lock>
                    <div class="rounded-2xl bg-chalk/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ __('loop.add_sector') }}</p>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            <input name="new_key" class="loop-input" placeholder="{{ __('loop.sector_key_placeholder') }}">
                            <input name="new_label" class="loop-input" placeholder="{{ __('loop.sector_label_placeholder') }}">
                        </div>
                    </div>
                    <div class="space-y-3">
                        @foreach ($sectors as $i => $sector)
                            <div class="grid gap-2 sm:grid-cols-[140px_1fr]">
                                <input type="hidden" name="sectors[{{ $i }}][key]" value="{{ $sector['key'] }}">
                                <input value="{{ $sector['key'] }}" class="loop-input !bg-chalk text-sm" disabled>
                                <input name="sectors[{{ $i }}][label]" value="{{ old('sectors.'.$i.'.label', $sector['label']) }}" class="loop-input" required>
                            </div>
                        @endforeach
                    </div>
                    <button class="loop-btn-mint">{{ __('loop.save') }}</button>
                </x-admin.settings-lock>
            </form>
        </div>
    @endif

    @if ($tab === 'countries')
        <div class="loop-glass p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.settings_tab_countries') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_countries_blurb') }}</p>
            <form method="POST" action="{{ route('admin.settings.countries') }}" class="mt-4">
                @csrf
                @method('PUT')
                <x-admin.settings-lock>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($countryCatalog as $code => $meta)
                            <label class="flex items-center gap-3 rounded-2xl border border-ink/10 px-4 py-3 text-sm">
                                <input type="checkbox" name="enabled[]" value="{{ $code }}" @checked(in_array($code, $countries['enabled'], true))>
                                <span>{{ $meta['flag'] }} <span class="font-semibold">{{ $meta['name'] }}</span> · {{ $meta['currency'] }} · {{ $meta['dial'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    <button class="loop-btn-mint">{{ __('loop.save') }}</button>
                </x-admin.settings-lock>
            </form>
        </div>
    @endif

    @if ($tab === 'marketing')
        <div class="loop-glass p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.settings_tab_marketing') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_marketing_blurb') }}</p>
            <form method="POST" action="{{ route('admin.settings.marketing') }}" class="mt-4">
                @csrf
                @method('PUT')
                <x-admin.settings-lock>
                    <div>
                        <label class="loop-label">{{ __('loop.hero_tagline_override') }}</label>
                        <input name="hero_tagline_override" value="{{ old('hero_tagline_override', $marketing['hero_tagline_override']) }}" class="loop-input" placeholder="{{ __('loop.tagline') }}">
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.pricing_blurb_override') }}</label>
                        <textarea name="pricing_blurb_override" rows="3" class="loop-input">{{ old('pricing_blurb_override', $marketing['pricing_blurb_override']) }}</textarea>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.launch_banner_text') }}</label>
                        <input name="launch_banner_text" value="{{ old('launch_banner_text', $marketing['launch_banner_text']) }}" class="loop-input">
                    </div>
                    <div class="flex flex-wrap gap-4 text-sm">
                        <label class="flex items-center gap-2"><input type="checkbox" name="show_affiliate_cta" value="1" @checked(old('show_affiliate_cta', $marketing['show_affiliate_cta']))> {{ __('loop.show_affiliate_cta') }}</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="show_referral_cta" value="1" @checked(old('show_referral_cta', $marketing['show_referral_cta']))> {{ __('loop.show_referral_cta') }}</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="launch_banner_enabled" value="1" @checked(old('launch_banner_enabled', $marketing['launch_banner_enabled']))> {{ __('loop.launch_banner_enabled') }}</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="holiday_message_enabled" value="1" @checked(old('holiday_message_enabled', $marketing['holiday_message_enabled']))> {{ __('loop.holiday_message_enabled') }}</label>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.holiday_message_text') }}</label>
                        <textarea name="holiday_message_text" rows="2" class="loop-input">{{ old('holiday_message_text', $marketing['holiday_message_text']) }}</textarea>
                    </div>
                    <button class="loop-btn-mint">{{ __('loop.save') }}</button>
                </x-admin.settings-lock>
            </form>
        </div>
    @endif

    @if ($tab === 'notifications')
        <div class="loop-glass p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.settings_tab_notifications') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_notifications_blurb') }}</p>
            <form method="POST" action="{{ route('admin.settings.notifications') }}" class="mt-6">
                @csrf
                @method('PUT')
                <x-admin.settings-lock>
                    <div class="overflow-x-auto rounded-2xl border border-ink/10">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-chalk/80 text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-muted">
                                <tr>
                                    <th class="px-4 py-3">{{ __('loop.notif_matrix_role') }}</th>
                                    <th class="px-4 py-3">{{ __('loop.notif_channel_in_app') }}</th>
                                    <th class="px-4 py-3">{{ __('loop.notif_channel_sms') }}</th>
                                    <th class="px-4 py-3">{{ __('loop.notif_channel_email') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink/8 bg-white">
                                <tr>
                                    <td class="px-4 py-3.5">
                                        <p class="font-semibold text-ink">{{ __('loop.notif_audience_member') }}</p>
                                        <p class="mt-0.5 text-xs text-ink-muted">{{ __('loop.notif_audience_member_help') }}</p>
                                    </td>
                                    <td class="px-4 py-3.5"><label class="inline-flex items-center gap-2"><input type="checkbox" name="customer_in_app" value="1" @checked(old('customer_in_app', $notifications['customer_in_app']))> <span class="sr-only">{{ __('loop.notif_channel_in_app') }}</span></label></td>
                                    <td class="px-4 py-3.5"><label class="inline-flex items-center gap-2"><input type="checkbox" name="customer_sms" value="1" @checked(old('customer_sms', $notifications['customer_sms']))> <span class="sr-only">{{ __('loop.notif_channel_sms') }}</span></label></td>
                                    <td class="px-4 py-3.5 text-xs text-ink-muted">—</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3.5">
                                        <p class="font-semibold text-ink">{{ __('loop.notif_audience_business') }}</p>
                                        <p class="mt-0.5 text-xs text-ink-muted">{{ __('loop.notif_audience_business_help') }}</p>
                                    </td>
                                    <td class="px-4 py-3.5"><label class="inline-flex items-center gap-2"><input type="checkbox" name="owner_in_app" value="1" @checked(old('owner_in_app', $notifications['owner_in_app']))> <span class="sr-only">{{ __('loop.notif_channel_in_app') }}</span></label></td>
                                    <td class="px-4 py-3.5"><label class="inline-flex items-center gap-2"><input type="checkbox" name="owner_sms" value="1" @checked(old('owner_sms', $notifications['owner_sms']))> <span class="sr-only">{{ __('loop.notif_channel_sms') }}</span></label></td>
                                    <td class="px-4 py-3.5"><label class="inline-flex items-center gap-2"><input type="checkbox" name="owner_email" value="1" @checked(old('owner_email', $notifications['owner_email']))> <span class="sr-only">{{ __('loop.notif_channel_email') }}</span></label></td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3.5">
                                        <p class="font-semibold text-ink">{{ __('loop.notif_audience_affiliate') }}</p>
                                        <p class="mt-0.5 text-xs text-ink-muted">{{ __('loop.notif_audience_affiliate_help') }}</p>
                                    </td>
                                    <td class="px-4 py-3.5"><label class="inline-flex items-center gap-2"><input type="checkbox" name="affiliate_in_app" value="1" @checked(old('affiliate_in_app', $notifications['affiliate_in_app']))> <span class="sr-only">{{ __('loop.notif_channel_in_app') }}</span></label></td>
                                    <td class="px-4 py-3.5"><label class="inline-flex items-center gap-2"><input type="checkbox" name="affiliate_sms" value="1" @checked(old('affiliate_sms', $notifications['affiliate_sms']))> <span class="sr-only">{{ __('loop.notif_channel_sms') }}</span></label></td>
                                    <td class="px-4 py-3.5 text-xs text-ink-muted">—</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.notif_platform_rules') }}</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            @foreach ([
                                'in_app_digest' => __('loop.in_app_digest'),
                                'holiday_messages' => __('loop.holiday_messages'),
                                'trial_reminders' => __('loop.trial_reminders'),
                                'admin_digest' => __('loop.notif_admin_digest'),
                            ] as $key => $label)
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $notifications[$key]))> {{ $label }}</label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="loop-label">{{ __('loop.quiet_hours_start') }}</label>
                            <input type="number" min="0" max="23" name="quiet_hours_start" value="{{ old('quiet_hours_start', $notifications['quiet_hours_start']) }}" class="loop-input">
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.quiet_hours_end') }}</label>
                            <input type="number" min="0" max="23" name="quiet_hours_end" value="{{ old('quiet_hours_end', $notifications['quiet_hours_end']) }}" class="loop-input">
                        </div>
                    </div>
                    <button class="loop-btn-mint mt-6">{{ __('loop.save') }}</button>
                </x-admin.settings-lock>
            </form>
        </div>
    @endif

    @if ($tab === 'visibility')
        <form method="POST" action="{{ route('admin.settings.sales-visibility') }}" class="loop-glass space-y-5 p-6">
            @csrf
            @method('PUT')
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.admin_sales_visibility') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.admin_sales_visibility_blurb') }}</p>
            </div>
            <x-admin.settings-lock>
                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="customers_see_sales" value="1" class="mt-1 rounded border-ink/20 text-mint focus:ring-mint" @checked(old('customers_see_sales', $salesVisibility['customers_see_sales']))>
                    <span>
                        <span class="font-semibold">{{ __('loop.customers_see_sales') }}</span>
                        <span class="mt-1 block text-ink-muted">{{ __('loop.customers_see_sales_help') }}</span>
                    </span>
                </label>
                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="front_desk_see_sales" value="1" class="mt-1 rounded border-ink/20 text-mint focus:ring-mint" @checked(old('front_desk_see_sales', $salesVisibility['front_desk_see_sales']))>
                    <span>
                        <span class="font-semibold">{{ __('loop.front_desk_see_sales') }}</span>
                        <span class="mt-1 block text-ink-muted">{{ __('loop.front_desk_see_sales_help') }}</span>
                    </span>
                </label>
                <button class="loop-btn-mint">{{ __('loop.save') }}</button>
            </x-admin.settings-lock>
        </form>
    @endif

    @if ($tab === 'referrals')
        <form method="POST" action="{{ route('admin.settings.referrals') }}" class="loop-glass space-y-5 p-6">
            @csrf
            @method('PUT')
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.admin_referral_program') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_referrals_blurb') }}</p>
            </div>
            <div class="rounded-2xl bg-mint-soft/50 p-4 text-sm text-ink">
                <p class="font-semibold">{{ __('loop.admin_referral_both_sides') }}</p>
                <p class="mt-1 text-ink-muted">{{ __('loop.admin_referral_both_sides_body') }}</p>
            </div>
            <x-admin.settings-lock>
                <div class="space-y-5" x-data="{
                    model: @js(old('incentive_model', $referral['incentive_model'] ?? 'standard')),
                    models: @js(\App\Support\ReferralProgram::models()),
                    apply() {
                        if (this.model === 'custom') return;
                        const m = this.models[this.model];
                        if (!m) return;
                        this.$refs.goal.value = m.goal_count;
                        this.$refs.you.value = m.referrer_extra_days_per_referral;
                        this.$refs.they.value = m.referred_extra_trial_days;
                    }
                }">
                    <div>
                        <label class="loop-label">{{ __('loop.incentive_model') }}</label>
                        <select name="incentive_model" class="loop-input" x-model="model" @change="apply()">
                            @foreach (\App\Support\ReferralProgram::models() as $key => $model)
                                <option value="{{ $key }}">{{ $model['label'] }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-ink-muted">{{ __('loop.incentive_model_help') }}</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="loop-label">{{ __('loop.goal_count') }}</label>
                            <input type="number" min="1" name="goal_count" x-ref="goal" value="{{ old('goal_count', $referral['goal_count']) }}" class="loop-input" required>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.goal_count_help') }}</p>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.referrer_extra_days') }}</label>
                            <input type="number" min="0" name="referrer_extra_days_per_referral" x-ref="you" value="{{ old('referrer_extra_days_per_referral', $referral['referrer_extra_days_per_referral']) }}" class="loop-input" required>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.referrer_extra_days_help') }}</p>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.referred_extra_trial_days') }}</label>
                            <input type="number" min="0" name="referred_extra_trial_days" x-ref="they" value="{{ old('referred_extra_trial_days', $referral['referred_extra_trial_days']) }}" class="loop-input" required>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.referred_extra_trial_days_help') }}</p>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.referrer_discount_percent') }}</label>
                            <input type="number" min="0" max="100" name="referrer_discount_percent" value="{{ old('referrer_discount_percent', $referral['referrer_discount_percent']) }}" class="loop-input" required>
                        </div>
                    </div>
                    <input type="hidden" name="referrer_months_per_referral" value="0">
                    <input type="hidden" name="referred_bonus_months" value="0">
                    <div class="flex flex-wrap gap-3">
                        <button class="loop-btn-mint">{{ __('loop.save') }}</button>
                        <a href="{{ route('admin.referrals.index') }}" class="loop-btn-ghost !py-2.5">{{ __('loop.referral_progress_title') }} →</a>
                    </div>
                </div>
            </x-admin.settings-lock>
        </form>
    @endif

    @if ($tab === 'affiliates')
        <div class="loop-glass p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.affiliate_program_settings') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_affiliates_blurb') }}</p>
            <p class="mt-2 rounded-2xl bg-violet-soft/50 px-4 py-3 text-sm text-ink">{{ __('loop.affiliate_kpi_governed') }} — {{ __('loop.monthly_paying_target') }}: {{ $affiliate['monthly_paying_business_target'] }}</p>
            <form method="POST" action="{{ route('admin.settings.affiliates') }}" class="mt-4">
                @csrf
                @method('PUT')
                <x-admin.settings-lock>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="loop-label">{{ __('loop.commission_percent') }}</label>
                            <input type="number" min="1" max="50" name="commission_percent" value="{{ old('commission_percent', $affiliate['commission_percent']) }}" class="loop-input" required>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.referred_discount_percent') }}</label>
                            <input type="number" min="0" max="50" name="referred_discount_percent" value="{{ old('referred_discount_percent', $affiliate['referred_discount_percent']) }}" class="loop-input" required>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.attribution_months') }}</label>
                            <input type="number" min="1" max="36" name="attribution_months" value="{{ old('attribution_months', $affiliate['attribution_months']) }}" class="loop-input" required>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.cookie_days') }}</label>
                            <input type="number" min="1" max="365" name="cookie_days" value="{{ old('cookie_days', $affiliate['cookie_days']) }}" class="loop-input" required>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.pin_length') }}</label>
                            <input type="number" min="4" max="6" name="pin_length" value="{{ old('pin_length', $affiliate['pin_length']) }}" class="loop-input" required>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.monthly_paying_target') }}</label>
                            <input type="number" min="1" max="100" name="monthly_paying_business_target" value="{{ old('monthly_paying_business_target', $affiliate['monthly_paying_business_target']) }}" class="loop-input" required>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.min_payout_amount') }}</label>
                            <input type="number" min="0" name="min_payout_amount" value="{{ old('min_payout_amount', $affiliate['min_payout_amount']) }}" class="loop-input" required>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.payout_schedule') }}</label>
                            <select name="payout_schedule" class="loop-input">
                                @foreach (['weekly', 'biweekly', 'monthly'] as $sched)
                                    <option value="{{ $sched }}" @selected(old('payout_schedule', $affiliate['payout_schedule']) === $sched)>{{ __("loop.payout_$sched") }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.tax_withholding_percent') }}</label>
                            <input type="number" min="0" max="40" name="tax_withholding_percent" value="{{ old('tax_withholding_percent', $affiliate['tax_withholding_percent']) }}" class="loop-input">
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.fraud_hold_days') }}</label>
                            <input type="number" min="0" max="90" name="fraud_hold_days" value="{{ old('fraud_hold_days', $affiliate['fraud_hold_days']) }}" class="loop-input">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="loop-label">{{ __('loop.terms_url') }}</label>
                            <input type="url" name="terms_url" value="{{ old('terms_url', $affiliate['terms_url']) }}" class="loop-input">
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-4 text-sm">
                        <label class="flex items-center gap-2"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $affiliate['enabled']))> {{ __('loop.affiliate_program_enabled') }}</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="attribution_enabled" value="1" @checked(old('attribution_enabled', $affiliate['attribution_enabled']))> {{ __('loop.attribution_enabled') }}</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="kpi_enabled" value="1" @checked(old('kpi_enabled', $affiliate['kpi_enabled']))> {{ __('loop.kpi_enabled') }}</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="show_kpis_to_affiliates" value="1" @checked(old('show_kpis_to_affiliates', $affiliate['show_kpis_to_affiliates']))> {{ __('loop.show_kpis_to_affiliates') }}</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="block_self_referral" value="1" @checked(old('block_self_referral', $affiliate['block_self_referral']))> {{ __('loop.block_self_referral') }}</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="require_tax_id" value="1" @checked(old('require_tax_id', $affiliate['require_tax_id']))> {{ __('loop.require_tax_id') }}</label>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button class="loop-btn-mint">{{ __('loop.save') }}</button>
                        <a href="{{ route('admin.insights.affiliate-performance') }}" class="loop-btn-ghost !py-2.5">{{ __('loop.affiliate_performance') }} →</a>
                        <a href="{{ route('admin.affiliates.index', ['tab' => 'applications']) }}" class="loop-btn-ghost !py-2.5">{{ __('loop.affiliate_applications_queue') }} →</a>
                    </div>
                </x-admin.settings-lock>
            </form>
        </div>
    @endif

    @if ($tab === 'product')
        <form method="POST" action="{{ route('admin.settings.feature-flags') }}" class="loop-glass space-y-5 p-6">
            @csrf
            @method('PUT')
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.admin_product_updates') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.admin_product_updates_blurb') }}</p>
                <p class="mt-3 rounded-2xl bg-chalk/70 px-4 py-3 text-sm text-ink-muted">{{ __('loop.admin_product_updates_rollout') }}</p>
            </div>
            <x-admin.settings-lock>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($featureCatalog as $feature)
                        @php $key = $feature['key']; @endphp
                        <label class="flex items-start gap-3 rounded-2xl border border-ink/10 px-4 py-3 text-sm has-[:checked]:border-mint has-[:checked]:bg-mint-soft/30">
                            <input type="checkbox" name="{{ $key }}" value="1" class="mt-1 rounded border-ink/20 text-mint-deep focus:ring-mint-deep" @checked(old($key, $featureFlags[$key] ?? false))>
                            <span>
                                <span class="block font-semibold">{{ __('loop.feature_'.$key.'_title') }}</span>
                                <span class="mt-1 block text-xs text-ink-muted">{{ __('loop.feature_'.$key.'_body') }}</span>
                                <span class="mt-2 inline-block rounded-lg bg-chalk px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-ink-muted">{{ __('loop.feature_cat_'.$feature['category']) }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <button class="loop-btn-mint">{{ __('loop.save_product_updates') }}</button>
            </x-admin.settings-lock>
        </form>
    @endif

    @if ($tab === 'links')
        <section class="loop-glass p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.settings_tab_links') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_links_blurb') }}</p>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <a href="{{ route('admin.settings', ['tab' => 'packages']) }}" class="rounded-2xl border border-ink/8 bg-white/70 p-4 transition hover:border-violet/30">
                    <p class="font-display text-lg font-semibold">{{ __('loop.edit_plan_catalog') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_packages') }}</p>
                </a>
                <a href="{{ route('admin.settings', ['tab' => 'affiliates']) }}" class="rounded-2xl border border-ink/8 bg-white/70 p-4 transition hover:border-violet/30">
                    <p class="font-display text-lg font-semibold">{{ __('loop.configure_affiliates') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_affiliates') }}</p>
                </a>
                <a href="{{ route('admin.settings', ['tab' => 'referrals']) }}" class="rounded-2xl border border-ink/8 bg-white/70 p-4 transition hover:border-violet/30">
                    <p class="font-display text-lg font-semibold">{{ __('loop.configure_referrals') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_referrals') }}</p>
                </a>
                <a href="{{ route('admin.integrations.index') }}" class="rounded-2xl border border-ink/8 bg-white/70 p-4 transition hover:border-violet/30">
                    <p class="font-display text-lg font-semibold">{{ __('loop.integrations_hub') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.integrations_hub_blurb') }}</p>
                </a>
                <a href="{{ route('admin.affiliates.index', ['tab' => 'applications']) }}" class="rounded-2xl border border-ink/8 bg-white/70 p-4 transition hover:border-violet/30">
                    <p class="font-display text-lg font-semibold">{{ __('loop.affiliate_applications_queue') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.admin_affiliates') }}</p>
                </a>
                <a href="{{ route('admin.referrals.index') }}" class="rounded-2xl border border-ink/8 bg-white/70 p-4 transition hover:border-violet/30">
                    <p class="font-display text-lg font-semibold">{{ __('loop.referral_progress_title') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.admin_referrals') }}</p>
                </a>
                <a href="{{ route('admin.plans.index') }}" class="rounded-2xl border border-ink/8 bg-white/70 p-4 transition hover:border-violet/30">
                    <p class="font-display text-lg font-semibold">{{ __('loop.view_plans') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.admin_plans') }}</p>
                </a>
            </div>
        </section>
    @endif
</x-app-layout>
