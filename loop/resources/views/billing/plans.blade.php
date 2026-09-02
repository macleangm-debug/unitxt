<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start gap-3">
            <x-back-icon :href="route('billing.show', ['months' => $selectedMonths])" />
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.billing_eyebrow') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.choose_loop_right') }}</h1>
                <p class="mt-1 max-w-xl text-ink-muted">{{ __('loop.choose_loop_right_blurb') }}</p>
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
            pickedKey: @js($payPlanKey),
            currentKey: @js($business->plan_key),
            planPrices: @js($planPrices),
            planNames: @js($planNames),
            payUrl: @js(route('payments.show')),
            plansUrl: @js(route('billing.plans')),
            fromPlans: true,
            untils: @js($untils),
            monthLabels: @js($monthLabels),
            saveTemplate: @js(__('loop.interval_save_money')),
            payTemplate: @js(__('loop.billing_pay_cta')),
            paySaveTemplate: @js(__('loop.billing_pay_cta_save')),
            continueLabel: @js(__('loop.continue_to_payment')),
            upgradeTemplate: @js(__('loop.upgrade_to_plan')),
            regularLabel: @js(__('loop.regular_price')),
        })"
    >
        <section>
            <p class="text-sm font-semibold">{{ __('loop.pay_for_months') }}</p>
            <div class="mt-3">
                <x-billing-month-pills :discounts="$discounts" />
            </div>
            <p class="mt-3 text-sm text-ink-muted">
                <span x-text="monthLabel()"></span>
                <span class="ml-2 text-xs font-bold uppercase tracking-[0.12em] text-mint-deep" x-show="months === 12" x-cloak>{{ __('loop.interval_best_value') }}</span>
            </p>
        </section>

        <div class="space-y-4">
            @foreach ($plans->where('key', '!=', 'free') as $plan)
                @php
                    $isCurrent = $business->plan_key === $plan->key && $business->billing_status === 'active';
                    $isScale = $plan->key === 'scale';
                    $isGrowth = $plan->key === 'growth';
                @endphp
                <button
                    type="button"
                    @click="setPlan(@js($plan->key))"
                    :class="pickedKey === @js($plan->key) ? 'border-violet ring-2 ring-violet/20 bg-violet-soft/30' : 'border-ink/10 bg-white'"
                    class="w-full rounded-[1.75rem] border p-6 text-left transition"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            @if ($isCurrent)
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.your_current_plan_badge') }}</p>
                            @elseif ($isGrowth)
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.most_popular') }}</p>
                            @endif
                            <p class="mt-1 font-display text-2xl font-semibold">{{ $plan->name }}</p>
                            <p class="mt-1 text-sm text-ink-muted">{{ $plan->tagline }}</p>
                        </div>
                        <p class="shrink-0 font-display text-lg font-semibold" x-text="priceFor(@js($plan->key))"></p>
                    </div>

                    @if ($isScale)
                        <p class="mt-4 text-sm font-semibold">{{ __('loop.everything_in_growth') }}</p>
                        <ul class="mt-2 space-y-1.5 text-sm text-ink-muted">
                            <li>🎡 {{ __('loop.games_wins') }}</li>
                            <li>🎟 {{ __('loop.raffles') }}</li>
                            <li>{{ __('loop.scale_engagement') }}</li>
                            <li>{{ __('loop.scale_premium') }}</li>
                        </ul>
                    @endif
                    <ul class="mt-4 space-y-1.5 text-sm text-ink-muted">
                        @foreach ($plan->features ?? [] as $feature)
                            <li>✓ {{ $feature }}</li>
                        @endforeach
                    </ul>
                </button>
            @endforeach
        </div>

        <section class="loop-panel p-6 sm:p-8 ring-2 ring-violet/15">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet" x-text="pickedSummaryTitle()"></p>
            <template x-if="isUpgrade()">
                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs text-ink-muted">{{ __('loop.your_current_plan') }}</p>
                        <p class="font-semibold">{{ $currentPlan?->name ?? $business->plan_key }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-muted">{{ __('loop.your_new_plan') }}</p>
                        <p class="font-semibold" x-text="pickedName()"></p>
                    </div>
                </div>
            </template>
            <p class="mt-4 text-sm text-ink-muted" x-text="regularPriceLine()"></p>
            <p class="mt-2 text-sm font-semibold text-mint-deep" x-show="quoteFor(pickedKey).save > 0" x-cloak x-text="saveLabelFor(pickedKey)"></p>
            <p class="mt-3 font-display text-4xl font-semibold" x-text="money(quoteFor(pickedKey).amount)"></p>
            <p class="mt-2 text-sm text-ink-muted">
                {{ __('loop.new_expiry') }}
                <span class="font-semibold text-ink" x-text="untilLabel()"></span>
            </p>
            <a class="loop-btn mt-6 hidden w-full sm:inline-flex" :href="payHref(pickedKey)" x-text="planCta()"></a>
        </section>

        <div class="loop-sticky-cta sm:hidden">
            <a class="loop-btn w-full justify-center" :href="payHref(pickedKey)" x-text="payCta(false, planCta())"></a>
        </div>
    </div>
</x-app-layout>
