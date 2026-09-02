@php
    $availableOffers = $availableOffers ?? (($customer && $membership) ? $membership->availableRewards() : collect());
    $raffleWins = $raffleWins ?? collect();
    $offerCards = $offerCards ?? [];
    $hasRedeemable = $availableOffers->isNotEmpty() || $raffleWins->isNotEmpty();
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

    @if (! empty($loopPaused))
        <div class="mx-auto max-w-xl rounded-[1.5rem] border border-coral/30 bg-coral/10 px-5 py-5">
            <p class="font-display text-2xl font-semibold">{{ __('loop.loop_paused_till_title') }}</p>
            @if ($customer && $membership)
                <p class="mt-3 text-sm font-semibold">{{ __('loop.till_paused_customer', [
                    'name' => $customer->first_name ?: $customer->name,
                    'points' => number_format($membership->points_balance),
                ]) }}</p>
            @elseif ($customer)
                <p class="mt-3 text-sm font-semibold">{{ $customer->first_name ?: $customer->name }}</p>
            @endif
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.till_paused_body') }}</p>
            @if (auth()->user()?->isOwner())
                <a href="{{ route('billing.show') }}" class="loop-btn mt-5 inline-flex">{{ __('loop.reactivate_loop') }}</a>
            @endif
            <a href="{{ route('till.index') }}" class="mt-4 block text-sm font-semibold text-ink-muted">{{ __('loop.back') }}</a>
        </div>
    @elseif ($needsRegister)
        <div
            x-data="{ open: true, step: 1 }"
            x-effect="if (!open && step === 1) window.location.href = @js(route('till.index'))"
            class="mx-auto max-w-xl"
        >
            <x-loop-sheet show="open && step === 1" model="open" :title="__('loop.customer_not_on_loop_title')" lock-swipe="true">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-violet-soft text-violet">
                    <x-loop-icon name="customers" class="h-7 w-7" />
                </div>
                <p class="rounded-[1.25rem] bg-chalk px-4 py-3 text-center font-display text-xl font-semibold tabular-nums tracking-tight">{{ $country_code }} {{ $phone }}</p>
                <p class="mt-4 text-base leading-relaxed text-ink-muted">{{ __('loop.customer_not_on_loop_body', ['phone' => $country_code.' '.$phone]) }}</p>
                <button type="button" class="loop-btn mt-6 w-full" @click="step = 2; open = false">{{ __('loop.add_and_continue') }}</button>
                <a href="{{ route('till.index') }}" class="loop-btn-ghost mt-3 w-full">{{ __('loop.cancel') }}</a>
            </x-loop-sheet>
            <form
                method="POST"
                action="{{ route('till.register-customer') }}"
                x-show="step >= 2"
                x-cloak
                class="overflow-visible rounded-[2rem] border border-ink/10 bg-white shadow-[0_24px_70px_rgba(11,31,42,0.08)]"
                x-data="{ regStep: {{ $errors->hasAny(['birth_month', 'birth_day', 'gender', 'email']) ? 2 : 1 }} }"
                data-loop-quiet
            >
                @csrf
                <div class="space-y-4 p-6" x-show="regStep === 1">
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.first_name') }}</h2>
                    <div>
                        <label class="loop-label">{{ __('loop.first_name') }}</label>
                        <input name="first_name" value="{{ old('first_name') }}" class="loop-input text-lg" required autofocus autocomplete="given-name">
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.last_name') }} ({{ __('loop.optional') }})</label>
                        <input name="last_name" value="{{ old('last_name') }}" class="loop-input text-lg" autocomplete="family-name">
                    </div>
                    <button type="button" class="loop-btn w-full" @click="regStep = 2">{{ __('loop.continue') }}</button>
                    <a href="{{ route('till.index') }}" class="block text-center text-sm font-semibold text-ink-muted">{{ __('loop.cancel') }}</a>
                </div>

                <div class="space-y-4 p-6" x-show="regStep === 2" x-cloak>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.birthday') }}</h2>
                    <x-birthday-fields :month="old('birth_month', '')" :day="old('birth_day', '')" />
                    <x-gender-field :value="old('gender', '')" />
                    <div>
                        <label class="loop-label">{{ __('loop.email_optional') }}</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="loop-input" autocomplete="email">
                    </div>
                    <div class="flex gap-3">
                        <button type="button" class="loop-btn-ghost flex-1" @click="regStep = 1">{{ __('loop.back') }}</button>
                        <button class="loop-btn flex-1">{{ __('loop.add_and_continue') }}</button>
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
                raffleWinnerId: @js((string) old('raffle_winner_id', '')),
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
                <div class="mb-4 rounded-[1.5rem] bg-gradient-to-br from-ink to-ink-soft px-5 py-4 text-white">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p class="font-display text-2xl font-semibold">{{ $customer->name }}</p>
                            <p class="mt-1 font-display text-4xl font-semibold text-lime">
                                <x-count-up :value="$membership->points_balance" />
                                <span class="text-lg font-medium text-white/60">{{ __('loop.pts') }}</span>
                            </p>
                        </div>
                    </div>
                    @if ($hasRedeemable)
                        @php $readyOffer = $availableOffers->first(); @endphp
                        <p class="mt-3 text-sm font-semibold text-lime">🎁 {{ $readyOffer?->name }} · {{ __('loop.offer_available') }}</p>
                    @elseif ($nextOffer)
                        <p class="mt-3 text-sm font-semibold text-lime">
                            {{ __('loop.points_to_next', ['points' => number_format(max(0, $nextOffer->points_cost - $membership->redeemablePoints())), 'offer' => $nextOffer->name]) }}
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
                data-loop-quiet
            >
                @csrf
                <input type="hidden" name="shop_id" value="{{ $shop->id }}">
                <input type="hidden" name="country_code" value="{{ $country_code }}">
                <input type="hidden" name="phone" value="{{ $phone }}">
                <input type="hidden" name="channel" value="{{ $channel }}">
                <input type="hidden" name="reward_id" :value="rewardId">
                <input type="hidden" name="raffle_winner_id" :value="raffleWinnerId">
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

                        <button type="button" class="flex w-full flex-col rounded-3xl border p-5 text-left transition" :class="!rewardId && !raffleWinnerId ? 'border-mint ring-2 ring-mint/20' : 'border-ink/10 hover:border-mint'" @click="pickOffer('')">
                            <p class="font-display text-lg font-semibold">{{ __('loop.till_just_sale') }}</p>
                            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.till_just_sale_body') }}</p>
                        </button>

                        @foreach ($raffleWins ?? [] as $win)
                            <button type="button" class="flex w-full flex-col rounded-3xl border p-5 text-left transition" :class="String(raffleWinnerId) === @js((string) $win->id) ? 'border-mint ring-2 ring-mint/20' : 'border-ink/10 hover:border-mint'" @click="pickOffer(@js((string) $win->id), 'raffle')">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="font-display text-lg font-semibold">{{ $win->raffle->prize_name }}</p>
                                    <span class="shrink-0 rounded-lg bg-lime/40 px-2.5 py-1 text-xs font-semibold text-ink">{{ __('loop.raffle') }}</span>
                                </div>
                                <p class="mt-1 text-sm text-ink-muted">{{ $win->raffle->name }}</p>
                                <p class="mt-2 text-sm font-semibold text-mint-deep">{{ __('loop.till_raffle_hint') }}</p>
                            </button>
                        @endforeach

                        @foreach ($availableOffers as $reward)
                            <button type="button" class="flex w-full flex-col rounded-3xl border p-5 text-left transition" :class="String(rewardId) === @js((string) $reward->id) ? 'border-mint ring-2 ring-mint/20' : 'border-ink/10 hover:border-mint'" @click="pickOffer(@js((string) $reward->id))">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="font-display text-lg font-semibold">{{ $reward->name }}</p>
                                    <span class="shrink-0 rounded-lg bg-mint-soft px-2.5 py-1 text-xs font-semibold text-ink">{{ number_format((int) $reward->points_cost) }} {{ __('loop.pts') }}</span>
                                </div>
                                <p class="mt-1 text-sm text-ink-muted">{{ $reward->label() }}</p>
                                <p class="mt-2 text-sm font-semibold text-mint-deep">
                                    {{ $reward->isFreeRedeem() ? __('loop.till_free_item_hint') : __('loop.till_discount_hint') }}
                                </p>
                            </button>
                        @endforeach

                        @if ($nextOffer)
                            <p class="text-sm font-semibold text-mint-deep">
                                {{ __('loop.points_to_next', ['points' => number_format(max(0, $nextOffer->points_cost - $membership->points_balance)), 'offer' => $nextOffer->name]) }}
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
                        <p>{{ __('loop.till_offer_takes_off') }}: <span class="font-semibold" x-text="currency + ' ' + window.loopNumber.format(discount(), window.loopNumber.decimalsFor(currency))"></span></p>
                        <p class="mt-1 font-display text-lg font-semibold">{{ __('loop.till_they_pay') }}: <span x-text="currency + ' ' + window.loopNumber.format(remaining(), window.loopNumber.decimalsFor(currency))"></span></p>
                    </div>

                    <div x-show="hasExtraPurchase()" x-cloak class="rounded-2xl border border-mint/30 bg-mint-soft/40 px-4 py-3 text-sm">
                        <p>{{ __('loop.till_they_pay') }}: <span class="font-display text-lg font-semibold" x-text="currency + ' ' + window.loopNumber.format(remaining(), window.loopNumber.decimalsFor(currency))"></span></p>
                    </div>

                    @if (($productPushes ?? collect())->isNotEmpty())
                        <div class="space-y-2">
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
                        <div x-show="!rewardId && !raffleWinnerId" x-cloak class="rounded-2xl border border-ink/10 bg-chalk/50 p-4 space-y-3">
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
