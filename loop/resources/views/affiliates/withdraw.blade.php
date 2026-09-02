<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.affiliates') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.withdraw') }}</h1>
            <p class="mt-1 text-ink-muted">
                @if (! $hasAccount)
                    {{ __('loop.add_payout_account_first') }}
                @else
                    {{ __('loop.confirm_payout_account_blurb') }}
                @endif
            </p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-lg space-y-5">
        <div class="rounded-[2rem] border border-ink/10 bg-white/90 p-6 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.ready_to_withdraw_label') }}</p>
            <p class="mt-2 font-display text-4xl font-semibold tabular-nums">
                <span x-data="loopCountUp({{ (int) $available }})" x-text="formatted()">{{ number_format($available) }}</span>
                <span class="text-lg text-ink-muted">{{ $currency }}</span>
            </p>
            @if ($available < $minPayout)
                <p class="mt-2 text-sm text-ink-muted">{{ __('loop.withdraw_below_min', ['min' => number_format($minPayout), 'currency' => $currency]) }}</p>
            @endif
        </div>

        <form method="POST" action="{{ route('affiliate.withdraw.store') }}" class="space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 sm:p-8"
              x-data="{
                  method: @js(old('payout_method', $affiliate->payout_method ?: 'phone')),
                  changing: @js(! $hasAccount || $errors->any()),
              }">
            @csrf

            @if ($hasAccount)
                <div x-show="!changing" class="rounded-2xl bg-chalk px-4 py-3 text-sm">
                    <p class="font-semibold">{{ $affiliate->payout_account_name }}</p>
                    <p class="mt-1 text-ink-muted">
                        @if ($affiliate->payout_method === 'bank')
                            {{ __('loop.payout_by_bank') }} · {{ $affiliate->bank_name }}
                        @else
                            {{ __('loop.payout_by_phone') }} · {{ $affiliate->payout_phone }}
                        @endif
                    </p>
                    <button type="button" class="mt-3 text-sm font-semibold text-violet" @click="changing = true">{{ __('loop.change_payout_account') }}</button>
                </div>
            @endif

            <div x-show="changing" @if ($hasAccount) x-cloak @endif>
                <p class="loop-label">{{ __('loop.payout_method_question') }}</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <label class="cursor-pointer rounded-2xl border px-4 py-3" :class="method === 'phone' ? 'border-mint bg-mint/10' : 'border-ink/10'">
                        <input type="radio" name="payout_method" value="phone" class="sr-only" x-model="method">
                        <span class="font-semibold">{{ __('loop.payout_by_phone') }}</span>
                        <span class="mt-1 block text-xs text-ink-muted">{{ __('loop.payout_by_phone_help') }}</span>
                    </label>
                    <label class="cursor-pointer rounded-2xl border px-4 py-3" :class="method === 'bank' ? 'border-mint bg-mint/10' : 'border-ink/10'">
                        <input type="radio" name="payout_method" value="bank" class="sr-only" x-model="method">
                        <span class="font-semibold">{{ __('loop.payout_by_bank') }}</span>
                        <span class="mt-1 block text-xs text-ink-muted">{{ __('loop.payout_by_bank_help') }}</span>
                    </label>
                </div>

                <div class="mt-5">
                    <label class="loop-label">{{ __('loop.payout_account_name') }}</label>
                    <input name="payout_account_name" value="{{ old('payout_account_name', $affiliate->payout_account_name ?: $affiliate->name) }}" class="loop-input" :required="changing">
                </div>

                <div class="mt-5" x-show="method === 'phone'" x-cloak>
                    <label class="loop-label">{{ __('loop.payout_phone') }}</label>
                    <input name="payout_phone" value="{{ old('payout_phone', $affiliate->payout_phone) }}" class="loop-input" placeholder="712000000" :required="changing && method === 'phone'" inputmode="numeric" pattern="[0-9+]*" autocomplete="tel-national">
                </div>

                <div class="mt-5" x-show="method === 'bank'" x-cloak>
                    <label class="loop-label">{{ __('loop.bank_name') }}</label>
                    <input name="bank_name" value="{{ old('bank_name', $affiliate->bank_name) }}" class="loop-input" :required="changing && method === 'bank'">
                </div>
            </div>

            @if ($available >= $minPayout)
                <button class="loop-btn-mint w-full">{{ __('loop.confirm_and_withdraw') }}</button>
            @else
                <button class="loop-btn-mint w-full" x-show="changing">{{ __('loop.save_payout_account') }}</button>
                <p class="text-center text-sm text-ink-muted" x-show="!changing" x-cloak>{{ __('loop.withdraw_below_min', ['min' => number_format($minPayout), 'currency' => $currency]) }}</p>
            @endif
        </form>
    </div>
</x-app-layout>
