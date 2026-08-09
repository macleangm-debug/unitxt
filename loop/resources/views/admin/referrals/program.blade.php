<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.admin_referral_program') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.admin_referral_program_blurb') }}</p>
    </x-slot>

    @include('admin.partials.nav')

    <div class="loop-admin-tabs" role="tablist">
        <a href="{{ route('admin.referrals.index', ['tab' => 'progress']) }}" class="loop-admin-tab">{{ __('loop.referral_tab_progress') }}</a>
        <a href="{{ route('admin.referrals.program') }}" class="loop-admin-tab is-active">{{ __('loop.referral_tab_program') }}</a>
    </div>

    <form method="POST" action="{{ route('admin.referrals.program.update') }}" class="mx-auto max-w-2xl space-y-5 loop-glass p-6">
        @csrf
        @method('PUT')

        <div class="rounded-2xl bg-mint-soft/50 p-4 text-sm text-ink">
            <p class="font-semibold">{{ __('loop.admin_referral_both_sides') }}</p>
            <p class="mt-1 text-ink-muted">{{ __('loop.admin_referral_both_sides_body') }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="loop-label">{{ __('loop.goal_count') }}</label>
                <input type="number" min="1" name="goal_count" value="{{ old('goal_count', $program['goal_count']) }}" class="loop-input" required>
                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.goal_count_help') }}</p>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.referrer_extra_days') }}</label>
                <input type="number" min="0" name="referrer_extra_days_per_referral" value="{{ old('referrer_extra_days_per_referral', $program['referrer_extra_days_per_referral']) }}" class="loop-input" required>
                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.referrer_extra_days_help') }}</p>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.referred_extra_trial_days') }}</label>
                <input type="number" min="0" name="referred_extra_trial_days" value="{{ old('referred_extra_trial_days', $program['referred_extra_trial_days']) }}" class="loop-input" required>
                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.referred_extra_trial_days_help') }}</p>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.referrer_discount_percent') }}</label>
                <input type="number" min="0" max="100" name="referrer_discount_percent" value="{{ old('referrer_discount_percent', $program['referrer_discount_percent']) }}" class="loop-input" required>
            </div>
        </div>

        <input type="hidden" name="referrer_months_per_referral" value="0">
        <input type="hidden" name="referred_bonus_months" value="0">

        <button class="loop-btn-mint w-full">{{ __('loop.save') }}</button>
    </form>
</x-app-layout>
