<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.billing') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.upgrade_title') }}</h1>
            <p class="mt-1 max-w-2xl text-ink-muted">{{ __('loop.upgrade_blurb') }}</p>
        </div>
    </x-slot>

    @if ($trialExpired)
        <div class="mb-6 rounded-[1.5rem] border border-coral/30 bg-coral/10 px-5 py-4">
            <p class="font-display text-lg font-semibold">{{ __('loop.trial_ended_title') }}</p>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.trial_ended_body') }}</p>
        </div>
    @elseif ($business->billing_status === 'trialing' && $daysLeft > 0)
        <div class="mb-6 rounded-[1.5rem] border border-mint/30 bg-mint-soft/50 px-5 py-4">
            <p class="font-semibold">{{ __('loop.trial_days_left', ['days' => $daysLeft]) }}</p>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.trial_days_left_body') }}</p>
        </div>
    @elseif (\App\Support\Plans::isPaidPlan($business->plan_key) && $business->billing_status === 'active')
        <div class="mb-6 rounded-[1.5rem] border border-mint/30 bg-mint-soft/40 px-5 py-4">
            <p class="font-semibold">{{ __('loop.current_plan') }}: {{ $currentPlan?->name ?? $business->plan_key }}</p>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.on_paid_plan_body') }}</p>
        </div>
    @endif

    <div class="mb-8 grid gap-3 sm:grid-cols-3">
        <div class="loop-panel p-4">
            <p class="text-xs text-ink-muted">{{ __('loop.free_max_shops') }}</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ $caps['max_shops'] ?? __('loop.unlimited') }}</p>
        </div>
        <div class="loop-panel p-4">
            <p class="text-xs text-ink-muted">{{ __('loop.free_max_members') }}</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ $caps['max_members'] ?? __('loop.unlimited') }}</p>
        </div>
        <div class="loop-panel p-4">
            <p class="text-xs text-ink-muted">{{ __('loop.free_max_monthly_visits') }}</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ $caps['max_monthly_visits'] ?? __('loop.unlimited') }}</p>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        @foreach ($plans->where('key', '!=', 'free') as $plan)
            @php $isCurrent = $business->plan_key === $plan->key && $business->billing_status === 'active'; @endphp
            <div @class([
                'flex flex-col rounded-[1.75rem] border p-6',
                'border-mint bg-mint-soft/30 ring-2 ring-mint' => $plan->key === 'growth',
                'border-ink/10 bg-white' => $plan->key !== 'growth',
            ])>
                @if ($plan->key === 'growth')
                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.most_popular') }}</p>
                @endif
                <p class="font-display text-xl font-semibold">{{ $plan->name }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ $plan->tagline }}</p>
                <p class="mt-4 font-display text-3xl font-semibold">{{ $plan->priceLabel() }}</p>
                <ul class="mt-4 flex-1 space-y-2 text-sm text-ink-muted">
                    @foreach ($plan->features ?? [] as $feature)
                        <li>✓ {{ $feature }}</li>
                    @endforeach
                </ul>
                @if ($isCurrent)
                    <span class="mt-6 inline-flex justify-center rounded-xl bg-chalk px-4 py-3 text-sm font-semibold text-ink-muted">{{ __('loop.current_plan') }}</span>
                @else
                    <form method="POST" action="{{ route('billing.choose') }}" class="mt-6 space-y-3">
                        @csrf
                        <input type="hidden" name="plan_key" value="{{ $plan->key }}">
                        <input type="hidden" name="country" value="{{ $country }}">
                        <div>
                            <label class="loop-label">{{ __('loop.pay_with_phone') }}</label>
                            <div class="flex gap-2">
                                <span class="inline-flex items-center rounded-2xl border border-ink/10 bg-chalk px-3 text-sm font-semibold">{{ $dial }}</span>
                                <input name="phone" value="{{ old('phone') }}" class="loop-input !mt-0" placeholder="7XXXXXXXX" required>
                            </div>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.pay_with_phone_help', ['currency' => $plan->currency ?: $currency]) }}</p>
                        </div>
                        <button class="loop-btn-mint w-full">{{ __('loop.pay_and_activate', ['plan' => $plan->name]) }}</button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>

    <p class="mt-8 text-center text-sm text-ink-muted">{{ __('loop.upgrade_payment_note') }}</p>
    <a href="{{ route('settings') }}" class="mt-4 block text-center text-sm font-semibold text-ink-muted underline">{{ __('loop.back') }}</a>
</x-app-layout>
