<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ $business->name }}</h1>
                <p class="mt-1 text-ink-muted">{{ $business->sectorLabel() }} · {{ $business->city }} · {{ $business->currency }}</p>
            </div>
            <a href="{{ route('till.index') }}" class="loop-btn-mint">{{ __('loop.open_sale') }}</a>
        </div>
    </x-slot>

    @if ($showWelcome ?? false)
        <div x-data="{ i: 0, slides: 3 }" class="mb-8 overflow-hidden rounded-3xl bg-ink p-6 text-white sm:p-8">
            <div class="relative min-h-[140px]">
                <div x-show="i===0" x-transition.opacity>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint">Loop</p>
                    <p class="mt-3 font-display text-2xl font-semibold">{{ __('loop.welcome_everybody') }}</p>
                    <p class="mt-2 text-sm text-white/70">{{ __('loop.welcome_sale_body') }}</p>
                </div>
                <div x-show="i===1" x-cloak x-transition.opacity>
                    <p class="font-display text-2xl font-semibold">{{ __('loop.welcome_open_sale') }}</p>
                    <p class="mt-2 text-sm text-white/70">{{ __('loop.welcome_open_sale_body') }}</p>
                </div>
                <div x-show="i===2" x-cloak x-transition.opacity>
                    <p class="font-display text-2xl font-semibold">{{ __('loop.welcome_campaign') }}</p>
                    <p class="mt-2 text-sm text-white/70">{{ __('loop.welcome_campaign_body') }}</p>
                </div>
            </div>
            <div class="mt-6 flex items-center justify-between">
                <div class="flex gap-1.5">
                    <template x-for="n in slides" :key="n">
                        <button type="button" class="h-1.5 w-6 rounded-full" :class="i===n-1 ? 'bg-mint' : 'bg-white/25'" @click="i=n-1"></button>
                    </template>
                </div>
                <button type="button" class="text-sm font-semibold text-mint" @click="i = (i+1) % slides">{{ __('loop.next') }} →</button>
            </div>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="loop-stat"><p class="text-sm text-ink-muted">{{ __('loop.shops') }}</p><p class="mt-2 font-display text-3xl font-semibold">{{ $shopCount }}</p></div>
        <div class="loop-stat"><p class="text-sm text-ink-muted">{{ __('loop.today') }}</p><p class="mt-2 font-display text-3xl font-semibold">{{ $todayVisits }}</p><p class="text-xs text-ink-muted">{{ $business->currency }} {{ number_format($todaySpend, 0) }}</p></div>
        <div class="loop-stat"><p class="text-sm text-ink-muted">{{ __('loop.members') }}</p><p class="mt-2 font-display text-3xl font-semibold">{{ $memberCount }}</p></div>
        <div class="loop-stat"><p class="text-sm text-ink-muted">{{ __('loop.visits') }}</p><p class="mt-2 font-display text-3xl font-semibold">{{ $visitCount }}</p></div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
        <section class="loop-panel p-6">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.active_campaigns') }}</h2>
                @if ($isOwner)
                    <a href="{{ route('campaigns.index') }}" class="text-sm font-semibold text-mint-deep">{{ __('loop.view_campaigns') }}</a>
                @endif
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($activeCampaigns as $campaign)
                    @if ($isOwner)
                        <a href="{{ route('campaigns.show', $campaign) }}" class="block rounded-2xl bg-chalk px-4 py-3 transition hover:bg-mint-soft/40">
                    @else
                        <div class="rounded-2xl bg-chalk px-4 py-3">
                    @endif
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $campaign->name }}</p>
                                <p class="text-sm text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-display text-xl font-semibold">{{ $campaign->today_visits_count }}</p>
                                <p class="text-xs text-ink-muted">{{ __('loop.today') }}</p>
                            </div>
                        </div>
                    @if ($isOwner)
                        </a>
                    @else
                        </div>
                    @endif
                @empty
                    <p class="text-sm text-ink-muted">{{ __('loop.no_live_campaigns') }}</p>
                @endforelse
            </div>
        </section>
        <section class="loop-panel p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.recent_visits') }}</h2>
            <div class="mt-4 space-y-3">
                @forelse ($recentVisits as $visit)
                    <div class="flex items-center justify-between rounded-2xl bg-chalk px-4 py-3">
                        <div>
                            <p class="font-semibold">{{ $visit->customer->name }}</p>
                            <p class="text-sm text-ink-muted">
                                {{ $visit->shop->name }} · {{ $business->currency }} {{ number_format($visit->amount_spent, 0) }}
                            </p>
                            <p class="text-xs text-ink-muted">{{ $visit->created_at->format('d M Y · H:i') }}</p>
                        </div>
                        <span class="text-sm font-semibold text-mint-deep">+{{ $visit->points_earned }}</span>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">{{ __('loop.sale_activity_hint') }}</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
