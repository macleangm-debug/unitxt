<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.admin') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.admin_dashboard') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.admin_dashboard_blurb') }}</p>
        </div>
    </x-slot>

    @include('admin.partials.nav')

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="loop-panel p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.admin_businesses') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $businessCount }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ $activeBusinesses }} {{ __('loop.live') }}</p>
        </div>
        <div class="loop-panel p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.admin_owners') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $ownerCount }}</p>
        </div>
        <div class="loop-panel p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.customers') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $customerCount }}</p>
        </div>
        <div class="loop-panel p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.sales') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $visitCount }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-ink/10 bg-white p-4">
            <p class="text-sm text-ink-muted">{{ __('loop.referral_pending') }}</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ $referralPending }}</p>
        </div>
        <div class="rounded-2xl border border-ink/10 bg-white p-4">
            <p class="text-sm text-ink-muted">{{ __('loop.referral_qualified') }}</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ $referralQualified }}</p>
        </div>
        <div class="rounded-2xl border border-ink/10 bg-white p-4">
            <p class="text-sm text-ink-muted">{{ __('loop.referral_rewarded') }}</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ $referralRewarded }}</p>
        </div>
    </div>

    <section class="mt-10">
        <div class="mb-4 flex items-end justify-between gap-3">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.recent_businesses') }}</h2>
            <a href="{{ route('admin.businesses.index') }}" class="text-sm font-semibold text-mint-deep">{{ __('loop.view_all') }} →</a>
        </div>
        <div class="space-y-2">
            @foreach ($recentBusinesses as $business)
                <div class="loop-panel flex flex-wrap items-center justify-between gap-3 p-4">
                    <div>
                        <p class="font-semibold">{{ $business->name }}</p>
                        <p class="text-xs text-ink-muted">{{ $business->sectorLabel() }} · {{ $business->city ?: $business->country }} · {{ $business->plan_key }}</p>
                    </div>
                    <span class="rounded-lg bg-chalk px-2.5 py-1 text-xs font-semibold">{{ $business->billing_status }}</span>
                </div>
            @endforeach
        </div>
    </section>
</x-app-layout>
