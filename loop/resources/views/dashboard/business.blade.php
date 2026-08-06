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

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="loop-stat"><p class="text-sm text-ink-muted">{{ __('loop.shops') }}</p><p class="mt-2 font-display text-3xl font-semibold">{{ $shopCount }}</p></div>
        <div class="loop-stat"><p class="text-sm text-ink-muted">{{ __('loop.today') }}</p><p class="mt-2 font-display text-3xl font-semibold">{{ $todayVisits }}</p><p class="text-xs text-ink-muted">{{ $business->currency }} {{ number_format($todaySpend, 0) }}</p></div>
        <div class="loop-stat"><p class="text-sm text-ink-muted">{{ __('loop.members') }}</p><p class="mt-2 font-display text-3xl font-semibold">{{ $memberCount }}</p></div>
        <div class="loop-stat"><p class="text-sm text-ink-muted">{{ __('loop.sales') }}</p><p class="mt-2 font-display text-3xl font-semibold">{{ $visitCount }}</p></div>
    </div>

    @if ($isOwner && $referralProgress)
        <section class="mt-6 overflow-hidden rounded-[1.75rem] border border-ink/10 bg-gradient-to-br from-ink via-ink-soft to-mint/20 p-6 text-white shadow-[0_24px_70px_rgba(11,31,42,0.12)]">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="max-w-xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint">{{ __('loop.referrals') }}</p>
                    <h2 class="mt-2 font-display text-2xl font-semibold">{{ __('loop.referral_dash_title', ['goal' => $referralProgress['goal']]) }}</h2>
                    <p class="mt-2 text-sm text-white/70">{{ __('loop.referral_dash_body') }}</p>
                </div>
                <a href="{{ route('settings.referrals') }}" class="rounded-full bg-mint px-4 py-2 text-sm font-semibold text-ink hover:bg-mint-deep">{{ __('loop.invite_businesses') }}</a>
            </div>

            <div class="mt-5">
                <div class="flex items-center justify-between text-sm">
                    <span>{{ __('loop.referral_joined_count', ['count' => $referralProgress['joined'], 'goal' => $referralProgress['goal']]) }}</span>
                    <span class="text-white/60">{{ $referralProgress['percent'] }}%</span>
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/15">
                    <div class="h-full rounded-full bg-mint transition-all" style="width: {{ $referralProgress['percent'] }}%"></div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    @for ($i = 1; $i <= $referralProgress['goal']; $i++)
                        <span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $referralProgress['joined'] >= $i ? 'bg-mint text-ink' : 'bg-white/10 text-white/70' }}">
                            {{ $i }} {{ __('loop.business') }}
                        </span>
                    @endfor
                </div>
                @if ($referralProgress['pending'] > 0)
                    <p class="mt-3 text-xs text-white/55">{{ __('loop.referral_pending_count', ['count' => $referralProgress['pending']]) }}</p>
                @endif
            </div>
        </section>
    @endif

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
                                <p class="font-semibold">{{ $campaign->displayName() }}</p>
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
            <h2 class="font-display text-xl font-semibold">{{ __('loop.recent_sales') }}</h2>
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
                    <p class="text-sm text-ink-muted">{{ __('loop.no_sales') }}</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
