<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex min-w-0 items-start gap-4">
                @php $shop = $business->shops->first(); @endphp
                @if ($shop)
                    <x-shop-logo :shop="$shop" class="h-14 w-14 shrink-0 rounded-[1.15rem]" />
                @endif
                <div class="min-w-0">
                    <h1 class="font-display text-3xl font-semibold leading-tight">{{ $business->name }}</h1>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.wallet_show_blurb') }}</p>
                </div>
            </div>
            <p class="font-display text-3xl font-semibold sm:text-4xl">{{ $membership->points_balance }} <span class="text-base text-ink-muted">{{ __('loop.pts') }}</span></p>
        </div>
    </x-slot>

    @if ($business->hotline)
        <a href="tel:{{ preg_replace('/\s+/', '', $business->hotline) }}" class="mb-5 flex items-center justify-between gap-3 rounded-[1.5rem] bg-ink px-5 py-4 text-white">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint">{{ __('loop.ready_to_redeem') }}</p>
                <p class="mt-1 font-display text-lg font-semibold">{{ __('loop.call_hotline_cta') }}</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-xl bg-mint px-3 py-2 text-sm font-semibold text-ink">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 011 1V20a1 1 0 01-1 1C10.4 21 3 13.6 3 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.46.57 3.58a1 1 0 01-.25 1.02l-2.2 2.19z"/></svg>
                {{ $business->hotline }}
            </span>
        </a>
    @endif

    @if ($raffleWins->isNotEmpty())
        <section class="loop-panel mb-5 overflow-hidden p-0">
            <div class="bg-gradient-to-br from-mint/20 via-white to-coral/10 px-6 py-5">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.raffle') }}</p>
                <h2 class="mt-1 font-display text-xl font-semibold">{{ __('loop.your_raffle_wins') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.your_raffle_wins_body') }}</p>
            </div>
            <div class="space-y-3 px-6 py-5">
                @foreach ($raffleWins as $win)
                    @php
                        $daysLeft = $win->claim_by && $win->status !== 'claimed' && $win->claim_by->isFuture()
                            ? (int) now()->startOfDay()->diffInDays($win->claim_by->copy()->startOfDay())
                            : null;
                        $expired = $win->claim_by && $win->status !== 'claimed' && $win->claim_by->isPast();
                    @endphp
                    <div class="rounded-2xl border border-ink/8 bg-white px-4 py-4 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-display text-lg font-semibold">{{ $win->raffle->prize_name }}</p>
                                <p class="mt-1 text-sm text-ink-muted">{{ $win->raffle->name }} · {{ __('loop.raffle_winner_status_'.$win->status) }}</p>
                            </div>
                            @if ($daysLeft !== null)
                                <span class="rounded-xl bg-ink px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.1em] text-mint">
                                    {{ trans_choice('loop.days_left', $daysLeft, ['count' => $daysLeft]) }}
                                </span>
                            @elseif ($expired)
                                <span class="rounded-xl bg-coral/15 px-3 py-1.5 text-xs font-semibold text-coral">{{ __('loop.claim_expired') }}</span>
                            @endif
                        </div>
                        @if ($win->claim_by && $win->status !== 'claimed')
                            <p class="mt-3 text-xs text-ink-muted">{{ __('loop.claim_by') }} {{ $win->claim_by->format('d M Y') }}</p>
                        @endif
                        @if ($business->hotline && $win->status !== 'claimed' && ! $expired)
                            <a href="tel:{{ preg_replace('/\s+/', '', $business->hotline) }}" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-mint-deep">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 011 1V20a1 1 0 01-1 1C10.4 21 3 13.6 3 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.46.57 3.58a1 1 0 01-.25 1.02l-2.2 2.19z"/></svg>
                                {{ __('loop.call_to_claim') }}
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="loop-panel mb-5 p-6">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.offers_at_this_shop') }}</h2>
        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.offers_at_this_shop_body') }}</p>
        <div class="mt-4 space-y-2.5">
            @forelse ($rewards as $reward)
                @php
                    $canRedeem = $membership->points_balance >= $reward->points_cost;
                    $needed = max(0, $reward->points_cost - $membership->points_balance);
                @endphp
                <div class="flex justify-between gap-3 rounded-xl bg-chalk px-4 py-3 {{ $canRedeem ? 'ring-1 ring-mint/40' : '' }}">
                    <div>
                        <p class="font-semibold leading-snug">{{ $reward->name }}</p>
                        <p class="mt-0.5 text-sm text-ink-muted">{{ $reward->label() }}</p>
                        @if (! $canRedeem)
                            <p class="mt-1.5 text-sm font-semibold text-coral">{{ __('loop.need_more_points', ['points' => $needed]) }}</p>
                        @endif
                    </div>
                    <span class="shrink-0 text-right text-sm font-semibold {{ $canRedeem ? 'text-mint-deep' : 'text-ink-muted' }}">
                        {{ $reward->points_cost }} {{ __('loop.pts') }}
                        @if ($canRedeem)
                            <span class="mt-1 block text-xs">{{ __('loop.ready') }}</span>
                        @endif
                    </span>
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.no_offers_yet') }}</p>
            @endforelse
        </div>
    </section>

    <section class="loop-panel p-6">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.points_activity') }}</h2>
        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.points_activity_blurb') }}</p>
        <div class="mt-4 space-y-2.5">
            @forelse ($transactions as $tx)
                <div class="flex justify-between rounded-xl bg-chalk px-4 py-3">
                    <div>
                        <p class="font-medium">
                            @if ($customersSeeSales)
                                {{ $tx->description }}
                            @else
                                {{ __('loop.points_activity_private') }}
                            @endif
                        </p>
                        <p class="text-xs text-ink-muted">{{ $tx->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="font-semibold {{ $tx->points >= 0 ? 'text-mint-deep' : 'text-coral' }}">{{ $tx->points >= 0 ? '+' : '' }}{{ $tx->points }}</span>
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.no_history_yet') }}</p>
            @endforelse
        </div>
    </section>
</x-app-layout>
