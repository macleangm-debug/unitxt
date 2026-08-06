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
                <button type="button" class="loop-btn-ghost !py-2"
                        @click="navigator.clipboard.writeText(@js($shareUrl)); copied=true; setTimeout(() => copied=false, 1800)">
                    <span x-text="copied ? @js(__('loop.copied')) : @js(__('loop.copy_link'))"></span>
                </button>
                <button type="button" class="loop-btn-mint !py-2 inline-flex items-center gap-2"
                        @click="
                            if (navigator.share) {
                                navigator.share({ title: 'Loop', text: @js(__('loop.share_invite_text')), url: @js($shareUrl) });
                            } else {
                                navigator.clipboard.writeText(@js($shareUrl));
                                copied = true;
                                setTimeout(() => copied = false, 1800);
                            }
                        ">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                        <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/>
                    </svg>
                    {{ __('loop.share_now') }}
                </button>
            </div>
            <p class="mt-3 text-sm">{{ __('loop.ref_code') }}: <span class="font-display text-lg font-semibold tracking-wide">{{ $code }}</span></p>
        </div>
        <div class="rounded-[1.5rem] bg-gradient-to-br from-ink to-ink-soft p-6 text-white">
            <p class="text-sm text-white/70">{{ __('loop.your_rewards') }}</p>
            <p class="mt-3 font-display text-4xl font-semibold">{{ $business->referral_credit_days }}</p>
            <p class="text-sm text-white/65">{{ __('loop.extra_days_earned') }}</p>
            @if ($plan)
                <p class="mt-4 text-xs text-white/55">{{ __('loop.current_plan') }}: {{ $plan->name }} · {{ $plan->priceLabel() }}</p>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <div class="loop-panel p-5">
            <p class="text-sm font-semibold">{{ __('loop.referrer_gets') }}</p>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.referrer_gets_body', [
                'days' => $program['referrer_extra_days_per_referral'],
                'goal' => $program['goal_count'],
            ]) }}</p>
        </div>
        <div class="loop-panel p-5">
            <p class="text-sm font-semibold">{{ __('loop.referred_gets') }}</p>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.referred_gets_body', [
                'days' => $program['referred_extra_trial_days'],
            ]) }}</p>
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
