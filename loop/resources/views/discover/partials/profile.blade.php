<section>
    <div class="loop-wallet relative overflow-hidden p-5 sm:p-8">
        <div class="relative grid gap-5 sm:gap-6 lg:grid-cols-[auto_1fr_auto] lg:items-center">
            <div class="mx-auto lg:mx-0">
                @if ($business->logoUrl())
                    <div class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-[1.5rem] bg-ink ring-4 ring-white/15 sm:h-36 sm:w-36">
                        <img src="{{ $business->logoUrl() }}" alt="{{ $business->name }}" class="h-full w-full object-cover">
                    </div>
                @else
                    <div class="flex h-28 w-28 items-center justify-center rounded-[1.5rem] bg-gradient-to-br from-violet to-lime/70 font-display text-4xl font-semibold text-white ring-4 ring-white/15 sm:h-36 sm:w-36">
                        {{ mb_substr($business->name, 0, 1) }}
                    </div>
                @endif
            </div>
            <div class="min-w-0 text-center lg:text-left">
                <h1 class="font-display text-3xl font-semibold leading-tight sm:text-4xl">{{ $business->name }}</h1>
                <p class="mt-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-lime">{{ $business->sectorLabel() }}</p>
                <p class="mt-2 text-sm text-white/70">
                    {{ $business->city ?: ($business->shops->first()?->city) }} · {{ $business->country }}
                    @if ($business->shops->count() > 1)
                        · {{ $business->shops->count() }} {{ __('loop.branches') }}
                    @endif
                </p>
                @if ($business->description)
                    <p class="mt-2 max-w-xl text-sm text-white/75 lg:mx-0 mx-auto">{{ $business->description }}</p>
                @endif
            @if (! empty($loopPaused))
                <p class="mt-3 rounded-2xl bg-white/10 px-3.5 py-2 text-sm text-white/85">{{ __('loop.discover_paused_blurb') }}</p>
                @if ($isCustomer && $totalPoints !== null)
                    <p class="mt-2 text-sm text-white/70">{{ __('loop.member_paused_points', ['points' => number_format($totalPoints)]) }}</p>
                    @if ($memberships->isNotEmpty())
                        <div class="mt-3 text-left">
                            <x-want-loop-back :business="$business" :wanted-back="$wantedBack ?? false" />
                        </div>
                    @endif
                @endif
            @endif
            </div>
            @if ($business->hotline && empty($loopPaused))
                <div class="text-center lg:text-right">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white/50">{{ __('loop.hotline') }}</p>
                    <a href="tel:{{ preg_replace('/\s+/', '', $business->hotline) }}" class="mt-1.5 inline-flex items-center gap-2.5 rounded-2xl bg-lime px-4 py-2.5 font-display text-base font-semibold text-ink transition hover:bg-white">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 011 1V20a1 1 0 01-1 1C10.4 21 3 13.6 3 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.46.57 3.58a1 1 0 01-.25 1.02l-2.2 2.19z"/></svg>
                        <span>{{ $business->hotline }}</span>
                    </a>
                    <p class="mt-2 text-xs text-white/55">{{ __('loop.call_hotline_cta') }}</p>
                </div>
            @endif
        </div>
    </div>
</section>

<section class="mt-6">
    <h2 class="font-display text-lg font-semibold">{{ __('loop.branches') }}</h2>
    <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
        @foreach ($business->shops as $shop)
            <div class="rounded-2xl border border-ink/10 bg-white px-4 py-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold leading-snug">{{ $shop->name }}</p>
                        <p class="mt-0.5 text-sm text-ink-muted">
                            @if ($shop->address){{ $shop->address }} · @endif{{ $shop->city }}
                        </p>
                        @if ($shop->phone)
                            <a href="tel:{{ preg_replace('/\s+/', '', $shop->phone) }}" class="mt-1.5 inline-flex items-center gap-1.5 text-sm font-semibold text-violet">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 011 1V20a1 1 0 01-1 1C10.4 21 3 13.6 3 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.46.57 3.58a1 1 0 01-.25 1.02l-2.2 2.19z"/></svg>
                                {{ $shop->phone }}
                            </a>
                        @endif
                    </div>
                    @if ($isCustomer && $memberships->has($shop->id))
                        <span class="shrink-0 rounded-lg bg-violet-soft px-2.5 py-1 text-xs font-semibold text-violet-deep">
                            {{ number_format($memberships->get($shop->id)->points_balance) }} {{ __('loop.pts') }}
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>

@if (empty($loopPaused))
<section class="mt-6">
    <div class="flex items-center justify-between gap-3">
        <h2 class="font-display text-lg font-semibold">{{ __('loop.active_campaigns') }}</h2>
        @if (! empty($campaignsHasMore))
            <a href="{{ route('discover.catalog', [$business, 'campaigns']) }}" class="text-sm font-semibold text-violet">{{ __('loop.view_all') }}</a>
        @endif
    </div>
    <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
        @forelse ($campaigns as $campaign)
            <div class="rounded-2xl border border-violet/15 bg-violet-soft/40 px-4 py-3.5">
                <p class="font-semibold leading-snug">{{ $campaign->displayName() }}</p>
                <p class="mt-0.5 text-sm text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
            </div>
        @empty
            <p class="text-sm text-ink-muted">{{ __('loop.no_live_campaigns') }}</p>
        @endforelse
    </div>
</section>

<section class="mt-6">
    <div class="flex items-center justify-between gap-3">
        <h2 class="font-display text-lg font-semibold">{{ __('loop.offers_at_till') }}</h2>
        @if (! empty($rewardsHasMore))
            <a href="{{ route('discover.catalog', [$business, 'offers']) }}" class="text-sm font-semibold text-violet">{{ __('loop.view_all') }}</a>
        @endif
    </div>
    <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
        @forelse ($rewards as $reward)
            <div class="rounded-2xl border border-ink/10 bg-white px-4 py-3.5">
                <p class="font-semibold leading-snug">{{ $reward->name }}</p>
                <p class="mt-0.5 text-sm text-ink-muted">{{ number_format((int) $reward->points_cost) }} {{ __('loop.pts') }} · {{ $reward->label() }}</p>
                @if ($business->hotline)
                    <a href="tel:{{ preg_replace('/\s+/', '', $business->hotline) }}" class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-violet">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 011 1V20a1 1 0 01-1 1C10.4 21 3 13.6 3 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.46.57 3.58a1 1 0 01-.25 1.02l-2.2 2.19z"/></svg>
                        {{ $business->hotline }}
                    </a>
                @endif
            </div>
        @empty
            <p class="text-sm text-ink-muted">{{ __('loop.no_offers_yet') }}</p>
        @endforelse
    </div>
</section>

@if (isset($liveGames) && $liveGames->isNotEmpty())
<section class="mt-6">
    <h2 class="font-display text-lg font-semibold">{{ __('loop.games_wins') }}</h2>
    <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
        @foreach ($liveGames as $game)
            <div class="rounded-2xl border border-violet/30 bg-lavender/40 px-4 py-3.5">
                <p class="font-semibold leading-snug">{{ __('loop.discover_game_today', ['game' => $game->typeLabel()]) }}</p>
                <p class="mt-0.5 text-sm text-ink-muted">{{ $game->qualifyLine() }}</p>
                @if (! empty($loopPaused))
                    <p class="mt-1 text-xs font-semibold text-ink-muted">{{ __('loop.game_paused') }}</p>
                @endif
            </div>
        @endforeach
    </div>
</section>
@endif
@if (isset($raffles) && $raffles->isNotEmpty())
<section class="mt-6">
    <div class="flex items-center justify-between gap-3">
        <h2 class="font-display text-lg font-semibold">{{ __('loop.raffles') }}</h2>
        @if (! empty($rafflesHasMore))
            <a href="{{ route('discover.catalog', [$business, 'raffles']) }}" class="text-sm font-semibold text-violet">{{ __('loop.view_all') }}</a>
        @endif
    </div>
    <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
        @foreach ($raffles as $raffle)
            <div class="rounded-2xl border border-lime/40 bg-lime/10 px-4 py-3.5">
                <p class="font-semibold leading-snug">{{ $raffle->name }}</p>
                <p class="mt-0.5 text-sm text-ink-muted">
                    {{ $raffle->prize_name }}
                    · {{ __('loop.freq_'.$raffle->frequency) }}
                    · {{ __('loop.raffle_draw_day', ['day' => $raffle->nextDrawDate()->format('j M')]) }}
                </p>
            </div>
        @endforeach
    </div>
</section>
@endif
@endif

@if ($related->isNotEmpty())
    <section class="mt-8">
        <h2 class="mb-3 font-display text-lg font-semibold">{{ __('loop.more_nearby') }}</h2>
        <div class="loop-shop-grid">
            @foreach ($related as $item)
                <x-discover-tile :business="$item" />
            @endforeach
        </div>
    </section>
@endif
