<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.settings') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.settings_blurb') }}</p>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('business.edit') }}" class="loop-panel loop-panel--energy group block p-6 transition hover:-translate-y-0.5">
            <p class="relative text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.business') }}</p>
            <p class="relative mt-3 font-display text-2xl font-semibold">{{ $business->name }}</p>
            <p class="relative mt-2 text-sm text-ink-muted">{{ __('loop.business_settings_blurb') }}</p>
        </a>
        <a href="{{ route('shops.index') }}" class="loop-panel loop-panel--energy group block p-6 transition hover:-translate-y-0.5">
            <p class="relative text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.shops') }}</p>
            <p class="relative mt-3 font-display text-3xl font-semibold" >{{ $shopCount }}</p>
            <p class="relative mt-2 text-sm text-ink-muted">{{ __('loop.settings_shops') }}</p>
        </a>
        <a href="{{ route('campaigns.index') }}" class="loop-panel loop-panel--energy group block p-6 transition hover:-translate-y-0.5">
            <p class="relative text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.campaigns_and_offers') }}</p>
            <p class="relative mt-3 font-display text-3xl font-semibold" >{{ $campaignCount + $offerCount }}</p>
            <p class="relative mt-2 text-sm text-ink-muted">{{ __('loop.settings_loyalty') }}</p>
        </a>
        <a href="{{ route('staff.index') }}" class="loop-panel loop-panel--energy group block p-6 transition hover:-translate-y-0.5">
            <p class="relative text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.staff') }}</p>
            <p class="relative mt-3 font-display text-3xl font-semibold" >{{ $staffCount }}</p>
            <p class="relative mt-2 text-sm text-ink-muted">{{ __('loop.settings_staff') }}</p>
        </a>
        <a href="{{ route('raffles.index') }}" class="loop-panel loop-panel--energy group block p-6 transition hover:-translate-y-0.5">
            <p class="relative text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.raffles') }}</p>
            <p class="relative mt-3 font-display text-3xl font-semibold" >{{ $raffleCount }}</p>
            <p class="relative mt-2 text-sm text-ink-muted">{{ __('loop.settings_raffles') }}</p>
        </a>
        <a href="{{ route('content-studio.index') }}" class="loop-panel loop-panel--energy group block p-6 transition hover:-translate-y-0.5">
            <p class="relative text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.content_studio') }}</p>
            <p class="relative mt-3 font-display text-2xl font-semibold">{{ __('loop.studio_create') }}</p>
            <p class="relative mt-2 text-sm text-ink-muted">{{ __('loop.settings_studio') }}</p>
        </a>
        <a href="{{ route('settings.referrals') }}" class="loop-panel loop-panel--energy group block p-6 transition hover:-translate-y-0.5">
            <p class="relative text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.referrals') }}</p>
            <p class="relative mt-3 font-display text-3xl font-semibold" >{{ $referralCount }}</p>
            <p class="relative mt-2 text-sm text-ink-muted">{{ __('loop.settings_referrals') }}</p>
            @if ($referralCredits > 0)
                <p class="relative mt-2 text-xs font-semibold text-violet">{{ __('loop.extra_days_short', ['count' => $referralCredits]) }}</p>
            @endif
        </a>
        <a href="{{ route('billing.show') }}" class="loop-panel loop-panel--energy group relative block overflow-hidden p-6 transition hover:-translate-y-0.5">
            <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-lime/30 blur-2xl"></div>
            <p class="relative text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.billing') }}</p>
            <p class="relative mt-3 font-display text-2xl font-semibold capitalize">{{ $planKey }}</p>
            <p class="relative mt-2 text-sm text-ink-muted">{{ __('loop.settings_billing') }}</p>
            <p class="relative mt-2 text-xs font-semibold capitalize text-ink-muted">{{ $billingStatus }}</p>
            <p class="relative mt-4 text-sm font-semibold text-violet">{{ __('loop.upgrade_now') }} →</p>
        </a>
    </div>
</x-app-layout>
