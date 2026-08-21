<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start gap-3">
            <x-back-icon :href="route('settings')" />
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.billing') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">
                    {{ ! empty($paused) ? __('loop.upgrade_title_paused') : __('loop.upgrade_title') }}
                </h1>
                <p class="mt-1 max-w-2xl text-ink-muted">
                    {{ ! empty($paused) ? __('loop.upgrade_blurb_paused') : __('loop.upgrade_blurb') }}
                </p>
            </div>
        </div>
    </x-slot>

    @if (! empty($paused) || ! empty($grace))
        <div class="mb-6 rounded-[1.5rem] border border-coral/30 bg-coral/10 px-5 py-5">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-coral">{{ __('loop.billing_protect_title') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ __('loop.loop_paused_named', ['name' => $business->name]) }}</p>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.loop_customers_connected', ['count' => number_format($momentum['members'])]) }}</p>
            <x-loop-pause-facts :business="$business" :momentum="$momentum" />
            <p class="mt-4 text-sm font-semibold">{{ __('loop.loop_paused_safe') }}</p>
        </div>
    @elseif ($trialExpired)
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
            @if ($business->plan_renews_at)
                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.renews_on', ['date' => $business->plan_renews_at->format('d M Y')]) }}</p>
            @endif
            @if ($business->price_locked_until?->isFuture())
                <p class="mt-1 text-xs text-mint-deep">{{ __('loop.price_locked_until', ['date' => $business->price_locked_until->format('d M Y')]) }}</p>
            @endif
        </div>
    @endif

    <div class="mb-8 grid gap-3 sm:grid-cols-3 lg:grid-cols-5">
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
        <div class="loop-panel p-4">
            <p class="text-xs text-ink-muted">{{ __('loop.max_product_pushes') }}</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ $caps['max_product_pushes'] ?? __('loop.unlimited') }}</p>
        </div>
        <div class="loop-panel p-4">
            <p class="text-xs text-ink-muted">{{ __('loop.max_offers') }}</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ $caps['max_offers'] ?? __('loop.unlimited') }}</p>
        </div>
    </div>

    <div
        class="space-y-6"
        x-data="billingPayConfirm({
            months: {{ ! empty($paused) || ! empty($grace) ? 12 : 1 }},
            discounts: @js($intervals),
            monthsLabel: @js(__('loop.months')),
            confirmTitle: @js(__('loop.confirm_plan_title')),
            confirmCta: @js(__('loop.pay_now')),
            cancelCta: @js(__('loop.cancel')),
        })"
    >
        <div>
            <p class="loop-label">{{ __('loop.pay_for_months') }}</p>
            <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach ($intervals as $months => $discount)
                    @php $quote = $quotes[$months] ?? null; @endphp
                    <label class="cursor-pointer rounded-2xl border border-ink/10 bg-white px-4 py-3 text-sm has-[:checked]:border-mint has-[:checked]:bg-mint-soft/40">
                        <input type="radio" class="sr-only" name="interval_ui" value="{{ $months }}" x-model.number="months" @checked($months === ($paused || $grace ? 12 : 1))>
                        <span class="font-semibold">{{ $months }} {{ __('loop.months') }}</span>
                        @if ($months === 12)
                            <span class="mt-0.5 block text-[10px] font-bold uppercase tracking-[0.12em] text-mint-deep">{{ __('loop.interval_best_value') }}</span>
                        @endif
                        @if ($quote && $quote['save'] > 0)
                            <span class="mt-1 block text-xs text-ink-muted line-through">{{ $currency }} {{ number_format($quote['full']) }}</span>
                            <span class="block text-sm font-semibold">{{ $currency }} {{ number_format($quote['amount']) }}</span>
                            <span class="mt-0.5 block text-xs text-mint-deep">{{ __('loop.interval_save_money', ['currency' => $currency, 'amount' => number_format($quote['save'])]) }}</span>
                        @elseif ($quote)
                            <span class="mt-1 block text-sm font-semibold">{{ $currency }} {{ number_format($quote['amount']) }}</span>
                        @elseif ($discount > 0)
                            <span class="mt-1 block text-xs text-mint-deep">-{{ $discount }}%</span>
                        @endif
                    </label>
                @endforeach
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
                    <p class="mt-4 font-display text-3xl font-semibold" x-text="priceLabel({{ (int) $plan->price_monthly }}, @js($plan->currency ?: $currency))"></p>
                    <p class="text-xs text-ink-muted">{{ __('loop.billed_upfront') }}</p>
                    @if ($plan->key === 'growth')
                        <p class="mt-2 text-xs font-semibold text-mint-deep">{{ __('loop.price_lock_12') }}</p>
                    @endif
                    <ul class="mt-4 flex-1 space-y-2 text-sm text-ink-muted">
                        @foreach ($plan->features ?? [] as $feature)
                            <li>✓ {{ $feature }}</li>
                        @endforeach
                    </ul>
                    @if ($isCurrent)
                        <span class="mt-6 inline-flex justify-center rounded-xl bg-chalk px-4 py-3 text-sm font-semibold text-ink-muted">{{ __('loop.current_plan') }}</span>
                    @else
                        <form method="POST" action="{{ route('billing.choose') }}" class="mt-6 space-y-3" @submit="ask($event, @js($plan->name), {{ (int) $plan->price_monthly }}, @js($plan->currency ?: $currency))">
                            @csrf
                            <input type="hidden" name="plan_key" value="{{ $plan->key }}">
                            <input type="hidden" name="country" value="{{ $country }}">
                            <input type="hidden" name="months" :value="months">
                            <div>
                                <label class="loop-label">{{ __('loop.pay_with_phone') }}</label>
                                <div class="flex gap-2">
                                    <span class="inline-flex items-center rounded-2xl border border-ink/10 bg-chalk px-3 text-sm font-semibold">{{ $dial }}</span>
                                    <input name="phone" value="{{ old('phone') }}" class="loop-input !mt-0" placeholder="7XXXXXXXX" required>
                                </div>
                                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.pay_with_phone_help', ['currency' => $plan->currency ?: $currency]) }}</p>
                            </div>
                            <button class="loop-btn-mint w-full">{{ ! empty($paused) ? __('loop.reactivate_loop') : __('loop.pay_and_activate', ['plan' => $plan->name]) }}</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>

        <template x-teleport="body">
            <div x-show="open" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" @keydown.escape.window="open=false">
                <div class="absolute inset-0 bg-ink/60 backdrop-blur-sm" @click="open=false"></div>
                <div class="relative w-full max-w-md rounded-[2rem] border border-ink/10 bg-white p-8 text-center shadow-[0_40px_100px_rgba(17,17,20,0.35)]">
                    <p class="font-display text-3xl font-semibold" x-text="title"></p>
                    <p class="mt-3 text-base text-ink-muted" x-text="body"></p>
                    <button type="button" class="loop-btn mt-8 w-full" @click="confirm()">{{ __('loop.pay_now') }}</button>
                    <button type="button" class="mt-4 text-sm font-semibold text-ink-muted" @click="open=false">{{ __('loop.cancel') }}</button>
                </div>
            </div>
        </template>
    </div>

    <p class="mt-8 text-center text-sm text-ink-muted">{{ __('loop.upgrade_payment_note') }}</p>
</x-app-layout>
