<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.settings') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.grow_with_referrals') }}</h1>
            <p class="mt-1 max-w-2xl text-ink-muted">{{ __('loop.grow_with_referrals_blurb') }}</p>
        </div>
    </x-slot>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="loop-panel p-6 lg:col-span-2">
            <p class="text-sm font-semibold">{{ __('loop.your_referral_link') }}</p>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.your_referral_link_body') }}</p>
            <div class="mt-4 flex flex-wrap items-center gap-2" x-data="{ copied: false }">
                <input readonly value="{{ $shareUrl }}" class="loop-input flex-1 font-mono text-sm" id="ref-link">
                <button type="button" class="loop-btn-mint !py-2"
                        @click="navigator.clipboard.writeText(@js($shareUrl)); copied=true; setTimeout(() => copied=false, 1800)">
                    <span x-text="copied ? @js(__('loop.copied')) : @js(__('loop.copy_link'))"></span>
                </button>
            </div>
            <p class="mt-3 text-sm">{{ __('loop.ref_code') }}: <span class="font-display text-lg font-semibold tracking-wide">{{ $code }}</span></p>
        </div>
        <div class="rounded-[1.5rem] bg-gradient-to-br from-ink to-ink-soft p-6 text-white">
            <p class="text-sm text-white/70">{{ __('loop.your_rewards') }}</p>
            <p class="mt-3 font-display text-4xl font-semibold">{{ $business->referral_credit_months }}</p>
            <p class="text-sm text-white/65">{{ __('loop.free_months_earned') }}</p>
            @if ($business->referral_discount_percent > 0)
                <p class="mt-4 text-sm text-mint">{{ __('loop.discount_ready', ['percent' => $business->referral_discount_percent]) }}</p>
            @endif
            @if ($plan)
                <p class="mt-4 text-xs text-white/55">{{ __('loop.current_plan') }}: {{ $plan->name }} · {{ $plan->priceLabel() }}</p>
            @endif
        </div>
    </div>

    <section class="mt-10">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.businesses_you_referred') }}</h2>
        <div class="mt-4 space-y-3">
            @forelse ($referrals as $referral)
                <div class="loop-panel flex items-center justify-between gap-3 p-4">
                    <div>
                        <p class="font-semibold">{{ $referral->referred?->name }}</p>
                        <p class="text-xs text-ink-muted">{{ $referral->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="rounded-lg bg-chalk px-2.5 py-1 text-xs font-semibold capitalize">{{ $referral->status }}</span>
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.no_referrals_yet_owner') }}</p>
            @endforelse
        </div>
    </section>

    <a href="{{ route('settings') }}" class="mt-8 inline-block text-sm font-semibold text-ink-muted underline">{{ __('loop.back') }}</a>
</x-app-layout>
