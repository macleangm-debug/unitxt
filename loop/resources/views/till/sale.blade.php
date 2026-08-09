@php
    $availableOffers = ($customer && $membership) ? $membership->availableRewards() : collect();
    $hasRedeemable = $availableOffers->isNotEmpty();
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

    @if ($customer && $membership)
        <div class="mb-6 grid gap-3 sm:grid-cols-3 animate-fade-up">
            <div class="rounded-[1.5rem] bg-gradient-to-br from-ink to-ink-soft p-5 text-white">
                <p class="text-sm text-white/70">{{ __('loop.balance') }}</p>
                <p class="mt-2 font-display text-4xl font-semibold">{{ $membership->points_balance }}</p>
                <p class="text-xs text-white/55">{{ __('loop.pts') }}</p>
            </div>
            <div class="loop-panel p-5 sm:col-span-2">
                <p class="text-sm font-semibold">{{ __('loop.ready_to_redeem') }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse ($availableOffers as $reward)
                        <span class="rounded-xl bg-mint-soft px-3 py-1.5 text-sm font-semibold text-ink">{{ $reward->name }} · {{ $reward->points_cost }} {{ __('loop.pts') }}</span>
                    @empty
                        <div class="w-full rounded-2xl border border-dashed border-coral/40 bg-coral/10 px-4 py-4">
                            <p class="font-display text-lg font-semibold text-ink">{{ __('loop.none_unlocked') }}</p>
                            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.none_unlocked_hint') }}</p>
                            @if ($nextOffer)
                                <p class="mt-3 text-sm font-semibold text-mint-deep">
                                    {{ __('loop.points_to_next', ['points' => max(0, $nextOffer->points_cost - $membership->points_balance), 'offer' => $nextOffer->name]) }}
                                </p>
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('till.store') }}"
          class="mx-auto max-w-xl overflow-hidden rounded-[2rem] border border-ink/10 bg-white/90 shadow-[0_24px_70px_rgba(11,31,42,0.08)]"
          x-data="{
            step: 1,
            amountDisplay: '{{ old('amount_spent') }}',
            wantRedeem: {{ old('reward_id') ? 'true' : 'false' }},
            selectedOffer: '{{ old('reward_id', '') }}',
            payWithPoints: {{ old('pay_with_points') ? 'true' : 'false' }},
            pointsToSpend: '{{ old('points_to_spend', '') }}',
            balance: {{ $membership->points_balance ?? 0 }},
            rate: {{ $campaign?->currencyPerPoint() ?? 0 }},
            currency: @js($business->currency),
            formatAmount() {
                let raw = String(this.amountDisplay).replace(/[^\d.]/g, '');
                const parts = raw.split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                this.amountDisplay = parts.join('.');
            },
            amountValue() { return String(this.amountDisplay).replace(/,/g, ''); },
            pointsValue() { return Math.min(parseInt(this.pointsToSpend || 0, 10) || 0, this.balance); },
            pointsDiscount() { return Math.round(this.pointsValue() * this.rate); }
          }">
        @csrf
        <input type="hidden" name="shop_id" value="{{ $shop->id }}">
        <input type="hidden" name="country_code" value="{{ $country_code }}">
        <input type="hidden" name="phone" value="{{ $phone }}">
        <input type="hidden" name="channel" value="{{ $channel }}">
        <input type="hidden" name="amount_spent" :value="amountValue()">

        @unless ($customer)
            <div class="border-b border-ink/5 bg-gradient-to-r from-mint/15 to-transparent px-6 py-4">
                <p class="text-sm font-semibold">{{ __('loop.new_customer_hint') }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.step') }} <span x-text="step"></span>/3</p>
            </div>

            <div class="space-y-4 p-6" x-show="step === 1">
                <div>
                    <label class="loop-label">{{ __('loop.first_name') }}</label>
                    <input name="first_name" value="{{ old('first_name') }}" class="loop-input text-lg" required>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.last_name') }}</label>
                    <input name="last_name" value="{{ old('last_name') }}" class="loop-input text-lg" required>
                </div>
                <button type="button" class="loop-btn-mint w-full" @click="step = 2">{{ __('loop.next') }}</button>
            </div>

            <div class="space-y-4 p-6" x-show="step === 2" x-cloak>
                <p class="font-display text-xl font-semibold">{{ __('loop.birthday_for_campaigns') }}</p>
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
                    <button type="button" class="loop-btn-ghost flex-1" @click="step = 1">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn flex-1" @click="step = 3">{{ __('loop.next') }}</button>
                </div>
            </div>
        @endunless

        <div class="space-y-5 p-6" @unless($customer) x-show="step === 3" x-cloak @endunless>
            <div>
                <label class="loop-label">{{ __('loop.amount_spent') }} ({{ $business->currency }})</label>
                <input type="text" inputmode="decimal" x-model="amountDisplay" @input="formatAmount()" class="loop-input text-3xl font-display font-semibold" placeholder="0">
                @if ($campaign)
                    <p class="mt-2 text-xs text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
                @endif
                <x-input-error :messages="$errors->get('amount_spent')" class="mt-1" />
            </div>

            @if ($hasRedeemable)
                <div class="rounded-[1.5rem] border border-mint/30 bg-gradient-to-br from-mint/15 to-white p-5">
                    <p class="font-display text-lg font-semibold">{{ __('loop.ask_redeem_title') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.ask_redeem_body') }}</p>

                    <div class="mt-4 grid gap-2">
                        <button type="button"
                                class="rounded-2xl border px-4 py-3 text-left text-sm font-semibold transition"
                                :class="!wantRedeem ? 'border-mint bg-mint-soft/50' : 'border-ink/10 bg-white'"
                                @click="wantRedeem=false; selectedOffer=''">
                            {{ __('loop.keep_earning') }}
                            <span class="mt-1 block text-xs font-normal text-ink-muted">{{ __('loop.keep_earning_body') }}</span>
                        </button>
                        <button type="button"
                                class="rounded-2xl border px-4 py-3 text-left text-sm font-semibold transition"
                                :class="wantRedeem ? 'border-mint bg-mint-soft/50' : 'border-ink/10 bg-white'"
                                @click="wantRedeem=true">
                            {{ __('loop.want_to_redeem') }}
                            <span class="mt-1 block text-xs font-normal text-ink-muted">{{ __('loop.want_to_redeem_body') }}</span>
                        </button>
                    </div>

                    <div x-show="wantRedeem" x-cloak class="mt-4 space-y-2">
                        <input type="hidden" name="reward_id" :value="wantRedeem ? selectedOffer : ''">
                        @foreach ($availableOffers as $reward)
                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-ink/10 bg-white px-4 py-3 has-[:checked]:border-mint has-[:checked]:bg-mint-soft/40">
                                <input type="radio" value="{{ $reward->id }}" class="mt-1 text-mint focus:ring-mint" x-model="selectedOffer">
                                <span>
                                    <span class="block text-sm font-semibold">{{ $reward->name }}</span>
                                    <span class="mt-0.5 block text-xs text-ink-muted">{{ $reward->points_cost }} {{ __('loop.pts') }} · {{ $reward->label() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <template x-if="!wantRedeem">
                        <input type="hidden" name="reward_id" value="">
                    </template>
                </div>
            @else
                <input type="hidden" name="reward_id" value="">
            @endif

            @if ($membership && $membership->points_balance > 0 && $campaign && $campaign->currencyPerPoint() > 0)
                <div class="rounded-2xl border border-ink/10 bg-chalk/70 p-4">
                    <label class="flex items-start gap-3 text-sm font-semibold">
                        <input type="checkbox" name="pay_with_points" value="1" x-model="payWithPoints" class="mt-0.5 rounded border-ink/20 text-mint focus:ring-mint">
                        <span>
                            {{ __('loop.pay_with_points') }}
                            <span class="mt-1 block text-xs font-normal text-ink-muted">{{ __('loop.pay_with_points_hint', ['rate' => number_format($campaign->currencyPerPoint(), 0), 'currency' => $business->currency]) }}</span>
                        </span>
                    </label>
                    <div x-show="payWithPoints" x-cloak class="mt-3 space-y-2">
                        <label class="loop-label">{{ __('loop.points_to_spend') }} (max {{ $membership->points_balance }})</label>
                        <input type="number" name="points_to_spend" min="1" max="{{ $membership->points_balance }}" x-model="pointsToSpend" class="loop-input">
                        <p class="text-sm font-semibold text-mint-deep" x-show="pointsValue() > 0">
                            ≈ <span x-text="currency"></span> <span x-text="pointsDiscount().toLocaleString()"></span>
                        </p>
                    </div>
                </div>
            @endif

            <div class="flex gap-3">
                @unless ($customer)
                    <button type="button" class="loop-btn-ghost flex-1" @click="step = 2">{{ __('loop.back') }}</button>
                @endunless
                <button class="loop-btn flex-1">{{ __('loop.complete_sale') }}</button>
            </div>
            <a href="{{ route('till.index') }}" class="block text-center text-sm text-ink-muted underline">{{ __('loop.cancel') }}</a>
        </div>
    </form>
</x-app-layout>
