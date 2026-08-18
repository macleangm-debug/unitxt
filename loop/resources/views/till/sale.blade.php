@php
    $availableOffers = $availableOffers ?? (($customer && $membership) ? $membership->availableRewards() : collect());
    $offerCards = $offerCards ?? [];
    $hasRedeemable = $availableOffers->isNotEmpty();
    $needsRegister = $needsRegister ?? false;
    $payEnabled = $business->payWithPointsEnabled();
    $payRate = $business->payCurrencyPerPoint();
    $payMaxPercent = $business->payPointsMaxPercent();
    $initialStep = (int) ($initialStep ?? 1);
    if ($hasRedeemable && $errors->hasAny(['amount_spent', 'points_to_spend'])) {
        $initialStep = 2;
    }
    $tillSteps = $hasRedeemable
        ? [1 => __('loop.till_step_offer'), 2 => __('loop.till_step_bill')]
        : [];
    $oldReward = old('reward_id', '');
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.sale') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">
                @if ($customer)
                    {{ $customer->name }}
                @else
                    {{ __('loop.new_customer') }}
                @endif
            </h1>
            <p class="mt-1 text-ink-muted">
                {{ $country_code }} {{ $phone }} · {{ $shop->name }}
                · {{ $channel === 'phone_order' ? __('loop.phone_order') : __('loop.in_store') }}
            </p>
        </div>
    </x-slot>

    @if ($needsRegister)
        <div
            x-data="{ open: true, step: 1 }"
            class="mx-auto max-w-xl"
        >
            <template x-teleport="body">
                <div
                    x-show="open && step === 1"
                    x-cloak
                    class="fixed inset-0 z-[80] flex items-center justify-center p-4"
                    @keydown.escape.window="open = false"
                >
                    <div class="absolute inset-0 bg-ink/60 backdrop-blur-sm"></div>
                    <div class="relative w-full max-w-md rounded-[2rem] bg-white p-8 text-center shadow-[0_40px_100px_rgba(17,17,20,0.35)] sm:p-10">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-violet-soft text-2xl text-violet">?</div>
                        <p class="mt-5 font-display text-2xl font-bold sm:text-3xl">{{ __('loop.customer_not_on_loop_title') }}</p>
                        <p class="mt-3 text-base text-ink-muted">{{ __('loop.customer_not_on_loop_body', ['phone' => $country_code.' '.$phone]) }}</p>
                        <button type="button" class="loop-btn mt-7 w-full" @click="step = 2">{{ __('loop.register_this_customer') }}</button>
                        <a href="{{ route('till.index') }}" class="mt-3 block text-sm font-semibold text-ink-muted">{{ __('loop.cancel') }}</a>
                    </div>
                </div>
            </template>

            <form
                method="POST"
                action="{{ route('till.register-customer') }}"
                x-show="step >= 2"
                x-cloak
                class="overflow-hidden rounded-[2rem] border border-ink/10 bg-white shadow-[0_24px_70px_rgba(11,31,42,0.08)]"
                x-data="{ regStep: 1 }"
            >
                @csrf
                <div class="space-y-4 p-6" x-show="regStep === 1">
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.register_customer_heading') }}</h2>
                    <div>
                        <label class="loop-label">{{ __('loop.first_name') }}</label>
                        <input name="first_name" value="{{ old('first_name') }}" class="loop-input text-lg" required>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.last_name') }}</label>
                        <input name="last_name" value="{{ old('last_name') }}" class="loop-input text-lg" required>
                    </div>
                    <button type="button" class="loop-btn w-full" @click="regStep = 2">{{ __('loop.next') }}</button>
                </div>

                <div class="space-y-4 p-6" x-show="regStep === 2" x-cloak>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.birthday_short') }}</h2>
                    <p class="text-sm text-ink-muted">{{ __('loop.birthday_premium_hint') }}</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-sheet-select
                            name="birth_month"
                            :label="__('loop.month')"
                            :options="collect(range(1,12))->mapWithKeys(fn ($m) => [$m => $m])->all()"
                            :value="old('birth_month', '')"
                        />
                        <x-sheet-select
                            name="birth_day"
                            :label="__('loop.day')"
                            :options="collect(range(1,31))->mapWithKeys(fn ($d) => [$d => $d])->all()"
                            :value="old('birth_day', '')"
                        />
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.email_optional') }}</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="loop-input">
                    </div>
                    <div class="flex gap-3">
                        <button type="button" class="loop-btn-ghost flex-1" @click="regStep = 1">{{ __('loop.back') }}</button>
                        <button class="loop-btn flex-1">{{ __('loop.finish_registration') }}</button>
                    </div>
                </div>
            </form>
        </div>
    @else
        <div
            class="mx-auto max-w-xl"
            x-data="tillWizard({
                step: {{ $initialStep }},
                hasOffers: {{ $hasRedeemable ? 'true' : 'false' }},
                rewardId: @js((string) $oldReward),
                offers: @js($offerCards),
                amountDisplay: @js(old('amount_spent', '')),
                currency: @js($business->currency),
                amountRequired: @js(__('loop.amount_required')),
                giveButton: @js(__('loop.till_give_button')),
                giveAndCollect: @js(__('loop.till_give_and_collect')),
                collectRemaining: @js(__('loop.till_collect_remaining')),
                completeSale: @js(__('loop.complete_sale')),
                payWithPoints: {{ old('pay_with_points') ? 'true' : 'false' }},
                pointsToSpend: @js((string) old('points_to_spend', '')),
                balance: {{ (int) ($membership->points_balance ?? 0) }},
                rate: {{ (float) $payRate }},
                maxPercent: {{ (int) $payMaxPercent }},
            })"
        >
            @if ($customer && $membership)
                <div class="mb-4 flex items-end justify-between gap-4 rounded-[1.5rem] bg-gradient-to-br from-ink to-ink-soft px-5 py-4 text-white">
                    <div>
                        <p class="text-xs text-white/70">{{ __('loop.balance') }}</p>
                        <p class="mt-1 font-display text-4xl font-semibold">{{ $membership->points_balance }} <span class="text-lg font-medium text-white/60">{{ __('loop.pts') }}</span></p>
                    </div>
                    @if (! $hasRedeemable && $nextOffer)
                        <p class="max-w-[12rem] text-right text-sm font-semibold text-lime">
                            {{ __('loop.points_to_next', ['points' => max(0, $nextOffer->points_cost - $membership->points_balance), 'offer' => $nextOffer->name]) }}
                        </p>
                    @endif
                </div>
            @endif

            @if ($tillSteps !== [])
                <x-form-stepper :steps="$tillSteps" />
            @endif

            <form
                x-ref="form"
                method="POST"
                action="{{ route('till.store') }}"
                class="overflow-hidden rounded-[2rem] border border-ink/10 bg-white shadow-[0_24px_70px_rgba(11,31,42,0.08)]"
                @submit="submitForm($event)"
            >
                @csrf
                <input type="hidden" name="shop_id" value="{{ $shop->id }}">
                <input type="hidden" name="country_code" value="{{ $country_code }}">
                <input type="hidden" name="phone" value="{{ $phone }}">
                <input type="hidden" name="channel" value="{{ $channel }}">
                <input type="hidden" name="reward_id" :value="rewardId">
                <input type="hidden" name="amount_spent" :value="amountValue()">

                @if ($errors->any())
                    <div class="mx-6 mt-6 rounded-xl border border-coral/30 bg-coral/10 px-4 py-3 text-sm text-ink" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if ($hasRedeemable)
                    <div data-step="1" x-show="step === 1" class="space-y-4 p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">1 · {{ __('loop.till_step_offer') }}</p>
                        <h2 class="font-display text-xl font-semibold">{{ __('loop.till_ask_title') }}</h2>
                        <p class="text-sm text-ink-muted">{{ __('loop.till_ask_body') }}</p>

                        <button type="button" class="flex w-full flex-col rounded-3xl border p-5 text-left transition" :class="!rewardId ? 'border-mint ring-2 ring-mint/20' : 'border-ink/10 hover:border-mint'" @click="pickOffer('')">
                            <p class="font-display text-lg font-semibold">{{ __('loop.till_just_sale') }}</p>
                            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.till_just_sale_body') }}</p>
                        </button>

                        @foreach ($availableOffers as $reward)
                            <button type="button" class="flex w-full flex-col rounded-3xl border p-5 text-left transition" :class="String(rewardId) === @js((string) $reward->id) ? 'border-mint ring-2 ring-mint/20' : 'border-ink/10 hover:border-mint'" @click="pickOffer(@js((string) $reward->id))">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="font-display text-lg font-semibold">{{ $reward->name }}</p>
                                    <span class="shrink-0 rounded-lg bg-mint-soft px-2.5 py-1 text-xs font-semibold text-ink">{{ $reward->points_cost }} {{ __('loop.pts') }}</span>
                                </div>
                                <p class="mt-1 text-sm text-ink-muted">{{ $reward->label() }}</p>
                                <p class="mt-2 text-sm font-semibold text-mint-deep">
                                    {{ $reward->isFreeRedeem() ? __('loop.till_free_item_hint') : __('loop.till_discount_hint') }}
                                </p>
                            </button>
                        @endforeach

                        @if ($nextOffer)
                            <p class="text-sm font-semibold text-mint-deep">
                                {{ __('loop.points_to_next', ['points' => max(0, $nextOffer->points_cost - $membership->points_balance), 'offer' => $nextOffer->name]) }}
                            </p>
                        @endif

                        <button type="button" class="loop-btn-mint w-full" @click="next()">{{ __('loop.continue') }}</button>
                    </div>
                @endif

                <div data-step="{{ $hasRedeemable ? 2 : 1 }}" x-show="step === {{ $hasRedeemable ? 2 : 1 }}" @if ($hasRedeemable) x-cloak @endif class="space-y-5 p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">
                        {{ $hasRedeemable ? '2 · ' : '' }}<span x-text="isFreeItem() ? @js(__('loop.till_step_give')) : @js(__('loop.till_step_bill'))"></span>
                    </p>

                    <div x-show="isFreeItem()" x-cloak class="space-y-2">
                        <h2 class="font-display text-xl font-semibold">{{ __('loop.till_give_title') }}</h2>
                        <p class="text-sm text-ink-muted">{{ __('loop.till_give_body') }}</p>
                        <p class="rounded-2xl bg-mint-soft/60 px-4 py-3 text-sm font-semibold" x-text="selectedOffer() ? selectedOffer().name : ''"></p>
                    </div>

                    <div>
                        <label class="loop-label">
                            <span x-show="!isFreeItem()">{{ __('loop.till_bill_label') }} ({{ $business->currency }})</span>
                            <span x-show="isFreeItem()" x-cloak>{{ __('loop.till_also_buying_label') }} ({{ $business->currency }})</span>
                        </label>
                        <input type="text" inputmode="decimal" x-ref="amountInput" x-model="amountDisplay" @input="formatAmount()" class="loop-input text-3xl font-display font-semibold" placeholder="0" data-amount-input>
                        <p x-show="isFreeItem()" x-cloak class="mt-2 text-xs text-ink-muted">{{ __('loop.till_also_buying_hint') }}</p>
                        @if ($campaign)
                            <p x-show="!isFreeItem() || amountValue() > 0" class="mt-2 text-xs text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
                        @endif
                        <x-input-error :messages="$errors->get('amount_spent')" class="mt-1" />
                    </div>

                    <div x-show="discount() > 0" x-cloak class="rounded-2xl border border-mint/30 bg-mint-soft/40 px-4 py-3 text-sm">
                        <p>{{ __('loop.till_offer_takes_off') }}: <span class="font-semibold" x-text="currency + ' ' + discount().toLocaleString()"></span></p>
                        <p class="mt-1 font-display text-lg font-semibold">{{ __('loop.till_they_pay') }}: <span x-text="currency + ' ' + remaining().toLocaleString()"></span></p>
                    </div>

                    <div x-show="hasExtraPurchase()" x-cloak class="rounded-2xl border border-mint/30 bg-mint-soft/40 px-4 py-3 text-sm">
                        <p>{{ __('loop.till_they_pay') }}: <span class="font-display text-lg font-semibold" x-text="currency + ' ' + remaining().toLocaleString()"></span></p>
                    </div>

                    @if (($productPushes ?? collect())->isNotEmpty())
                        <div x-show="showFeatured()" class="space-y-2">
                            <p class="text-xs text-ink-muted">{{ __('loop.auto_bonuses_till_hint') }}</p>
                            @foreach ($productPushes as $push)
                                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-violet/25 bg-violet-soft/40 px-4 py-3">
                                    <input type="checkbox" name="featured_campaign_ids[]" value="{{ $push->id }}" class="mt-0.5 rounded border-ink/20 text-violet focus:ring-violet" @checked(in_array($push->id, old('featured_campaign_ids', []), false))>
                                    <span>
                                        <span class="block text-sm font-semibold">{{ __('loop.featured_in_sale_q', ['product' => $push->featured_product_name]) }}</span>
                                        <span class="mt-1 block text-xs font-normal text-ink-muted">{{ __('loop.featured_in_sale_hint', ['points' => $push->bonus_points]) }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    @if ($membership && $membership->points_balance > 0 && $payEnabled)
                        <div x-show="!rewardId" x-cloak class="rounded-2xl border border-ink/10 bg-chalk/50 p-4 space-y-3">
                            <label class="flex items-start gap-3 text-sm font-semibold">
                                <input type="checkbox" name="pay_with_points" value="1" x-model="payWithPoints" class="mt-0.5 rounded border-ink/20 text-mint-deep focus:ring-mint-deep">
                                <span>
                                    {{ __('loop.pay_with_points') }}
                                    <span class="mt-1 block text-xs font-normal text-ink-muted">{{ __('loop.pay_with_points_till_help', [
                                        'rate' => number_format($payRate, 0),
                                        'currency' => $business->currency,
                                        'percent' => $payMaxPercent,
                                    ]) }}</span>
                                </span>
                            </label>
                            <div x-show="payWithPoints" x-cloak class="space-y-2">
                                <label class="loop-label">{{ __('loop.points_to_spend') }}</label>
                                <input type="number" name="points_to_spend" min="1" :max="maxPointsByPercent()" x-model="pointsToSpend" class="loop-input">
                                <p class="text-xs text-ink-muted">
                                    {{ __('loop.max') }}: <span x-text="maxPointsByPercent()"></span> {{ __('loop.pts') }}
                                    · ≈ <span x-text="currency + ' ' + pointsDiscount().toLocaleString()"></span>
                                </p>
                                <x-input-error :messages="$errors->get('points_to_spend')" class="mt-1" />
                            </div>
                        </div>
                    @endif

                    <button class="w-full" :class="isFreeItem() ? 'loop-btn-mint' : 'loop-btn'" x-text="submitLabel()"></button>

                    @if ($hasRedeemable)
                        <button type="button" class="loop-btn-ghost w-full" @click="go(1)">{{ __('loop.back') }}</button>
                    @endif
                    <a href="{{ route('till.index') }}" class="block text-center text-sm text-ink-muted underline">{{ __('loop.cancel') }}</a>
                </div>
            </form>
        </div>
    @endif
</x-app-layout>
