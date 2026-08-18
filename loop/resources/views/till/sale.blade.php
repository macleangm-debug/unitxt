@php
    $keep = $keep ?? false;
    $availableOffers = ($customer && $membership) ? $membership->availableRewards() : collect();
    $hasRedeemable = $availableOffers->isNotEmpty();
    $needsRegister = $needsRegister ?? false;
    $mode = $mode ?? 'sale';
    $payEnabled = $business->payWithPointsEnabled();
    $payRate = $business->payCurrencyPerPoint();
    $payMaxPercent = $business->payPointsMaxPercent();
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
            <div
                x-show="open && step === 1"
                x-cloak
                class="fixed inset-0 z-[80] flex items-center justify-center px-4"
            >
                <div class="absolute inset-0 bg-ink/60 backdrop-blur-sm"></div>
                <div class="relative w-full max-w-md rounded-[2rem] bg-white p-8 text-center shadow-2xl">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-violet-soft text-2xl text-violet">?</div>
                    <p class="mt-5 font-display text-2xl font-bold sm:text-3xl">{{ __('loop.customer_not_on_loop_title') }}</p>
                    <p class="mt-3 text-base text-ink-muted">{{ __('loop.customer_not_on_loop_body', ['phone' => $country_code.' '.$phone]) }}</p>
                    <button type="button" class="loop-btn mt-7 w-full" @click="step = 2">{{ __('loop.register_this_customer') }}</button>
                    <a href="{{ route('till.index') }}" class="mt-3 block text-sm font-semibold text-ink-muted">{{ __('loop.cancel') }}</a>
                </div>
            </div>

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
                            :placeholder="__('loop.month')"
                        />
                        <x-sheet-select
                            name="birth_day"
                            :label="__('loop.day')"
                            :options="collect(range(1,31))->mapWithKeys(fn ($d) => [$d => $d])->all()"
                            :value="old('birth_day', '')"
                            :placeholder="__('loop.day')"
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
        <div x-data="{ saveForBigger: {{ $keep ? 'true' : 'false' }} }">
        @if ($customer && $membership)
            <div class="mb-6 grid gap-3 sm:grid-cols-3">
                <div class="rounded-[1.5rem] bg-gradient-to-br from-ink to-ink-soft p-5 text-white">
                    <p class="text-sm text-white/70">{{ __('loop.balance') }}</p>
                    <p class="mt-2 font-display text-4xl font-semibold">{{ $membership->points_balance }}</p>
                    <p class="text-xs text-white/55">{{ __('loop.pts') }} · {{ __('loop.one_wallet_hint') }}</p>
                </div>
                <div class="loop-panel p-5 sm:col-span-2">
                    @if ($hasRedeemable && $mode !== 'redeem')
                        <div x-show="!saveForBigger">
                            <p class="font-display text-lg font-semibold">{{ __('loop.ask_redeem_title') }}</p>
                            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.ask_redeem_body') }}</p>
                            <p class="mt-4 text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.ready_now') }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($availableOffers as $reward)
                                    <span class="rounded-xl bg-mint-soft px-3 py-1.5 text-sm font-semibold text-mint-deep">{{ $reward->name }} · {{ $reward->points_cost }} {{ __('loop.pts') }}</span>
                                @endforeach
                            </div>
                            @if ($nextOffer)
                                <p class="mt-4 text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.bigger_later') }}</p>
                                <p class="mt-1 text-sm font-semibold text-mint-deep">
                                    {{ __('loop.points_to_next', ['points' => max(0, $nextOffer->points_cost - $membership->points_balance), 'offer' => $nextOffer->name]) }}
                                </p>
                            @endif
                            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                <a href="{{ route('till.ticket', ['mode' => 'redeem']) }}" class="loop-btn-mint text-center">{{ __('loop.want_to_redeem') }}</a>
                                <button type="button" class="loop-btn-ghost" @click="saveForBigger = true">{{ __('loop.keep_earning') }}</button>
                            </div>
                            <p class="mt-2 text-xs text-ink-muted">{{ __('loop.keep_earning_body') }}</p>
                        </div>
                        <div x-show="saveForBigger" x-cloak>
                            <p class="font-semibold text-mint-deep">
                                @if ($nextOffer)
                                    {{ __('loop.save_for_bigger_banner', ['points' => max(0, $nextOffer->points_cost - $membership->points_balance), 'offer' => $nextOffer->name]) }}
                                @else
                                    {{ __('loop.save_for_bigger_banner_plain') }}
                                @endif
                            </p>
                            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.keep_earning_body') }}</p>
                            <a href="{{ route('till.ticket', ['mode' => 'redeem']) }}" class="mt-3 inline-block text-sm font-semibold text-mint-deep underline">{{ __('loop.want_to_redeem') }}</a>
                        </div>
                    @elseif ($hasRedeemable)
                        <p class="text-sm font-semibold">{{ __('loop.ready_to_redeem') }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($availableOffers as $reward)
                                <span class="rounded-xl bg-mint-soft px-3 py-1.5 text-sm font-semibold text-mint-deep">{{ $reward->name }} · {{ $reward->points_cost }} {{ __('loop.pts') }}</span>
                            @endforeach
                        </div>
                        @if ($nextOffer)
                            <p class="mt-3 text-sm font-semibold text-mint-deep">
                                {{ __('loop.points_to_next', ['points' => max(0, $nextOffer->points_cost - $membership->points_balance), 'offer' => $nextOffer->name]) }}
                            </p>
                        @endif
                    @else
                        <p class="text-sm font-semibold">{{ __('loop.ready_to_redeem') }}</p>
                        <div class="mt-3 w-full rounded-2xl border border-dashed border-coral/40 bg-coral/10 px-4 py-4">
                            <p class="font-display text-lg font-semibold text-ink">{{ __('loop.none_unlocked') }}</p>
                            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.none_unlocked_hint') }}</p>
                            @if ($nextOffer)
                                <p class="mt-3 text-sm font-semibold text-mint-deep">
                                    {{ __('loop.points_to_next', ['points' => max(0, $nextOffer->points_cost - $membership->points_balance), 'offer' => $nextOffer->name]) }}
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if ($customer)
            <div class="mx-auto mb-4 grid max-w-xl grid-cols-2 gap-2">
                <a href="{{ route('till.ticket', ['mode' => 'sale']) }}"
                   class="rounded-2xl border px-3 py-3 text-center text-sm font-semibold {{ $mode === 'sale' ? 'border-mint-deep bg-mint-soft/50' : 'border-ink/10 bg-white' }}">
                    {{ __('loop.mode_sale') }}
                </a>
                <a href="{{ route('till.ticket', ['mode' => 'redeem']) }}"
                   class="rounded-2xl border px-3 py-3 text-center text-sm font-semibold {{ $mode === 'redeem' ? 'border-mint-deep bg-mint-soft/50' : 'border-ink/10 bg-white' }}">
                    {{ __('loop.mode_redeem') }}
                </a>
            </div>
        @endif

        @if ($mode === 'redeem' && $customer)
            <form method="POST" action="{{ route('till.redeem') }}"
                  class="mx-auto max-w-xl space-y-5 overflow-hidden rounded-[2rem] border border-ink/10 bg-white p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)]"
                  x-data="{ selectedOffer: '{{ old('reward_id', '') }}' }">
                @csrf
                <input type="hidden" name="shop_id" value="{{ $shop->id }}">
                <input type="hidden" name="country_code" value="{{ $country_code }}">
                <input type="hidden" name="phone" value="{{ $phone }}">
                <input type="hidden" name="reward_id" :value="selectedOffer">

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.mode_redeem') }}</p>
                    <h2 class="mt-1 font-display text-xl font-semibold">{{ __('loop.redeem_pick_title') }}</h2>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.redeem_pick_body') }}</p>
                </div>

                @if ($hasRedeemable)
                    <div class="space-y-2">
                        @foreach ($availableOffers as $reward)
                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-ink/10 bg-white px-4 py-3 has-[:checked]:border-mint-deep has-[:checked]:bg-mint-soft/40">
                                <input type="radio" value="{{ $reward->id }}" class="mt-1 text-mint-deep focus:ring-mint-deep" x-model="selectedOffer" required>
                                <span>
                                    <span class="block text-sm font-semibold">{{ $reward->name }}</span>
                                    <span class="mt-0.5 block text-xs text-ink-muted">{{ $reward->points_cost }} {{ __('loop.pts') }} · {{ $reward->label() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.redeem_note_label') }}</label>
                        <input name="notes" class="loop-input" value="{{ old('notes') }}" placeholder="{{ __('loop.redeem_note_placeholder') }}">
                        <p class="mt-1 text-xs text-ink-muted">{{ __('loop.redeem_note_help') }}</p>
                    </div>
                    <x-input-error :messages="$errors->get('reward_id')" class="mt-1" />
                    <button class="loop-btn-mint w-full" name="continue_to_sale" value="1">{{ __('loop.redeem_and_sale') }}</button>
                    <button class="loop-btn-ghost w-full" type="submit">{{ __('loop.redeem_only') }}</button>
                    <a href="{{ route('till.ticket', ['mode' => 'sale', 'keep' => 1]) }}" class="block text-center text-sm font-semibold text-mint-deep">{{ __('loop.skip_for_bigger') }}</a>
                @else
                    <p class="rounded-2xl border border-dashed border-ink/15 px-4 py-6 text-sm text-ink-muted">{{ __('loop.none_unlocked_hint') }}</p>
                    <a href="{{ route('till.ticket', ['mode' => 'sale']) }}" class="loop-btn w-full text-center">{{ __('loop.mode_sale') }}</a>
                @endif
                <a href="{{ route('till.index') }}" class="block text-center text-sm text-ink-muted underline">{{ __('loop.cancel') }}</a>
            </form>
        @else
            <form method="POST" action="{{ route('till.store') }}"
                  class="mx-auto max-w-xl overflow-hidden rounded-[2rem] border border-ink/10 bg-white shadow-[0_24px_70px_rgba(11,31,42,0.08)]"
                  x-data="{
                    amountDisplay: '{{ old('amount_spent') }}',
                    payWithPoints: {{ old('pay_with_points') ? 'true' : 'false' }},
                    pointsToSpend: '{{ old('points_to_spend', '') }}',
                    balance: {{ $membership->points_balance ?? 0 }},
                    rate: {{ $payRate }},
                    maxPercent: {{ $payMaxPercent }},
                    currency: @js($business->currency),
                    formatAmount() {
                        let raw = String(this.amountDisplay).replace(/[^\d.]/g, '');
                        const parts = raw.split('.');
                        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                        this.amountDisplay = parts.join('.');
                    },
                    amountValue() { return parseFloat(String(this.amountDisplay).replace(/,/g, '')) || 0; },
                    maxPointsByPercent() {
                        if (!this.rate || !this.amountValue()) return this.balance;
                        const maxCurrency = this.amountValue() * (this.maxPercent / 100);
                        return Math.min(this.balance, Math.floor(maxCurrency / this.rate));
                    },
                    pointsValue() { return Math.min(parseInt(this.pointsToSpend || 0, 10) || 0, this.maxPointsByPercent()); },
                    pointsDiscount() { return Math.round(this.pointsValue() * this.rate); }
                  }">
                @csrf
                <input type="hidden" name="shop_id" value="{{ $shop->id }}">
                <input type="hidden" name="country_code" value="{{ $country_code }}">
                <input type="hidden" name="phone" value="{{ $phone }}">
                <input type="hidden" name="channel" value="{{ $channel }}">
                <input type="hidden" name="amount_spent" :value="amountValue()">

                <div class="space-y-5 p-6">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.mode_sale') }}</p>
                        <label class="loop-label mt-2">{{ __('loop.amount_spent') }} ({{ $business->currency }})</label>
                        <input type="text" inputmode="decimal" x-model="amountDisplay" @input="formatAmount()" class="loop-input text-3xl font-display font-semibold" placeholder="0">
                        @if ($campaign)
                            <p class="mt-2 text-xs text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
                        @endif
                        <p class="mt-2 text-xs text-ink-muted">{{ __('loop.sale_after_redeem_hint') }}</p>
                        <x-input-error :messages="$errors->get('amount_spent')" class="mt-1" />
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
                        <div class="rounded-2xl border border-ink/10 bg-chalk/50 p-4 space-y-3">
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

                    <button class="loop-btn w-full">{{ __('loop.complete_sale') }}</button>
                    <a href="{{ route('till.index') }}" class="block text-center text-sm text-ink-muted underline">{{ __('loop.cancel') }}</a>
                </div>
            </form>
        @endif
        </div>
    @endif
</x-app-layout>
