<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.settings') }}</p>
                <h1 class="mt-1 font-display text-2xl font-semibold sm:text-3xl">{{ __('loop.upgrade_title') }}</h1>
                <p class="mt-1 max-w-2xl text-sm text-ink-muted">{{ __('loop.upgrade_blurb') }}</p>
            </div>
            <x-settings-back :href="route('settings')" :label="__('loop.back')" />
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
    @endif

    <div class="mb-6 loop-panel p-5">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.current_plan') }}</p>
        <p class="mt-2 font-display text-2xl font-semibold">{{ $currentPlan?->name ?? ucfirst((string) $business->plan_key) }}</p>
        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.on_paid_plan_body') }}</p>
        <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl bg-chalk/70 px-4 py-3">
                <p class="text-xs text-ink-muted">{{ __('loop.free_max_shops') }}</p>
                <p class="mt-1 font-display text-xl font-semibold">{{ $caps['max_shops'] ?? __('loop.unlimited') }}</p>
            </div>
            <div class="rounded-2xl bg-chalk/70 px-4 py-3">
                <p class="text-xs text-ink-muted">{{ __('loop.free_max_members') }}</p>
                <p class="mt-1 font-display text-xl font-semibold">{{ $caps['max_members'] ?? __('loop.unlimited') }}</p>
            </div>
            <div class="rounded-2xl bg-chalk/70 px-4 py-3">
                <p class="text-xs text-ink-muted">{{ __('loop.free_max_monthly_visits') }}</p>
                <p class="mt-1 font-display text-xl font-semibold">{{ $caps['max_monthly_visits'] ?? __('loop.unlimited') }}</p>
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        @foreach ($plans->where('key', '!=', 'free') as $plan)
            @php $isCurrent = $business->plan_key === $plan->key && $business->billing_status === 'active'; @endphp
            <div @class([
                'loop-panel flex flex-col p-6',
                'ring-2 ring-mint border-mint/40' => $plan->key === 'growth',
            ])>
                @if ($plan->key === 'growth')
                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.most_popular') }}</p>
                @endif
                <p class="font-display text-xl font-semibold">{{ $plan->name }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ $plan->tagline }}</p>
                <p class="mt-4 font-display text-3xl font-semibold tracking-tight">{{ $plan->priceLabel() }}</p>
                <ul class="mt-4 flex-1 space-y-2.5 text-sm text-ink">
                    @foreach ($plan->features ?? [] as $feature)
                        <li class="flex gap-2">
                            <span class="mt-0.5 text-mint-deep">✓</span>
                            <span>{{ $feature }}</span>
                        </li>
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
                                <input name="phone" value="{{ old('phone') }}" class="loop-input !mt-0" placeholder="7XXXXXXXX" required autocomplete="off">
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
</x-app-layout>
