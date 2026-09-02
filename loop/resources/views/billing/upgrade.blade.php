<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start gap-3">
            <x-back-icon :href="route('settings')" />
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.billing_eyebrow') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">
                    {{ ! empty($paused) ? __('loop.upgrade_title_paused') : __('loop.keep_loop_running') }}
                </h1>
                <p class="mt-1 max-w-xl text-ink-muted">
                    {{ ! empty($paused) ? __('loop.upgrade_blurb_paused') : __('loop.keep_loop_running_blurb') }}
                </p>
            </div>
        </div>
    </x-slot>

    <div
        class="mx-auto max-w-xl space-y-8 pb-24 sm:pb-0"
        x-data="billingPayConfirm({
            months: {{ (int) $selectedMonths }},
            discounts: @js($discounts),
            monthly: {{ (int) $quoteMonthly }},
            currency: @js($currency),
            planKey: @js($payPlanKey),
            payUrl: @js(route('payments.show')),
            plansUrl: @js(route('billing.plans')),
            untils: @js($untils),
            monthLabels: @js($monthLabels),
            saveTemplate: @js(__('loop.interval_save_money')),
            payTemplate: @js(__('loop.billing_pay_cta')),
            paySaveTemplate: @js(__('loop.billing_pay_cta_save')),
            continueLabel: @js(__('loop.continue_to_payment')),
        })"
    >
        @if (! empty($paused) || ! empty($grace))
            <div class="rounded-[1.5rem] border border-coral/30 bg-coral/10 px-5 py-5">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-coral">{{ __('loop.billing_protect_title') }}</p>
                <p class="mt-2 font-display text-2xl font-semibold">{{ __('loop.loop_paused_named', ['name' => $business->name]) }}</p>
                <p class="mt-2 text-sm text-ink-muted">{{ __('loop.loop_customers_connected', ['count' => number_format($momentum['members'])]) }}</p>
                <x-loop-pause-facts :business="$business" :momentum="$momentum" />
                <p class="mt-4 text-sm font-semibold">{{ __('loop.loop_paused_safe') }}</p>
            </div>
        @elseif ($trialExpired)
            <div class="rounded-[1.5rem] border border-coral/30 bg-coral/10 px-5 py-4">
                <p class="font-display text-lg font-semibold">{{ __('loop.trial_ended_title') }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.trial_ended_body') }}</p>
            </div>
        @endif

        <section class="loop-panel p-6 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.your_plan') }}</p>
            <h2 class="mt-2 font-display text-3xl font-semibold">{{ $currentPlan?->name ?? $business->plan_key }}</h2>
            <p class="mt-2 text-sm font-semibold">
                @if (! empty($paused))
                    {{ __('loop.paused') }}
                @elseif (! empty($grace))
                    {{ __('loop.past_due') }}
                @elseif ($business->billing_status === 'trialing')
                    {{ __('loop.trialing') }}
                @else
                    {{ __('loop.active') }}
                @endif
                @if ($coverageUntil)
                    <span class="font-normal text-ink-muted">· {{ __('loop.until_date', ['date' => $coverageUntil]) }}</span>
                @endif
            </p>
            @if ($quoteMonthly > 0 && \App\Support\Plans::isPaidPlan($business->plan_key))
                <p class="mt-3 font-display text-xl font-semibold">{{ $currency }} {{ number_format($quoteMonthly) }} <span class="text-base font-normal text-ink-muted">/ {{ __('loop.mo') }}</span></p>
            @endif
            @if ($business->price_locked_until?->isFuture())
                <p class="mt-2 text-xs text-mint-deep">{{ __('loop.price_locked_until', ['date' => $business->price_locked_until->translatedFormat('j F Y')]) }}</p>
            @endif
            @if (! \App\Support\Plans::isPaidPlan($business->plan_key) && $payPlan)
                <p class="mt-3 text-sm text-ink-muted">{{ __('loop.billing_start_on', ['plan' => $payPlan->name]) }}</p>
            @endif
            <a class="mt-5 inline-flex text-sm font-semibold text-violet" :href="plansHref()">{{ __('loop.see_all_packages') }}</a>
        </section>

        @if (! empty($showValue) && empty($paused) && empty($grace))
            <section>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.what_youre_keeping') }}</p>
                <div class="mt-3 grid grid-cols-2 gap-3">
                    <div class="rounded-2xl bg-white px-4 py-3">
                        <p class="font-display text-2xl font-semibold">{{ number_format($momentum['members']) }}</p>
                        <p class="text-xs text-ink-muted">{{ __('loop.members') }}</p>
                    </div>
                    <div class="rounded-2xl bg-white px-4 py-3">
                        <p class="font-display text-2xl font-semibold">{{ number_format($momentum['close_to_reward']) }}</p>
                        <p class="text-xs text-ink-muted">{{ __('loop.billing_close_to_reward') }}</p>
                    </div>
                    <div class="rounded-2xl bg-white px-4 py-3">
                        <p class="font-display text-2xl font-semibold">{{ number_format($momentum['rewards_ready']) }}</p>
                        <p class="text-xs text-ink-muted">{{ __('loop.billing_rewards_ready') }}</p>
                    </div>
                    <div class="rounded-2xl bg-white px-4 py-3">
                        <p class="font-display text-2xl font-semibold">{{ number_format($momentum['active_campaigns']) }}</p>
                        <p class="text-xs text-ink-muted">{{ __('loop.billing_campaigns_running') }}</p>
                    </div>
                </div>
                <p class="mt-3 text-sm text-ink-muted">{{ __('loop.paying_ahead_keeps') }}</p>
            </section>
        @endif

        <section>
            <h2 class="font-display text-2xl font-semibold">{{ __('loop.pay_ahead_save') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.how_long_continue') }}</p>
            <div class="mt-4">
                <x-billing-month-pills :discounts="$discounts" />
            </div>
            <p class="mt-3 text-sm font-semibold">
                <span x-text="monthLabel()"></span>
                <span class="ml-2 text-xs font-bold uppercase tracking-[0.12em] text-mint-deep" x-show="months === 12" x-cloak>{{ __('loop.interval_best_value') }}</span>
            </p>
        </section>

        <section class="loop-panel p-6 sm:p-8 ring-2 ring-violet/15">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet" x-text="summaryTitle(@js($payPlan?->name ?? 'Growth'))"></p>
            <p class="mt-4 text-sm text-ink-muted">
                <span x-text="money(monthly)"></span>
                ×
                <span x-text="months"></span>
            </p>
            <p class="text-sm text-ink-muted line-through" x-show="quote().save > 0" x-cloak x-text="money(quote().full)"></p>
            <p class="mt-2 text-sm font-semibold text-mint-deep" x-show="quote().save > 0" x-cloak x-text="saveLabel()"></p>
            <p class="mt-3 font-display text-4xl font-semibold" x-text="money(quote().amount)"></p>
            <p class="mt-2 text-sm text-ink-muted">
                {{ __('loop.new_expiry') }}
                <span class="font-semibold text-ink" x-text="untilLabel()"></span>
            </p>
            <a class="loop-btn mt-6 hidden w-full sm:inline-flex" :href="payHref()">{{ ! empty($paused) ? __('loop.reactivate_loop') : __('loop.continue_to_payment') }}</a>
        </section>

        @if ($history->isNotEmpty())
            <section class="pt-2">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.payment_history') }}</p>
                <ul class="mt-3 divide-y divide-ink/5">
                    @foreach ($history as $index => $intent)
                        <li
                            class="flex items-baseline justify-between gap-3 py-3 text-sm"
                            @if ($index > 2) x-show="historyOpen" x-cloak @endif
                        >
                            <span class="text-ink-muted">
                                {{ $intent->paid_at?->translatedFormat('j M Y') ?? $intent->created_at?->translatedFormat('j M Y') }}
                                · {{ $intent->meta['plan_name'] ?? $intent->plan_key }}
                            </span>
                            <span class="shrink-0 font-semibold">
                                {{ $intent->currency }} {{ number_format($intent->amount) }}
                                · {{ $intent->status === 'paid' ? __('loop.paid') : __('loop.pending') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
                @if ($history->count() > 3)
                    <button type="button" class="mt-2 text-sm font-semibold text-violet" @click="historyOpen = ! historyOpen" x-text="historyOpen ? @js(__('loop.see_less')) : @js(__('loop.see_all_payments'))"></button>
                @endif
            </section>
        @endif

        <div class="loop-sticky-cta sm:hidden">
            <a class="loop-btn w-full justify-center" :href="payHref()" x-text="payCta({{ ! empty($paused) ? 'true' : 'false' }}, @js(__('loop.reactivate_loop')))"></a>
        </div>
    </div>
</x-app-layout>
