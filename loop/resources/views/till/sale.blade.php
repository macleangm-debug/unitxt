<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">
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
    </x-slot>

    @if ($customer && $membership)
        <div class="mb-6 grid gap-3 sm:grid-cols-3 animate-fade-up">
            <div class="loop-panel p-4">
                <p class="text-sm text-ink-muted">{{ __('loop.balance') }}</p>
                <p class="font-display text-3xl font-semibold">{{ $membership->points_balance }}</p>
            </div>
            <div class="loop-panel p-4 sm:col-span-2">
                <p class="text-sm text-ink-muted">{{ __('loop.available_offers') }}</p>
                <p class="mt-1 text-sm">
                    @forelse ($membership->availableRewards() as $reward)
                        <span class="mr-2 mb-1 inline-block rounded-lg bg-mint-soft px-2 py-1 font-medium">{{ $reward->name }} ({{ $reward->points_cost }} pts)</span>
                    @empty
                        <span class="text-ink-muted">{{ __('loop.none_unlocked') }}</span>
                    @endforelse
                </p>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('till.store') }}" class="loop-panel max-w-xl space-y-4 p-6"
          x-data="{
            amountDisplay: '{{ old('amount_spent') }}',
            payWithPoints: {{ old('pay_with_points') ? 'true' : 'false' }},
            pointsToSpend: '{{ old('points_to_spend', '') }}',
            balance: {{ $membership->points_balance ?? 0 }},
            rate: {{ $campaign?->currencyPerPoint() ?? 0 }},
            formatAmount() {
                let raw = String(this.amountDisplay).replace(/[^\d.]/g, '');
                const parts = raw.split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                this.amountDisplay = parts.join('.');
            },
            amountValue() {
                return String(this.amountDisplay).replace(/,/g, '');
            },
            pointsValue() {
                const pts = parseInt(this.pointsToSpend || 0, 10) || 0;
                return Math.min(pts, this.balance);
            },
            pointsDiscount() {
                return Math.round(this.pointsValue() * this.rate);
            }
          }">
        @csrf
        <input type="hidden" name="shop_id" value="{{ $shop->id }}">
        <input type="hidden" name="country_code" value="{{ $country_code }}">
        <input type="hidden" name="phone" value="{{ $phone }}">
        <input type="hidden" name="channel" value="{{ $channel }}">
        <input type="hidden" name="amount_spent" :value="amountValue()">

        @unless ($customer)
            <p class="rounded-xl bg-chalk px-3 py-2 text-sm text-ink-muted">{{ __('loop.new_customer_hint') }}</p>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="loop-label">{{ __('loop.first_name') }}</label>
                    <input name="first_name" value="{{ old('first_name') }}" class="loop-input" required>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.last_name') }}</label>
                    <input name="last_name" value="{{ old('last_name') }}" class="loop-input" required>
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="loop-label">{{ __('loop.month') }}</label>
                    <select name="birth_month" class="loop-input">
                        <option value="">—</option>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected(old('birth_month') == $m)>{{ $m }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.day') }}</label>
                    <select name="birth_day" class="loop-input">
                        <option value="">—</option>
                        @for ($d = 1; $d <= 31; $d++)
                            <option value="{{ $d }}" @selected(old('birth_day') == $d)>{{ $d }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.email_optional') }}</label>
                <input type="email" name="email" value="{{ old('email') }}" class="loop-input">
            </div>
        @endunless

        <div>
            <label class="loop-label">{{ __('loop.amount_spent') }} ({{ $business->currency }})</label>
            <input type="text" inputmode="decimal" x-model="amountDisplay" @input="formatAmount()" class="loop-input text-xl" placeholder="0">
            @if ($campaign)
                <p class="mt-1 text-xs text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
            @endif
            <x-input-error :messages="$errors->get('amount_spent')" class="mt-1" />
        </div>

        <div>
            <label class="loop-label">{{ __('loop.apply_offer') }}</label>
            <select name="reward_id" class="loop-input">
                <option value="">{{ __('loop.no_offer') }}</option>
                @foreach ($rewards as $reward)
                    <option value="{{ $reward->id }}" @selected(old('reward_id') == $reward->id)>
                        {{ $reward->name }} · {{ $reward->points_cost }} pts · {{ $reward->label() }}
                        @if ($reward->product_name) · {{ $reward->product_name }} @endif
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('reward_id')" class="mt-1" />
        </div>

        @if ($membership && $membership->points_balance > 0 && $campaign && $campaign->currencyPerPoint() > 0)
            <div class="rounded-2xl border border-ink/10 bg-chalk/70 p-4">
                <label class="flex items-center gap-3 text-sm font-semibold">
                    <input type="checkbox" name="pay_with_points" value="1" x-model="payWithPoints" class="rounded border-ink/20 text-mint focus:ring-mint">
                    {{ __('loop.pay_with_points') }}
                </label>
                <div x-show="payWithPoints" x-cloak class="mt-3 space-y-2">
                    <label class="loop-label">{{ __('loop.points_to_spend') }} (max {{ $membership->points_balance }})</label>
                    <input type="number" name="points_to_spend" min="1" max="{{ $membership->points_balance }}" x-model="pointsToSpend" class="loop-input">
                    <p class="text-xs text-ink-muted">
                        ≈ {{ $business->currency }} <span x-text="pointsDiscount().toLocaleString()"></span>
                        · {{ __('loop.rate_hint') }}: 1 pt ≈ {{ number_format($campaign->currencyPerPoint(), 0) }} {{ $business->currency }}
                    </p>
                    <x-input-error :messages="$errors->get('points_to_spend')" class="mt-1" />
                </div>
            </div>
        @endif

        <button class="loop-btn-mint w-full">{{ __('loop.complete_sale') }}</button>
        <a href="{{ route('till.index') }}" class="block text-center text-sm text-ink-muted underline">{{ __('loop.cancel') }}</a>
    </form>
</x-app-layout>
