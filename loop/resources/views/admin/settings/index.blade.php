@php
    $tab = request('tab', 'overview');
    $allowed = ['overview', 'packages', 'billing', 'growth', 'games', 'marketing', 'platform', 'sectors', 'countries', 'language', 'visibility', 'referrals', 'affiliates', 'notifications', 'product', 'health', 'legal', 'links'];
    if (! in_array($tab, $allowed, true)) {
        $tab = 'overview';
    }
    $tabs = [
        'overview' => __('loop.settings_tab_overview'),
        'packages' => __('loop.settings_tab_packages'),
        'billing' => __('loop.settings_tab_billing'),
        'growth' => __('loop.settings_tab_growth'),
        'games' => __('loop.settings_tab_games'),
        'marketing' => __('loop.settings_tab_marketing'),
        'platform' => __('loop.settings_tab_platform'),
        'sectors' => __('loop.settings_tab_sectors'),
        'countries' => __('loop.settings_tab_countries'),
        'language' => __('loop.settings_tab_language'),
        'visibility' => __('loop.settings_tab_visibility'),
        'referrals' => __('loop.settings_tab_referrals'),
        'affiliates' => __('loop.settings_tab_affiliates'),
        'notifications' => __('loop.settings_tab_notifications'),
        'product' => __('loop.settings_tab_product'),
        'health' => __('loop.settings_tab_health'),
        'legal' => __('loop.settings_tab_legal'),
        'links' => __('loop.settings_tab_links'),
    ];
@endphp

<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('loop.source_of_truth') }}</p>
            <h1 class="mt-1">{{ __('loop.admin_settings_hub') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('loop.admin_settings_hub_blurb') }}</p>
        </div>
    </x-slot>

    <div class="admin-subnav" role="tablist">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.settings', ['tab' => $key]) }}"
               class="{{ $tab === $key ? 'is-active' : '' }}"
               role="tab"
               aria-selected="{{ $tab === $key ? 'true' : 'false' }}">{{ $label }}</a>
        @endforeach
        <a href="{{ route('admin.integrations.index') }}">{{ __('loop.integrations_hub') }}</a>
        <a href="{{ route('admin.errors.index') }}">{{ __('loop.admin_errors') }}</a>
    </div>

    @if ($tab === 'overview')
        <section class="admin-card">
            <h2 class="text-lg font-semibold">{{ __('loop.settings_snapshot_title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('loop.settings_snapshot_blurb') }}</p>
            <dl class="admin-dl mt-5">
                @foreach ($settingsSummary as $label => $copy)
                    <dt>{{ $label }}</dt>
                    <dd>{{ $copy }}</dd>
                @endforeach
            </dl>
        </section>
        <section class="admin-card mt-5">
            <h2 class="text-lg font-semibold">{{ __('loop.settings_hub_quick') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('loop.settings_hub_quick_blurb') }}</p>
            <div class="admin-table-wrap mt-4">
                <table class="admin-table">
                    <tbody>
                        @foreach ([
                            'packages' => [__('loop.settings_tab_packages'), __('loop.settings_tab_packages_blurb')],
                            'billing' => [__('loop.settings_tab_billing'), __('loop.billing_trial_settings_blurb')],
                            'growth' => [__('loop.settings_tab_growth'), __('loop.growth_banners_settings_blurb')],
                            'games' => [__('loop.settings_tab_games'), __('loop.settings_tab_games_blurb')],
                            'marketing' => [__('loop.settings_tab_marketing'), __('loop.settings_tab_marketing_blurb')],
                            'platform' => [__('loop.settings_tab_platform'), __('loop.admin_base_url_blurb')],
                            'sectors' => [__('loop.settings_tab_sectors'), __('loop.admin_sectors_blurb')],
                            'countries' => [__('loop.settings_tab_countries'), __('loop.settings_tab_countries_blurb')],
                            'language' => [__('loop.settings_tab_language'), __('loop.settings_tab_language_blurb')],
                            'visibility' => [__('loop.settings_tab_visibility'), __('loop.admin_sales_visibility_blurb')],
                            'referrals' => [__('loop.settings_tab_referrals'), __('loop.settings_tab_referrals_blurb')],
                            'affiliates' => [__('loop.settings_tab_affiliates'), __('loop.settings_tab_affiliates_blurb')],
                            'notifications' => [__('loop.settings_tab_notifications'), __('loop.settings_tab_notifications_blurb')],
                            'product' => [__('loop.settings_tab_product'), __('loop.admin_product_updates_blurb')],
                            'health' => [__('loop.settings_tab_health'), __('loop.settings_tab_health_blurb')],
                            'links' => [__('loop.settings_tab_links'), __('loop.settings_tab_links_blurb')],
                        ] as $key => [$title, $blurb])
                            <tr>
                                <td>
                                    <p class="font-semibold">{{ $title }}</p>
                                    <p class="text-xs text-slate-500">{{ $blurb }}</p>
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <a href="{{ route('admin.settings', ['tab' => $key]) }}" class="admin-link">{{ __('loop.view') }}</a>
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td>
                                <p class="font-semibold">{{ __('loop.integrations_hub') }}</p>
                                <p class="text-xs text-slate-500">{{ __('loop.integrations_hub_blurb') }}</p>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.integrations.index') }}" class="admin-link">{{ __('loop.view') }}</a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p class="font-semibold">{{ __('loop.admin_errors') }}</p>
                                <p class="text-xs text-slate-500">{{ __('loop.admin_errors_blurb') }}</p>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.errors.index') }}" class="admin-link">{{ __('loop.view') }}</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($tab === 'packages')
        <section class="space-y-5">
            <div class="admin-card">
                <h2 class="text-lg font-semibold">{{ __('loop.settings_tab_packages') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('loop.settings_tab_packages_edit_blurb') }}</p>
                <x-admin.used-by :items="[__('loop.used_by_packages')]" />
                <form method="GET" class="mt-4 flex flex-wrap items-end gap-3">
                    <input type="hidden" name="tab" value="packages">
                    <div>
                        <x-sheet-select
                            name="country"
                            :label="__('loop.country')"
                            :options="collect($countryCatalog)->mapWithKeys(fn ($meta, $code) => [$code => ($meta['flag'].' '.$meta['name'])])->all()"
                            :value="$planCountry"
                            :autosubmit="true"
                        />
                    </div>
                </form>
                <form method="POST" action="{{ route('admin.settings.plans.clone-country') }}" class="mt-4 flex flex-wrap items-end gap-3 border-t border-slate-200 pt-4">
                    @csrf
                    <input type="hidden" name="from" value="{{ $planCountry }}">
                    <div>
                        <x-sheet-select
                            name="to"
                            :label="__('loop.add_country_packages')"
                            :options="collect($countryCatalog)->reject(fn ($meta, $code) => $code === $planCountry)->mapWithKeys(fn ($meta, $code) => [$code => ($meta['flag'].' '.$meta['name'])])->all()"
                            :value="collect($countryCatalog)->keys()->first(fn ($code) => $code !== $planCountry)"
                        />
                    </div>
                    <button class="admin-btn">{{ __('loop.copy_packages_to_country') }}</button>
                    <p class="w-full text-xs text-slate-500">{{ __('loop.copy_packages_hint') }}</p>
                </form>
            </div>
            @forelse ($plans as $plan)
                <form method="POST" action="{{ route('admin.settings.plans.update', $plan) }}" class="admin-card space-y-4">
                    @csrf
                    @method('PUT')
                    <x-admin.settings-lock>
                        <x-slot:summary>
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $plan->key }} · {{ $plan->country }}</p>
                                    <h3 class="mt-1 text-lg font-semibold">{{ $plan->name }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">{{ $plan->tagline }}</p>
                                </div>
                                <p class="text-lg font-semibold">{{ $plan->priceLabel() }}</p>
                            </div>
                            <dl class="admin-dl mt-4">
                                <dt>{{ __('loop.max_shops') }}</dt>
                                <dd>{{ $plan->max_shops ?? __('loop.unlimited') }}</dd>
                                <dt>{{ __('loop.max_members') }}</dt>
                                <dd>{{ $plan->max_members ?? __('loop.unlimited') }}</dd>
                                <dt>{{ __('loop.max_offers') }}</dt>
                                <dd>{{ $plan->max_offers ?? __('loop.unlimited') }}</dd>
                                <dt>{{ __('loop.plan_includes_sms') }}</dt>
                                <dd>{{ $plan->has_sms ? __('loop.on') : __('loop.off') }}</dd>
                                <dt>{{ __('loop.plan_includes_raffles') }}</dt>
                                <dd>{{ $plan->has_raffles ? __('loop.on') : __('loop.off') }}</dd>
                                <dt>{{ __('loop.plan_includes_games') }}</dt>
                                <dd>{{ $plan->has_games ? __('loop.on') : __('loop.off') }}</dd>
                                <dt>{{ __('loop.show_on_pricing') }}</dt>
                                <dd>{{ $plan->is_public ? __('loop.on') : __('loop.off') }}</dd>
                            </dl>
                        </x-slot>
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
                            <div>
                                <label class="loop-label">{{ __('loop.max_product_pushes') }}</label>
                                <input type="number" min="0" name="max_product_pushes" value="{{ old('max_product_pushes', $plan->max_product_pushes) }}" class="loop-input" placeholder="{{ __('loop.unlimited') }}">
                            </div>
                            <div>
                                <label class="loop-label">{{ __('loop.max_offers') }}</label>
                                <input type="number" min="1" name="max_offers" value="{{ old('max_offers', $plan->max_offers) }}" class="loop-input" placeholder="{{ __('loop.unlimited') }}">
                            </div>
                            <label class="flex items-center gap-2 text-sm font-semibold">
                                <input type="checkbox" name="has_raffles" value="1" class="rounded border-ink/20 text-mint focus:ring-mint" @checked(old('has_raffles', $plan->has_raffles))>
                                {{ __('loop.plan_includes_raffles') }}
                            </label>
                            <label class="flex items-center gap-2 text-sm font-semibold">
                                <input type="checkbox" name="has_sms" value="1" class="rounded border-ink/20 text-mint focus:ring-mint" @checked(old('has_sms', $plan->has_sms))>
                                {{ __('loop.plan_includes_sms') }}
                            </label>
                            <label class="flex items-center gap-2 text-sm font-semibold">
                                <input type="checkbox" name="has_games" value="1" class="rounded border-ink/20 text-mint focus:ring-mint" @checked(old('has_games', $plan->has_games))>
                                {{ __('loop.plan_includes_games') }}
                            </label>
                            <div class="sm:col-span-2">
                                <label class="loop-label">{{ __('loop.plan_features') }}</label>
                                <textarea name="features_text" rows="4" class="loop-input" placeholder="{{ __('loop.plan_features_help') }}">{{ old('features_text', implode("\n", $plan->features ?? [])) }}</textarea>
                                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.plan_features_help') }}</p>
                            </div>
                        </div>
                        <button class="admin-btn">{{ __('loop.save_package') }}</button>
                    </x-admin.settings-lock>
                </form>
            @empty
                <x-admin.empty-state :empty="true" :title="__('loop.settings_tab_packages')" />
            @endforelse
        </section>
    @endif

    @if ($tab === 'billing')
        <div class="admin-card">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.billing_trial_settings') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.billing_trial_settings_blurb') }}</p>
            <x-admin.used-by :items="[__('loop.used_by_billing')]" />
            <p class="mt-2 text-xs text-ink-muted">{{ __('loop.billing_front_sync_hint') }}</p>
            <form method="POST" action="{{ route('admin.settings.billing') }}" class="mt-4">
                @csrf
                @method('PUT')
                <x-admin.settings-lock>
                    <x-slot:summary>
                        <dl class="admin-dl">
                            <dt>{{ __('loop.trial_days') }}</dt>
                            <dd>{{ $billing['trial_days'] }}</dd>
                            <dt>{{ __('loop.grace_days') }}</dt>
                            <dd>{{ $billing['grace_days'] }}</dd>
                            <dt>{{ __('loop.free_max_shops') }}</dt>
                            <dd>{{ $billing['free_max_shops'] }}</dd>
                            <dt>{{ __('loop.free_max_members') }}</dt>
                            <dd>{{ $billing['free_max_members'] }}</dd>
                            <dt>{{ __('loop.free_max_monthly_visits') }}</dt>
                            <dd>{{ $billing['free_max_monthly_visits'] }}</dd>
                            <dt>{{ __('loop.free_max_offers') }}</dt>
                            <dd>{{ $billing['free_max_offers'] }}</dd>
                            <dt>{{ __('loop.block_till_when_trial_ends') }}</dt>
                            <dd>{{ $billing['block_till_when_trial_ends'] ? __('loop.on') : __('loop.off') }}</dd>
                        </dl>
                    </x-slot>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="loop-label">{{ __('loop.trial_days') }}</label>
                            <input type="number" min="1" max="90" name="trial_days" value="{{ old('trial_days', $billing['trial_days']) }}" class="loop-input" required>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.grace_days') }}</label>
                            <input type="number" min="0" max="30" name="grace_days" value="{{ old('grace_days', $billing['grace_days'] ?? 7) }}" class="loop-input" required>
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
                        <div>
                            <label class="loop-label">{{ __('loop.free_max_product_pushes') }}</label>
                            <input type="number" min="0" name="free_max_product_pushes" value="{{ old('free_max_product_pushes', $billing['free_max_product_pushes']) }}" class="loop-input" required>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.free_max_offers') }}</label>
                            <input type="number" min="1" name="free_max_offers" value="{{ old('free_max_offers', $billing['free_max_offers']) }}" class="loop-input" required>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.free_max_offers_help') }}</p>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.discount_3_months') }}</label>
                            <input type="number" min="0" max="80" name="discount_months_3" value="{{ old('discount_months_3', $billing['discount_months_3'] ?? 8) }}" class="loop-input" required>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.discount_3_months_help') }}</p>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.discount_6_months') }}</label>
                            <input type="number" min="0" max="80" name="discount_months_6" value="{{ old('discount_months_6', $billing['discount_months_6'] ?? 15) }}" class="loop-input" required>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.discount_6_months_help') }}</p>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.discount_12_months') }}</label>
                            <input type="number" min="0" max="80" name="discount_months_12" value="{{ old('discount_months_12', $billing['discount_months_12'] ?? 25) }}" class="loop-input" required>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.discount_12_months_help') }}</p>
                        </div>
                    </div>
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="block_till_when_trial_ends" value="1" class="mt-1 rounded border-ink/20 text-mint focus:ring-mint" @checked(old('block_till_when_trial_ends', $billing['block_till_when_trial_ends']))>
                        <span>
                            <span class="font-semibold">{{ __('loop.block_till_when_trial_ends') }}</span>
                            <span class="mt-1 block text-ink-muted">{{ __('loop.block_till_when_trial_ends_help') }}</span>
                        </span>
                    </label>
                    <button class="admin-btn">{{ __('loop.save') }}</button>
                </x-admin.settings-lock>
            </form>
        </div>
    @endif

    @if ($tab === 'growth')
        <form method="POST" action="{{ route('admin.settings.growth') }}" class="admin-card space-y-5">
            @csrf
            @method('PUT')
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.growth_banners_settings') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.growth_banners_settings_blurb') }}</p>
            </div>
            <x-admin.settings-lock>
                <x-slot:summary>
                    <dl class="admin-dl">
                        <dt>{{ __('loop.raffle_min_members') }}</dt>
                        <dd>{{ $growth['raffle_min_members'] }}</dd>
                        <dt>{{ __('loop.raffle_max_winners_percent') }}</dt>
                        <dd>{{ $growth['raffle_max_winners_percent'] }}%</dd>
                        <dt>{{ __('loop.banner_max_count') }}</dt>
                        <dd>{{ $growth['banner_max_count'] }}</dd>
                        <dt>{{ __('loop.banner_member_milestones') }}</dt>
                        <dd>{{ implode(', ', $growth['banner_member_milestones'] ?? []) }}</dd>
                        <dt>{{ __('loop.onboarding_celebrate') }}</dt>
                        <dd>{{ ! empty($growth['onboarding_celebrate']) ? __('loop.on') : __('loop.off') }}</dd>
                    </dl>
                </x-slot>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.admin_section_raffles') }}</p>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="loop-label">{{ __('loop.raffle_min_members') }}</label>
                            <input type="number" min="10" name="raffle_min_members" value="{{ old('raffle_min_members', $growth['raffle_min_members']) }}" class="loop-input" required>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.raffle_min_members_help') }}</p>
                            <x-admin.used-by :items="[__('loop.used_by_raffle_min')]" />
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
                        <div>
                            <label class="loop-label">{{ __('loop.raffle_spin_seconds') }}</label>
                            <input type="number" min="45" max="60" name="raffle_spin_seconds" value="{{ old('raffle_spin_seconds', $growth['raffle_spin_seconds']) }}" class="loop-input" required>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.raffle_spin_seconds_help') }}</p>
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
                <button class="admin-btn">{{ __('loop.save') }}</button>
            </x-admin.settings-lock>
        </form>
    @endif

    @if ($tab === 'games')
        @php $gs = $gameSettings ?? \App\Support\GameSettings::settings(); @endphp
        <form method="POST" action="{{ route('admin.settings.games') }}" class="admin-card space-y-5">
            @csrf
            @method('PUT')
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.settings_tab_games') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_games_blurb') }}</p>
            </div>
            <x-admin.settings-lock>
                <label class="flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" name="enabled" value="1" class="rounded border-ink/20 text-mint focus:ring-mint" @checked(old('enabled', $gs['enabled']))>
                    {{ __('loop.game_settings_enabled') }}
                </label>
                <div>
                    <p class="loop-label">{{ __('loop.game_settings_types') }}</p>
                    <div class="mt-2 flex flex-wrap gap-3 text-sm">
                        @foreach (\App\Support\GameSettings::TYPES as $type)
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="types[]" value="{{ $type }}" @checked(in_array($type, old('types', $gs['types']), true))>
                                {{ __('loop.game_type_'.$type) }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="loop-label">{{ __('loop.game_settings_qualify') }}</label>
                        <select name="default_qualify" class="loop-input">
                            @foreach (\App\Support\GameSettings::QUALIFY as $mode)
                                <option value="{{ $mode }}" @selected(old('default_qualify', $gs['default_qualify']) === $mode)>{{ __('loop.game_qualify_'.$mode) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.game_spend_multiplier') }}</label>
                        <input type="number" min="1" max="4" step="0.1" name="spend_multiplier" value="{{ old('spend_multiplier', $gs['spend_multiplier']) }}" class="loop-input" required>
                        <p class="mt-1 text-xs text-ink-muted">{{ __('loop.game_spend_multiplier_help') }}</p>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.game_recommended_visits') }}</label>
                        <input type="number" min="2" max="20" name="recommended_visit_threshold" value="{{ old('recommended_visit_threshold', $gs['recommended_visit_threshold']) }}" class="loop-input" required>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.game_recommended_win_rate') }}</label>
                        <input type="number" min="5" max="50" name="recommended_win_rate" value="{{ old('recommended_win_rate', $gs['recommended_win_rate']) }}" class="loop-input" required>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.game_max_win_rate') }}</label>
                        <input type="number" min="10" max="80" name="max_win_rate" value="{{ old('max_win_rate', $gs['max_win_rate']) }}" class="loop-input" required>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.game_default_frequency') }}</label>
                        <select name="default_play_frequency" class="loop-input">
                            @foreach (\App\Support\GameSettings::FREQUENCIES as $freq)
                                <option value="{{ $freq }}" @selected(old('default_play_frequency', $gs['default_play_frequency']) === $freq)>{{ __('loop.game_limit_'.$freq) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.game_max_duration') }}</label>
                        <input type="number" min="1" max="365" name="max_duration_days" value="{{ old('max_duration_days', $gs['max_duration_days']) }}" class="loop-input" required>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.game_claim_days') }}</label>
                        <input type="number" min="1" max="30" name="claim_days" value="{{ old('claim_days', $gs['claim_days']) }}" class="loop-input" required>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.game_expected_plays') }}</label>
                        <input type="number" min="20" max="20000" name="expected_plays" value="{{ old('expected_plays', $gs['expected_plays']) }}" class="loop-input" required>
                    </div>
                </div>
                <div>
                    <p class="loop-label">{{ __('loop.game_prize_kinds') }}</p>
                    <div class="mt-2 flex flex-wrap gap-3 text-sm">
                        @foreach (\App\Support\GameSettings::PRIZE_KINDS as $kind)
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="allowed_prize_kinds[]" value="{{ $kind }}" @checked(in_array($kind, old('allowed_prize_kinds', $gs['allowed_prize_kinds']), true))>
                                {{ __('loop.game_prize_kind_'.$kind) }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <button class="admin-btn">{{ __('loop.save') }}</button>
            </x-admin.settings-lock>
        </form>
    @endif

    @if ($tab === 'platform')
        <form method="POST" action="{{ route('admin.settings.base-url') }}" class="admin-card space-y-5">
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
                    <x-admin.used-by :items="[__('loop.used_by_platform_url')]" />
                </div>
                <button class="admin-btn">{{ __('loop.save') }}</button>
            </x-admin.settings-lock>
        </form>
    @endif

    @if ($tab === 'sectors')
        <div class="admin-card">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.admin_sectors') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.admin_sectors_blurb') }}</p>
            <x-admin.used-by :items="[__('loop.used_by_sectors')]" />

            <div class="mt-6 rounded-2xl border border-ink/10 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ __('loop.sector_search_misses') }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.sector_search_misses_blurb') }}</p>
                <div class="mt-3 space-y-2">
                    @forelse ($sectorMisses ?? [] as $miss)
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <p class="font-semibold">{{ $miss->query }}</p>
                            <p class="tabular-nums text-ink-muted">{{ $miss->hits }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted">{{ __('loop.sector_search_misses_empty') }}</p>
                    @endforelse
                </div>
            </div>

            <form method="POST" action="{{ route('admin.settings.sectors') }}" class="mt-4">
                @csrf
                @method('PUT')
                <x-admin.settings-lock>
                    <div class="rounded-2xl bg-chalk/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ __('loop.add_sector') }}</p>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            <input name="new_key" class="loop-input" placeholder="{{ __('loop.sector_key_placeholder') }}">
                            <input name="new_label" class="loop-input" placeholder="{{ __('loop.sector_label_placeholder') }}">
                            <select name="new_category" class="loop-input">
                                @foreach ($sectorCategories ?? \App\Support\Sectors::CATEGORIES as $catKey => $catLabel)
                                    <option value="{{ $catKey }}">{{ $catLabel }}</option>
                                @endforeach
                            </select>
                            <input name="new_aliases" class="loop-input" placeholder="{{ __('loop.sector_aliases_placeholder') }}">
                        </div>
                    </div>
                    @php
                        $groupedSectors = collect($sectors)->groupBy(fn ($row) => $row['category'] ?? 'other');
                    @endphp
                    <div class="space-y-6">
                        @foreach ($groupedSectors as $catKey => $rows)
                            <div>
                                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.12em] text-violet">{{ \App\Support\Sectors::categoryLabel($catKey) }}</p>
                                <div class="space-y-3">
                                    @foreach ($rows as $sector)
                                        @php $i = collect($sectors)->search(fn ($row) => $row['key'] === $sector['key']); @endphp
                                        <div class="rounded-2xl border border-ink/10 p-3">
                                            <input type="hidden" name="sectors[{{ $i }}][key]" value="{{ $sector['key'] }}">
                                            <input type="hidden" name="sectors[{{ $i }}][featured]" value="0">
                                            <div class="grid gap-2 sm:grid-cols-[140px_1fr_8rem]">
                                                <input value="{{ $sector['key'] }}" class="loop-input !bg-chalk text-sm" disabled>
                                                <input name="sectors[{{ $i }}][label]" value="{{ old('sectors.'.$i.'.label', $sector['label']) }}" class="loop-input" required>
                                                <select name="sectors[{{ $i }}][category]" class="loop-input">
                                                    @foreach ($sectorCategories ?? \App\Support\Sectors::CATEGORIES as $optionKey => $optionLabel)
                                                        <option value="{{ $optionKey }}" @selected(($sector['category'] ?? 'other') === $optionKey)>{{ $optionLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                                <input name="sectors[{{ $i }}][aliases]" value="{{ old('sectors.'.$i.'.aliases', $sector['aliases'] ?? '') }}" class="loop-input min-w-[12rem] flex-1" placeholder="{{ __('loop.sector_aliases_placeholder') }}">
                                                <label class="flex items-center gap-2 text-sm font-semibold">
                                                    <input type="checkbox" name="sectors[{{ $i }}][featured]" value="1" @checked(! empty($sector['featured']))>
                                                    {{ __('loop.sector_featured') }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button class="admin-btn">{{ __('loop.save') }}</button>
                </x-admin.settings-lock>
            </form>
        </div>
    @endif

    @if ($tab === 'countries')
        <div class="admin-card">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.settings_tab_countries') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_countries_blurb') }}</p>
            <x-admin.used-by :items="[__('loop.used_by_countries')]" />
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
                    <button class="admin-btn">{{ __('loop.save') }}</button>
                </x-admin.settings-lock>
            </form>
        </div>
    @endif

    @if ($tab === 'language')
        <div class="admin-card">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.settings_tab_language') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_language_blurb') }}</p>
            <x-admin.used-by :items="[__('loop.used_by_language')]" />

            <div class="mt-5 rounded-2xl border border-ink/10 bg-chalk/40 px-4 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ __('loop.console_language') }}</p>
                <p class="mt-2 text-sm text-ink">{{ __('loop.console_language_help') }}</p>
                <div class="mt-3">
                    <x-admin.locale-switch />
                </div>
                <p class="mt-3 text-sm text-ink-muted">
                    {{ __('loop.console_language_now', ['lang' => app()->getLocale() === 'sw' ? __('loop.lang_swahili') : __('loop.lang_english')]) }}
                </p>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-ink/10 px-4 py-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">EN</p>
                    <p class="mt-1 font-semibold">{{ __('loop.lang_english') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.lang_english_help') }}</p>
                </div>
                <div class="rounded-2xl border border-ink/10 px-4 py-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">SW</p>
                    <p class="mt-1 font-semibold">{{ __('loop.lang_swahili') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.lang_swahili_help') }}</p>
                </div>
            </div>

            <div class="mt-5 space-y-3 text-sm text-ink">
                <p class="font-semibold">{{ __('loop.language_where_title') }}</p>
                <ul class="list-disc space-y-2 pl-5 text-ink-muted">
                    <li>{{ __('loop.language_where_apps') }}</li>
                    <li>{{ __('loop.language_where_default') }}</li>
                    <li>{{ __('loop.language_where_articles') }}</li>
                </ul>
            </div>
            <a href="{{ route('admin.articles.index') }}" class="admin-btn-ghost mt-5 inline-flex">{{ __('loop.language_edit_articles') }}</a>
        </div>
    @endif

    @if ($tab === 'marketing')
        <div class="admin-card">
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
                    <x-admin.used-by :items="[__('loop.used_by_marketing_ctas')]" />
                    <div>
                        <label class="loop-label">{{ __('loop.holiday_message_text') }}</label>
                        <textarea name="holiday_message_text" rows="2" class="loop-input">{{ old('holiday_message_text', $marketing['holiday_message_text']) }}</textarea>
                    </div>
                    <button class="admin-btn">{{ __('loop.save') }}</button>
                </x-admin.settings-lock>
            </form>
        </div>
    @endif

    @if ($tab === 'notifications')
        <div class="admin-card">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.settings_tab_notifications') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_notifications_blurb') }}</p>
            <x-admin.used-by :items="[__('loop.used_by_notifications')]" />
            <form method="POST" action="{{ route('admin.settings.notifications') }}" class="mt-4">
                @csrf
                @method('PUT')
                <x-admin.settings-lock>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ([
                            'owner_in_app' => __('loop.notif_owner_in_app'),
                            'owner_email' => __('loop.notif_owner_email'),
                            'owner_sms' => __('loop.notif_owner_sms'),
                            'customer_in_app' => __('loop.notif_customer_in_app'),
                            'customer_sms' => __('loop.notif_customer_sms'),
                            'affiliate_in_app' => __('loop.notif_affiliate_in_app'),
                            'affiliate_sms' => __('loop.notif_affiliate_sms'),
                            'admin_digest' => __('loop.notif_admin_digest'),
                            'holiday_messages' => __('loop.holiday_messages'),
                            'in_app_digest' => __('loop.in_app_digest'),
                            'trial_reminders' => __('loop.trial_reminders'),
                        ] as $key => $label)
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $notifications[$key]))> {{ $label }}</label>
                        @endforeach
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="loop-label">{{ __('loop.quiet_hours_start') }}</label>
                            <input type="number" min="0" max="23" name="quiet_hours_start" value="{{ old('quiet_hours_start', $notifications['quiet_hours_start']) }}" class="loop-input">
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.quiet_hours_end') }}</label>
                            <input type="number" min="0" max="23" name="quiet_hours_end" value="{{ old('quiet_hours_end', $notifications['quiet_hours_end']) }}" class="loop-input">
                            <x-admin.policy-note :body="__('loop.quiet_hours_enforced_note')" />
                        </div>
                    </div>
                    <button class="admin-btn">{{ __('loop.save') }}</button>
                </x-admin.settings-lock>
            </form>
        </div>
        <div class="admin-card mt-5">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.sms_rates_title') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.sms_rates_blurb') }}</p>
            <form method="POST" action="{{ route('admin.settings.messaging') }}" class="mt-4">
                @csrf
                @method('PUT')
                <x-admin.settings-lock>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="loop-label">{{ __('loop.price_per_message') }}</label>
                            <input type="number" min="1" name="price_per_message" value="{{ old('price_per_message', $messagingRates['price_per_message'] ?? 30) }}" class="loop-input">
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.chars_per_message') }}</label>
                            <input type="number" min="1" max="320" name="chars_per_message" value="{{ old('chars_per_message', $messagingRates['chars_per_message'] ?? 160) }}" class="loop-input">
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.sender_id_yearly_fee') }}</label>
                            <input type="number" min="0" name="sender_id_yearly_fee" value="{{ old('sender_id_yearly_fee', $messagingRates['sender_id_yearly_fee'] ?? 15000) }}" class="loop-input">
                        </div>
                    </div>
                    <button class="admin-btn mt-4">{{ __('loop.save') }}</button>
                </x-admin.settings-lock>
            </form>
        </div>
    @endif

    @if ($tab === 'visibility')
        <form method="POST" action="{{ route('admin.settings.sales-visibility') }}" class="admin-card space-y-5">
            @csrf
            @method('PUT')
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.admin_sales_visibility') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.admin_sales_visibility_blurb') }}</p>
                <x-admin.used-by :items="[__('loop.used_by_visibility')]" />
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
                <button class="admin-btn">{{ __('loop.save') }}</button>
            </x-admin.settings-lock>
        </form>
    @endif

    @if ($tab === 'referrals')
        <form method="POST" action="{{ route('admin.settings.referrals') }}" class="admin-card space-y-5">
            @csrf
            @method('PUT')
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.admin_referral_program') }}</h2>
                <p class="mt-1 text-xs font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ __('loop.admin_referrals_program_kind') }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_referrals_blurb') }}</p>
                <x-admin.used-by :items="[__('loop.used_by_referrals')]" />
            </div>
            <div class="rounded-2xl bg-mint-soft/50 p-4 text-sm text-ink">
                <p class="font-semibold">{{ __('loop.admin_referral_both_sides') }}</p>
                <p class="mt-1 text-ink-muted">{{ __('loop.admin_referral_both_sides_body') }}</p>
            </div>
            <x-admin.settings-lock>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="loop-label">{{ __('loop.goal_count') }}</label>
                        <input type="number" min="1" name="goal_count" value="{{ old('goal_count', $referral['goal_count']) }}" class="loop-input" required>
                        <p class="mt-1 text-xs text-ink-muted">{{ __('loop.goal_count_help') }}</p>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.referrer_extra_days') }}</label>
                        <input type="number" min="0" name="referrer_extra_days_per_referral" value="{{ old('referrer_extra_days_per_referral', $referral['referrer_extra_days_per_referral']) }}" class="loop-input" required>
                        <p class="mt-1 text-xs text-ink-muted">{{ __('loop.referrer_extra_days_help') }}</p>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.referred_extra_trial_days') }}</label>
                        <input type="number" min="0" name="referred_extra_trial_days" value="{{ old('referred_extra_trial_days', $referral['referred_extra_trial_days']) }}" class="loop-input" required>
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
                    <button class="admin-btn">{{ __('loop.save') }}</button>
                    <a href="{{ route('admin.referrals.index') }}" class="admin-btn-ghost !py-2.5">{{ __('loop.referral_progress_title') }} →</a>
                </div>
            </x-admin.settings-lock>
        </form>
    @endif

    @if ($tab === 'affiliates')
        <div class="admin-card">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.affiliate_program_settings') }}</h2>
            <p class="mt-1 text-xs font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ __('loop.admin_affiliates_program_kind') }}</p>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_affiliates_blurb') }}</p>
            <p class="mt-2 rounded-2xl bg-violet-soft/50 px-4 py-3 text-sm text-ink">{{ __('loop.affiliate_kpi_governed') }} — {{ __('loop.monthly_paying_target') }}: {{ $affiliate['monthly_paying_business_target'] }}</p>
            <x-admin.policy-note :body="__('loop.commission_basis_note')" />
            <form method="POST" action="{{ route('admin.settings.affiliates') }}" class="mt-4">
                @csrf
                @method('PUT')
                <x-admin.settings-lock>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="loop-label">{{ __('loop.commission_percent') }}</label>
                            <input type="number" min="1" max="50" name="commission_percent" value="{{ old('commission_percent', $affiliate['commission_percent']) }}" class="loop-input" required>
                            <x-admin.used-by :items="[__('loop.used_by_affiliate_commission')]" />
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.referred_discount_percent') }}</label>
                            <input type="number" min="0" max="50" name="referred_discount_percent" value="{{ old('referred_discount_percent', $affiliate['referred_discount_percent']) }}" class="loop-input" required>
                            <x-admin.used-by :items="[__('loop.used_by_affiliate_discount')]" />
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.attribution_months') }}</label>
                            <input type="number" min="1" max="36" name="attribution_months" value="{{ old('attribution_months', $affiliate['attribution_months']) }}" class="loop-input" required>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.cookie_days') }}</label>
                            <input type="number" min="1" max="365" name="cookie_days" value="{{ old('cookie_days', $affiliate['cookie_days']) }}" class="loop-input" required>
                            <x-admin.used-by :items="[__('loop.used_by_cookie_days')]" />
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.pin_length') }}</label>
                            <input type="number" min="4" max="6" name="pin_length" value="{{ old('pin_length', $affiliate['pin_length']) }}" class="loop-input" required>
                            <x-admin.used-by :items="[__('loop.used_by_affiliate_pin')]" />
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.monthly_paying_target') }}</label>
                            <input type="number" min="1" max="100" name="monthly_paying_business_target" value="{{ old('monthly_paying_business_target', $affiliate['monthly_paying_business_target']) }}" class="loop-input" required>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.min_payout_amount') }}</label>
                            <input type="number" min="0" name="min_payout_amount" value="{{ old('min_payout_amount', $affiliate['min_payout_amount']) }}" class="loop-input" required>
                            <x-admin.policy-note :body="__('loop.policy_only_payouts')" />
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.payout_schedule') }}</label>
                            <select name="payout_schedule" class="loop-input">
                                @foreach (['weekly', 'biweekly', 'monthly'] as $sched)
                                    <option value="{{ $sched }}" @selected(old('payout_schedule', $affiliate['payout_schedule']) === $sched)>{{ __("loop.payout_$sched") }}</option>
                                @endforeach
                            </select>
                            <x-admin.policy-note :body="__('loop.policy_only_payouts')" />
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.tax_withholding_percent') }}</label>
                            <input type="number" min="0" max="40" name="tax_withholding_percent" value="{{ old('tax_withholding_percent', $affiliate['tax_withholding_percent']) }}" class="loop-input">
                            <x-admin.policy-note :body="__('loop.policy_only_payouts')" />
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
                    <x-admin.used-by :items="[__('loop.used_by_self_referral')]" />
                    <x-admin.policy-note :body="__('loop.policy_only_tax_id')" />
                    <div class="flex flex-wrap gap-3">
                        <button class="admin-btn">{{ __('loop.save') }}</button>
                        <a href="{{ route('admin.insights.affiliate-performance') }}" class="admin-btn-ghost !py-2.5">{{ __('loop.affiliate_performance') }} →</a>
                        <a href="{{ route('admin.affiliates.index', ['tab' => 'applications']) }}" class="admin-btn-ghost !py-2.5">{{ __('loop.affiliate_applications_queue') }} →</a>
                    </div>
                </x-admin.settings-lock>
            </form>
        </div>
    @endif

    @if ($tab === 'product')
        <form method="POST" action="{{ route('admin.settings.feature-flags') }}" class="admin-card space-y-5">
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
                                @if (__('loop.feature_'.$key.'_used_by') !== 'loop.feature_'.$key.'_used_by')
                                    <span class="mt-2 block text-xs text-ink-muted"><span class="font-semibold text-ink">{{ __('loop.used_by') }}:</span> {{ __('loop.feature_'.$key.'_used_by') }}</span>
                                @endif
                                <span class="mt-2 inline-block rounded-lg bg-chalk px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-ink-muted">{{ __('loop.feature_cat_'.$feature['category']) }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <button class="admin-btn">{{ __('loop.save_product_updates') }}</button>
            </x-admin.settings-lock>
        </form>
    @endif

    @if ($tab === 'health')
        <section class="admin-card">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.settings_health_title') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_health_blurb') }}</p>
            <div class="mt-5 space-y-3">
                @foreach ($healthChecks as $check)
                    <div class="flex items-start justify-between gap-4 rounded-2xl border border-ink/8 px-4 py-3">
                        <div>
                            <p class="font-semibold">{{ $check['label'] }}</p>
                            <p class="mt-1 text-sm text-ink-muted">{{ $check['detail'] }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide {{ $check['status'] === 'ok' ? 'bg-mint-soft text-mint-deep' : ($check['status'] === 'off' ? 'bg-chalk text-ink-muted' : 'bg-coral/15 text-coral') }}">
                            {{ $check['status'] === 'ok' ? '✓' : ($check['status'] === 'off' ? __('loop.off') : '⚠') }}
                            {{ $check['status'] === 'ok' ? __('loop.health_connected') : ($check['status'] === 'off' ? __('loop.health_paused') : __('loop.health_warn')) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </section>
        <section class="admin-card mt-5">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.settings_audit_title') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_audit_blurb') }}</p>
            <div class="admin-table-wrap mt-4">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('loop.when') }}</th>
                            <th>{{ __('loop.who') }}</th>
                            <th>{{ __('loop.setting') }}</th>
                            <th>{{ __('loop.what_changed') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentAudits as $audit)
                            @php
                                $old = is_array($audit->old_value) ? $audit->old_value : [];
                                $new = is_array($audit->new_value) ? $audit->new_value : [];
                                $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
                                $changed = [];
                                foreach ($keys as $field) {
                                    if (($old[$field] ?? null) != ($new[$field] ?? null)) {
                                        $from = is_bool($old[$field] ?? null) ? (($old[$field] ?? false) ? __('loop.on') : __('loop.off')) : (is_array($old[$field] ?? null) ? json_encode($old[$field]) : (string) ($old[$field] ?? '—'));
                                        $to = is_bool($new[$field] ?? null) ? (($new[$field] ?? false) ? __('loop.on') : __('loop.off')) : (is_array($new[$field] ?? null) ? json_encode($new[$field]) : (string) ($new[$field] ?? '—'));
                                        $changed[] = $field.': '.$from.' → '.$to;
                                    }
                                }
                            @endphp
                            <tr>
                                <td class="whitespace-nowrap">{{ $audit->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}</td>
                                <td>{{ $audit->user?->name ?? __('loop.system') }}</td>
                                <td>{{ $audit->setting_key }}</td>
                                <td class="text-xs text-ink-muted">{{ $changed !== [] ? implode(' · ', array_slice($changed, 0, 6)) : __('loop.no_field_diff') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-ink-muted">{{ __('loop.no_setting_audits') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($tab === 'legal')
        <section class="admin-card">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.settings_tab_legal') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.settings_tab_legal_blurb') }}</p>
            <form method="POST" action="{{ route('admin.settings.legal') }}" class="mt-5 space-y-4">
                @csrf
                @method('PUT')
                @foreach ([
                    'legal_name' => __('loop.legal_company_name'),
                    'registration_number' => __('loop.legal_registration'),
                    'tin' => __('loop.legal_tin'),
                    'address' => __('loop.legal_address'),
                    'legal_email' => __('loop.legal_email'),
                    'dpo_email' => __('loop.legal_dpo_email'),
                    'support_email' => __('loop.legal_support_email'),
                    'support_phone' => __('loop.legal_support_phone'),
                ] as $key => $label)
                    <div>
                        <label class="loop-label">{{ $label }}</label>
                        <input name="{{ $key }}" value="{{ old($key, $legalIdentity[$key] ?? '') }}" class="loop-input">
                    </div>
                @endforeach
                <button class="loop-btn">{{ __('loop.save') }}</button>
            </form>
        </section>
        <section class="admin-card mt-5">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.legal_documents') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.legal_documents_admin_blurb') }}</p>
            <div class="admin-table-wrap mt-4">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('loop.document') }}</th>
                            <th>{{ __('loop.version') }}</th>
                            <th>{{ __('loop.status') }}</th>
                            <th>{{ __('loop.effective') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($legalDocuments as $doc)
                            <tr>
                                <td>{{ $doc->title_en }}</td>
                                <td>{{ $doc->version }}</td>
                                <td>{{ $doc->status }}{{ $doc->counsel_reviewed ? '' : ' · DRAFT' }}</td>
                                <td>{{ optional($doc->effective_on)->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($tab === 'links')
        <section class="admin-card">
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
</x-admin-layout>
