<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.affiliates') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.payout_settings') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.payout_settings_blurb') }}</p>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('affiliate.payout.update') }}" class="mx-auto max-w-lg space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 sm:p-8"
          x-data="{ method: @js(old('payout_method', $affiliate->payout_method ?: 'phone')) }">
        @csrf
        @method('PUT')

        <div>
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
        </div>

        <div>
            <label class="loop-label">{{ __('loop.payout_account_name') }}</label>
            <input name="payout_account_name" value="{{ old('payout_account_name', $affiliate->payout_account_name ?: $affiliate->name) }}" class="loop-input" required>
        </div>

        <div x-show="method === 'phone'" x-cloak>
            <label class="loop-label">{{ __('loop.payout_phone') }}</label>
            <input name="payout_phone" value="{{ old('payout_phone', $affiliate->payout_phone) }}" class="loop-input" placeholder="+255 712 000 000" :required="method === 'phone'">
        </div>

        <div x-show="method === 'bank'" x-cloak>
            <label class="loop-label">{{ __('loop.bank_name') }}</label>
            <input name="bank_name" value="{{ old('bank_name', $affiliate->bank_name) }}" class="loop-input" :required="method === 'bank'">
        </div>

        <button class="loop-btn-mint w-full">{{ __('loop.save') }}</button>
    </form>
</x-app-layout>
