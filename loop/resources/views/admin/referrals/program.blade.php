<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.admin_referral_program') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.admin_referral_program_blurb') }}</p>
    </x-slot>

    @include('admin.partials.nav')

    <form method="POST" action="{{ route('admin.referrals.program.update') }}" class="mx-auto max-w-2xl space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)]">
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
                <label class="loop-label">{{ __('loop.referrer_months_per') }}</label>
                <input type="number" min="0" name="referrer_months_per_referral" value="{{ old('referrer_months_per_referral', $program['referrer_months_per_referral']) }}" class="loop-input" required>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.referrer_discount_percent') }}</label>
                <input type="number" min="0" max="100" name="referrer_discount_percent" value="{{ old('referrer_discount_percent', $program['referrer_discount_percent']) }}" class="loop-input" required>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.referred_extra_trial_days') }}</label>
                <input type="number" min="0" name="referred_extra_trial_days" value="{{ old('referred_extra_trial_days', $program['referred_extra_trial_days']) }}" class="loop-input" required>
                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.referred_extra_trial_days_help') }}</p>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.referred_bonus_months') }}</label>
                <input type="number" min="0" name="referred_bonus_months" value="{{ old('referred_bonus_months', $program['referred_bonus_months']) }}" class="loop-input" required>
                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.referred_bonus_months_help') }}</p>
            </div>
        </div>

        <div>
            <p class="loop-label">{{ __('loop.milestones') }}</p>
            <p class="mb-3 text-xs text-ink-muted">{{ __('loop.milestones_help') }}</p>
            <div class="space-y-2">
                @php
                    $rows = old('milestone_count')
                        ? collect(old('milestone_count'))->map(fn ($c, $i) => ['count' => $c, 'bonus_months' => old('milestone_bonus.'.$i)])
                        : collect($program['milestones']);
                    while ($rows->count() < 3) {
                        $rows->push(['count' => '', 'bonus_months' => '']);
                    }
                @endphp
                @foreach ($rows as $row)
                    <div class="grid grid-cols-2 gap-3">
                        <input type="number" min="1" name="milestone_count[]" value="{{ $row['count'] }}" class="loop-input" placeholder="{{ __('loop.milestone_count_ph') }}">
                        <input type="number" min="0" name="milestone_bonus[]" value="{{ $row['bonus_months'] }}" class="loop-input" placeholder="{{ __('loop.milestone_bonus_ph') }}">
                    </div>
                @endforeach
            </div>
        </div>

        <button class="loop-btn-mint w-full">{{ __('loop.save') }}</button>
    </form>
</x-app-layout>
