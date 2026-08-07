<x-app-layout>
    <x-slot name="header">
        <div class="mx-auto max-w-lg text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.quick_setup') }}</p>
            <h1 class="mt-2 font-display text-3xl font-semibold">{{ __('loop.choose_your_promo') }}</h1>
            <p class="mt-2 text-ink-muted">{{ __('loop.choose_your_promo_body') }}</p>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('affiliate.setup.store') }}" class="mx-auto max-w-lg space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8">
        @csrf
        <div class="rounded-2xl bg-ink p-5 text-white">
            <p class="text-xs text-white/55">{{ __('loop.tracking_code') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $affiliate->tracking_code }}</p>
            <p class="mt-2 text-xs text-white/55">{{ __('loop.tracking_code_help') }}</p>
        </div>

        <div>
            <label class="loop-label">{{ __('loop.your_promo_code') }}</label>
            <input name="promo_code" value="{{ old('promo_code', $suggested) }}" class="loop-input font-display text-2xl font-semibold uppercase tracking-wide" required minlength="4" maxlength="12" pattern="[A-Za-z0-9]+">
            <p class="mt-2 text-xs text-ink-muted">{{ __('loop.promo_code_rules') }}</p>
            <x-input-error :messages="$errors->get('promo_code')" class="mt-1" />
        </div>

        <div class="rounded-2xl bg-mint-soft/60 px-4 py-3 text-sm text-ink">
            {{ __('loop.setup_share_hint') }}
        </div>

        <button class="loop-btn-mint w-full">{{ __('loop.save_and_share') }}</button>
    </form>
</x-app-layout>
