<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.admin_settings_hub') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.admin_settings_hub_blurb') }}</p>
    </x-slot>

    @include('admin.partials.nav')

    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
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
                    <p class="mt-1 text-xs text-ink-muted">{{ __('loop.trial_days_help') }}</p>
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
                    <p class="mt-1 text-xs text-ink-muted">{{ __('loop.free_max_monthly_visits_help') }}</p>
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

        <div class="space-y-4">
            <a href="{{ route('admin.referrals.program') }}" class="loop-panel block p-5 transition hover:-translate-y-0.5">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.admin_referral_program') }}</p>
                <p class="mt-2 font-display text-lg font-semibold">{{ __('loop.configure_referrals') }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.goal_count') }}: {{ $referral['goal_count'] }} · {{ __('loop.referrer_months_per') }}: {{ $referral['referrer_months_per_referral'] }}</p>
            </a>

            <a href="{{ route('admin.plans.index') }}" class="loop-panel block p-5 transition hover:-translate-y-0.5">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.admin_plans') }}</p>
                <p class="mt-2 font-display text-lg font-semibold">{{ __('loop.view_plans') }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ $plans->count() }} {{ __('loop.plans_live') }}</p>
            </a>

            <div class="loop-panel p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.integrations') }}</p>
                <p class="mt-2 text-sm text-ink-muted">{{ __('loop.integrations_blurb') }}</p>
                <ul class="mt-4 space-y-2">
                    @foreach ($integrations as $item)
                        <li class="flex items-center justify-between rounded-xl bg-chalk px-3 py-2 text-sm">
                            <span class="font-medium">{{ $item['name'] }}</span>
                            <span class="text-xs font-semibold text-ink-muted">{{ __('loop.coming_soon') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
