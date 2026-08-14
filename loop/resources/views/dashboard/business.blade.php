<x-app-layout>
    <x-slot name="header">
        <div
            class="loop-wallet mb-2 px-5 py-6 sm:px-7 sm:py-7"
        >
            <div class="loop-orb loop-orb--a "></div>
            <div class="loop-orb loop-orb--b "></div>
            <div class="loop-orb loop-orb--c "></div>
            <div class="relative flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-lime">Loop</p>
                    <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ $business->name }}</h1>
                    <p class="mt-1 text-sm text-white/60">{{ $business->sectorLabel() }} · {{ $business->city }} · {{ $business->currency }}</p>
                </div>
                <a href="{{ route('till.index') }}" class="loop-btn-lime">{{ __('loop.open_sale') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="loop-stat">
            <p class="text-sm text-ink-muted">{{ __('loop.shops') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $shopCount }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-sm text-ink-muted">{{ __('loop.today') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $todayVisits }}</p>
            <p class="text-xs text-ink-muted">{{ $business->currency }} {{ number_format($todaySpend, 0) }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-sm text-ink-muted">{{ __('loop.members') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $memberCount }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-sm text-ink-muted">{{ __('loop.sales') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $visitCount }}</p>
        </div>
    </div>

    @if ($isOwner && !empty($needsUpgrade))
        <section class="mt-6 overflow-hidden rounded-[1.5rem] border border-coral/25 bg-white p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="max-w-xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-coral">{{ __('loop.billing') }}</p>
                    <h2 class="mt-2 font-display text-2xl font-semibold">
                        {{ !empty($trialExpired) ? __('loop.trial_ended_title') : __('loop.upgrade_nudge_title') }}
                    </h2>
                    <p class="mt-2 text-sm text-ink-muted">
                        @if (!empty($trialExpired))
                            {{ __('loop.trial_ended_body') }}
                        @elseif (($trialDaysLeft ?? 0) > 0)
                            {{ __('loop.trial_days_left', ['days' => $trialDaysLeft]) }} — {{ __('loop.upgrade_nudge_body') }}
                        @else
                            {{ __('loop.upgrade_nudge_body') }}
                        @endif
                    </p>
                </div>
                <a href="{{ route('billing.show') }}" class="loop-btn">{{ __('loop.upgrade_now') }}</a>
            </div>
        </section>
    @endif

    @if ($isOwner && $referralProgress)
        <section class="loop-wallet loop-wallet--liquid mt-6 p-6">
            <div class="loop-orb loop-orb--a !h-28 !w-28 !blur-2xl"></div>
            <div class="loop-orb loop-orb--b !h-24 !w-24 !blur-2xl"></div>
            <div class="relative flex flex-wrap items-start justify-between gap-4">
                <div class="max-w-xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-lime">{{ __('loop.referrals') }}</p>
                    <h2 class="mt-2 font-display text-2xl font-semibold">{{ __('loop.referral_dash_title', ['goal' => $referralProgress['goal']]) }}</h2>
                    <p class="mt-2 text-sm text-white/70">{{ __('loop.referral_dash_short', ['days' => $referralProgress['program']['referrer_extra_days_per_referral'] ?? 3]) }}</p>
                </div>
                <a href="{{ route('settings.referrals') }}" class="loop-btn-lime !py-2">{{ __('loop.invite_businesses') }}</a>
            </div>

            <div class="relative mt-5">
                <div class="flex items-center justify-between text-sm">
                    <span>{{ __('loop.referral_joined_count', ['count' => $referralProgress['joined'], 'goal' => $referralProgress['goal']]) }}</span>
                    <span class="text-white/60">{{ $referralProgress['percent'] }}%</span>
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/15">
                    <div class="h-full rounded-full bg-lime transition-all" style="width: {{ $referralProgress['percent'] }}%"></div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    @for ($i = 1; $i <= $referralProgress['goal']; $i++)
                        <span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $referralProgress['joined'] >= $i ? 'bg-lime text-ink' : 'bg-white/10 text-white/70' }}">
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

    <div class="mt-8 grid gap-8 lg:grid-cols-[1.15fr_0.85fr]">
        <section>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.active_campaigns') }}</h2>
                @if ($isOwner)
                    <a href="{{ route('campaigns.index') }}" class="text-sm font-semibold text-violet">{{ __('loop.view_campaigns') }}</a>
                @endif
            </div>
            <div class="divide-y divide-ink/10">
                @forelse ($activeCampaigns as $campaign)
                    @if ($isOwner)
                        <a href="{{ route('campaigns.show', $campaign) }}" class="flex items-start justify-between gap-3 py-3.5 transition hover:bg-violet-soft/40">
                    @else
                        <div class="flex items-start justify-between gap-3 py-3.5">
                    @endif
                            <div>
                                <p class="font-semibold">{{ $campaign->displayName() }}</p>
                                <p class="text-sm text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-display text-xl font-semibold">{{ $campaign->today_visits_count }}</p>
                                <p class="text-xs text-ink-muted">{{ __('loop.today') }}</p>
                            </div>
                    @if ($isOwner)
                        </a>
                    @else
                        </div>
                    @endif
                @empty
                    <p class="py-3 text-sm text-ink-muted">{{ __('loop.no_live_campaigns') }}</p>
                @endforelse
            </div>
        </section>
        <section>
            @if ($recentVisits->isNotEmpty())
                <h2 class="mb-4 font-display text-xl font-semibold">{{ __('loop.recent_sales') }}</h2>
                <div class="divide-y divide-ink/10">
                    @foreach ($recentVisits as $visit)
                        <div class="flex items-center justify-between gap-4 py-3.5">
                            <div class="min-w-0">
                                <p class="font-semibold">{{ $visit->customer->name }}</p>
                                <p class="text-xs text-ink-muted">{{ $visit->shop->name }} · {{ $visit->created_at->format('d M Y · H:i') }}</p>
                                <p class="mt-0.5 text-xs font-medium text-violet">+{{ $visit->points_earned }} pts</p>
                            </div>
                            <p class="shrink-0 text-right font-display text-xl font-semibold tracking-tight">
                                {{ $business->currency }} {{ number_format($visit->amount_spent, 0) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
