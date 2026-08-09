@php
    $tab = request('tab', 'overview');
    $allowed = ['overview', 'packages', 'billing', 'growth', 'platform', 'sectors', 'visibility', 'product', 'links'];
    if (! in_array($tab, $allowed, true)) {
        $tab = 'overview';
    }
    $tabs = [
        'overview' => __('loop.settings_tab_overview'),
        'packages' => __('loop.settings_tab_packages'),
        'billing' => __('loop.settings_tab_billing'),
        'growth' => __('loop.settings_tab_growth'),
        'platform' => __('loop.settings_tab_platform'),
        'sectors' => __('loop.settings_tab_sectors'),
        'visibility' => __('loop.settings_tab_visibility'),
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
                    'platform' => [__('loop.settings_tab_platform'), __('loop.admin_base_url_blurb')],
                    'sectors' => [__('loop.settings_tab_sectors'), __('loop.admin_sectors_blurb')],
                    'visibility' => [__('loop.settings_tab_visibility'), __('loop.admin_sales_visibility_blurb')],
                    'product' => [__('loop.settings_tab_product'), __('loop.admin_product_updates_blurb')],
                    'links' => [__('loop.settings_tab_links'), __('loop.settings_tab_links_blurb')],
                ] as $key => [$title, $blurb])
                    <a href="{{ route('admin.settings', ['tab' => $key]) }}" class="rounded-2xl border border-ink/8 bg-white/70 p-4 transition hover:border-violet/30 hover:bg-white">
                        <p class="font-semibold">{{ $title }}</p>
                        <p class="mt-1 text-xs text-ink-muted">{{ $blurb }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($tab === 'packages')
        <section class="loop-glass p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.settings_tab_packages') }}</h2>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_packages_blurb') }}</p>
                </div>
                <a href="{{ route('admin.plans.index') }}" class="loop-btn-mint !py-2">{{ __('loop.view_plans') }}</a>
            </div>
            <div class="mt-5 loop-table-wrap">
                <table class="loop-table">
                    <thead>
                        <tr>
                            <th>{{ __('loop.plan') }}</th>
                            <th>{{ __('loop.price') }}</th>
                            <th>{{ __('loop.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($plans as $plan)
                            <tr>
                                <td>
                                    <p class="font-semibold">{{ $plan->name }}</p>
                                    <p class="text-xs text-ink-muted">{{ $plan->key }}</p>
                                </td>
                                <td>{{ $plan->priceLabel() }}</td>
                                <td>{{ $plan->is_public ? __('loop.live') : __('loop.off') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-ink-muted">{{ __('loop.no_data_yet') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($tab === 'billing')
        <form method="POST" action="{{ route('admin.settings.billing') }}" class="loop-glass space-y-5 p-6">
            @csrf
            @method('PUT')
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.billing_trial_settings') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.billing_trial_settings_blurb') }}</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="loop-label">{{ __('loop.trial_days') }}</label>
                    <input type="number" min="1" max="90" name="trial_days" value="{{ old('trial_days', $billing['trial_days']) }}" class="loop-input" required>
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
            <button class="loop-btn-mint">{{ __('loop.save') }}</button>
        </form>
    @endif

    @if ($tab === 'growth')
        <form method="POST" action="{{ route('admin.settings.growth') }}" class="loop-glass space-y-5 p-6">
            @csrf
            @method('PUT')
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.growth_banners_settings') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.growth_banners_settings_blurb') }}</p>
            </div>
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
            <div>
                <label class="loop-label">{{ __('loop.base_url_field') }}</label>
                <input type="url" name="base_url" value="{{ old('base_url', $platformUrl['base_url']) }}" class="loop-input" placeholder="https://loop.example.com" required>
                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.base_url_help') }}</p>
            </div>
            <button class="loop-btn-mint">{{ __('loop.save') }}</button>
        </form>
    @endif

    @if ($tab === 'sectors')
        <form method="POST" action="{{ route('admin.settings.sectors') }}" class="loop-glass space-y-5 p-6">
            @csrf
            @method('PUT')
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.admin_sectors') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.admin_sectors_blurb') }}</p>
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
            <div class="rounded-2xl bg-chalk/70 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ __('loop.add_sector') }}</p>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <input name="new_key" class="loop-input" placeholder="{{ __('loop.sector_key_placeholder') }}">
                    <input name="new_label" class="loop-input" placeholder="{{ __('loop.sector_label_placeholder') }}">
                </div>
            </div>
            <button class="loop-btn-mint">{{ __('loop.save') }}</button>
        </form>
    @endif

    @if ($tab === 'visibility')
        <form method="POST" action="{{ route('admin.settings.sales-visibility') }}" class="loop-glass space-y-5 p-6">
            @csrf
            @method('PUT')
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.admin_sales_visibility') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.admin_sales_visibility_blurb') }}</p>
            </div>
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
        </form>
    @endif

    @if ($tab === 'product')
        <form method="POST" action="{{ route('admin.settings.feature-flags') }}" class="loop-glass space-y-5 p-6">
            @csrf
            @method('PUT')
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.admin_product_updates') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.admin_product_updates_blurb') }}</p>
            </div>
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
        </form>
    @endif

    @if ($tab === 'links')
        <section class="loop-glass p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.settings_tab_links') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_links_blurb') }}</p>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <a href="{{ route('admin.affiliates.index', ['tab' => 'applications']) }}" class="rounded-2xl border border-ink/8 bg-white/70 p-4 transition hover:border-violet/30">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.admin_affiliates') }}</p>
                    <p class="mt-2 font-display text-lg font-semibold">{{ __('loop.configure_affiliates') }}</p>
                </a>
                <a href="{{ route('admin.referrals.index') }}" class="rounded-2xl border border-ink/8 bg-white/70 p-4 transition hover:border-violet/30">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.admin_referrals') }}</p>
                    <p class="mt-2 font-display text-lg font-semibold">{{ __('loop.referral_progress_title') }}</p>
                </a>
                <a href="{{ route('admin.referrals.program') }}" class="rounded-2xl border border-ink/8 bg-white/70 p-4 transition hover:border-violet/30">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.admin_referral_program') }}</p>
                    <p class="mt-2 font-display text-lg font-semibold">{{ __('loop.configure_referrals') }}</p>
                </a>
                <a href="{{ route('admin.plans.index') }}" class="rounded-2xl border border-ink/8 bg-white/70 p-4 transition hover:border-violet/30">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.admin_plans') }}</p>
                    <p class="mt-2 font-display text-lg font-semibold">{{ __('loop.view_plans') }}</p>
                </a>
                <div class="rounded-2xl border border-ink/8 bg-white/70 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.integrations') }}</p>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.integrations_blurb') }}</p>
                </div>
            </div>
        </section>
    @endif
</x-app-layout>
