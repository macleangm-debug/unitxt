<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.admin_settings_hub') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.admin_settings_hub_blurb') }}</p>
    </x-slot>

    @include('admin.partials.nav')

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('admin.settings.billing') }}" class="space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)]">
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

            <button class="loop-btn-mint w-full">{{ __('loop.save') }}</button>
        </form>

        <form method="POST" action="{{ route('admin.settings.growth') }}" class="space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)]">
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

            <button class="loop-btn-mint w-full">{{ __('loop.save') }}</button>
        </form>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('admin.settings.base-url') }}" class="space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)]">
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
            <button class="loop-btn-mint w-full">{{ __('loop.save') }}</button>
        </form>

        <form method="POST" action="{{ route('admin.settings.sectors') }}" class="space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)]">
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
            <button class="loop-btn-mint w-full">{{ __('loop.save') }}</button>
        </form>

        <form method="POST" action="{{ route('admin.settings.sales-visibility') }}" class="space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)]">
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
            <button class="loop-btn-mint w-full">{{ __('loop.save') }}</button>
        </form>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <a href="{{ route('admin.affiliates.index') }}" class="loop-panel block p-5 transition hover:-translate-y-0.5">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.admin_affiliates') }}</p>
            <p class="mt-2 font-display text-lg font-semibold">{{ __('loop.configure_affiliates') }}</p>
        </a>
        <a href="{{ route('admin.referrals.program') }}" class="loop-panel block p-5 transition hover:-translate-y-0.5">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.admin_referral_program') }}</p>
            <p class="mt-2 font-display text-lg font-semibold">{{ __('loop.configure_referrals') }}</p>
        </a>
        <a href="{{ route('admin.plans.index') }}" class="loop-panel block p-5 transition hover:-translate-y-0.5">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.admin_plans') }}</p>
            <p class="mt-2 font-display text-lg font-semibold">{{ __('loop.view_plans') }}</p>
        </a>
        <div class="loop-panel p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.integrations') }}</p>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.integrations_blurb') }}</p>
        </div>
    </div>
</x-app-layout>
