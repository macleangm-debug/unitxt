<x-app-layout>
    <x-slot name="header">
        @if (!empty($subscriptionBanner))
            <div @class([
                'mb-3 flex items-center justify-between gap-3 rounded-2xl border px-3.5 py-2 text-sm',
                'border-coral/30 bg-coral/10 text-ink' => ($subscriptionBanner['tone'] ?? '') === 'coral',
                'border-amber-200 bg-amber-50 text-ink' => ($subscriptionBanner['tone'] ?? '') !== 'coral',
            ])>
                <p class="min-w-0 truncate font-medium">{{ $subscriptionBanner['text'] }}</p>
                <a href="{{ route('billing.show') }}" class="shrink-0 text-xs font-semibold text-violet">{{ __('loop.renew_now') }}</a>
            </div>
        @endif
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
                <a href="{{ ! empty($paused) ? route('billing.show') : route('till.index') }}" class="loop-btn-lime">
                    {{ ! empty($paused) ? __('loop.reactivate_loop') : __('loop.open_sale') }}
                </a>
            </div>
        </div>
    </x-slot>

    @if ($isOwner && ! empty($paused) && ! empty($momentum))
        <section class="mb-6 rounded-[1.5rem] border border-coral/30 bg-coral/10 px-5 py-5">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-coral">Loop</p>
            <h2 class="mt-2 font-display text-2xl font-semibold">{{ __('loop.loop_momentum_paused') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.loop_customers_connected', ['count' => number_format($momentum['members'])]) }}</p>
            <x-loop-pause-facts :business="$business" :momentum="$momentum" />
            <p class="mt-4 text-sm font-semibold">{{ __('loop.loop_paused_safe') }}</p>
            <a href="{{ route('billing.show') }}" class="loop-btn mt-5 inline-flex">{{ __('loop.reactivate_loop') }}</a>
        </section>
    @endif

    @if ($isOwner && ! empty($pulse) && empty($paused))
        @if (! empty($pulse['milestone']))
            <section class="mb-6 rounded-[1.5rem] bg-ink px-5 py-5 text-white">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-lime">Loop</p>
                <h2 class="mt-2 font-display text-2xl font-semibold">{{ $pulse['milestone']['title'] }}</h2>
                <p class="mt-1 text-sm text-white/65">{{ $pulse['milestone']['body'] }}</p>
            </section>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="loop-stat">
                <p class="text-sm text-ink-muted">{{ __('loop.pulse_what_happened') }}</p>
                <p class="mt-2 font-display text-2xl font-semibold leading-snug">
                    <x-count-up :value="$pulse['today_visits']" class="font-display" /> {{ $pulse['happened'] }}
                </p>
                @if (! empty($pulse['happened_returning']))
                    <p class="mt-1 text-sm text-ink-muted">{{ $pulse['happened_returning'] }}</p>
                @endif
                <p class="mt-1 text-xs text-ink-muted">{{ $business->currency }} <x-count-up :value="$todaySpend" /></p>
            </div>
            <div class="loop-stat">
                <p class="text-sm text-ink-muted">{{ __('loop.pulse_what_happening') }}</p>
                <p class="mt-2 font-display text-2xl font-semibold leading-snug">
                    <x-count-up :value="$pulse['redeemable']" class="font-display" /> {{ $pulse['happening'] }}
                </p>
            </div>
            <div class="loop-stat">
                <p class="text-sm text-ink-muted">{{ $pulse['reason_to_return'] }}</p>
                <p class="mt-2 font-display text-2xl font-semibold leading-snug">
                    <x-count-up :value="$memberCount" class="font-display" />
                </p>
                @if (! empty($pulse['campaign_return_line']))
                    <p class="mt-1 text-xs text-ink-muted">{{ $pulse['campaign_return_line'] }}</p>
                @endif
            </div>
        </div>

        @if (! empty($pulse['needs']) || ! empty($pulse['next']))
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @if (! empty($pulse['needs']))
                    <div class="loop-stat">
                        <p class="text-sm text-ink-muted">{{ __('loop.pulse_what_needs') }}</p>
                        <p class="mt-2 font-display text-xl font-semibold leading-snug">{{ $pulse['needs']['title'] }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ $pulse['needs']['body'] }}</p>
                    </div>
                @endif
                <div class="loop-stat">
                    <p class="text-sm text-ink-muted">{{ __('loop.pulse_what_next') }}</p>
                    <p class="mt-2 font-display text-xl font-semibold leading-snug">{{ $pulse['next']['title'] }}</p>
                    <a href="{{ $pulse['next']['url'] }}" class="loop-btn-mint mt-4 inline-flex">{{ $pulse['next']['cta'] }}</a>
                </div>
            </div>
        @endif

        @if (! empty($pulse['money_line']))
            <p class="mt-4 rounded-[1.25rem] border border-ink/8 bg-white/80 px-4 py-3 text-sm font-medium">{{ $pulse['money_line'] }}</p>
        @endif

        @if (! empty($pulse['suggestion']))
            <section class="mt-4 rounded-[1.5rem] border border-violet/15 bg-violet-soft/40 px-5 py-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.pulse_something_you_could') }}</p>
                <h2 class="mt-1 font-display text-lg font-semibold">{{ $pulse['suggestion']['title'] }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ $pulse['suggestion']['body'] }}</p>
                <a href="{{ $pulse['suggestion']['url'] }}" class="mt-3 inline-flex text-sm font-semibold text-violet">{{ $pulse['suggestion']['cta'] }} →</a>
            </section>
        @endif
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="loop-stat">
                <p class="text-sm text-ink-muted">{{ __('loop.shops') }}</p>
                <p class="mt-2 font-display text-3xl font-semibold"><x-count-up :value="$shopCount" /></p>
            </div>
            <div class="loop-stat">
                <p class="text-sm text-ink-muted">{{ __('loop.today') }}</p>
                <p class="mt-2 font-display text-3xl font-semibold"><x-count-up :value="$todayVisits" /></p>
                <p class="text-xs text-ink-muted">{{ $business->currency }} {{ number_format($todaySpend, 0) }}</p>
            </div>
            <div class="loop-stat">
                <p class="text-sm text-ink-muted">{{ __('loop.members') }}</p>
                <p class="mt-2 font-display text-3xl font-semibold"><x-count-up :value="$memberCount" /></p>
            </div>
            <div class="loop-stat">
                <p class="text-sm text-ink-muted">{{ __('loop.sales') }}</p>
                <p class="mt-2 font-display text-3xl font-semibold"><x-count-up :value="$visitCount" /></p>
                <p class="text-xs text-ink-muted">{{ $business->currency }} {{ number_format($totalSpend ?? 0, 0) }}</p>
            </div>
        </div>
    @endif

    @if ($isOwner && $referralProgress)
        <section class="loop-wallet loop-wallet--liquid mt-6 p-6">
            <div class="loop-orb loop-orb--a !h-28 !w-28 !blur-2xl"></div>
            <div class="loop-orb loop-orb--b !h-24 !w-24 !blur-2xl"></div>
            <div class="relative flex flex-wrap items-start justify-between gap-4">
                <div class="max-w-xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-lime">{{ __('loop.referrals') }}</p>
                    <h2 class="mt-2 font-display text-2xl font-semibold">{{ __('loop.referral_dash_title', ['goal' => $referralProgress['goal']]) }}</h2>
                    <p class="mt-2 text-sm text-white/70">{{ __('loop.referral_dash_body', [
                        'days' => $referralProgress['program']['referrer_extra_days_per_referral'] ?? 3,
                    ]) }}</p>
                </div>
                @if (\App\Support\MarketingSettings::settings()['show_referral_cta'])
                <a href="{{ route('settings.referrals') }}" class="loop-btn-lime !py-2">{{ __('loop.invite_businesses') }}</a>
                @endif
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
