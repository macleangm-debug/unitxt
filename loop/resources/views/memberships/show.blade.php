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
            <div x-data="loopCountUp({{ (int) $membership->points_balance }})">
                <p class="font-display text-3xl font-semibold sm:text-4xl">
                    <span x-text="formatted()">{{ number_format($membership->points_balance) }}</span>
                    <span class="text-base text-ink-muted">{{ __('loop.pts') }}</span>
                </p>
            </div>
        </div>
    </x-slot>

    <div x-data="loopRedeem()">
        @if ($business->hotline)
            <a href="tel:{{ preg_replace('/\s+/', '', $business->hotline) }}" class="mb-4 flex items-center justify-between gap-3 rounded-[1.5rem] bg-ink px-5 py-4 text-white">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-lime">{{ __('loop.ready_to_redeem') }}</p>
                    <p class="mt-1 font-display text-lg font-semibold">{{ __('loop.call_hotline_cta') }}</p>
                </div>
                <span class="inline-flex items-center gap-2 rounded-xl bg-lime px-3 py-2 text-sm font-semibold text-ink">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 011 1V20a1 1 0 01-1 1C10.4 21 3 13.6 3 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.46.57 3.58a1 1 0 01-.25 1.02l-2.2 2.19z"/></svg>
                    {{ $business->hotline }}
                </span>
            </a>
        @endif

        @if ($raffleWins->isNotEmpty())
            <section class="mb-4 overflow-hidden rounded-[1.75rem] border border-violet/20 bg-violet-soft/40">
                <div class="px-5 py-4 sm:px-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.raffle') }}</p>
                    <h2 class="mt-1 font-display text-xl font-semibold">{{ __('loop.your_raffle_wins') }}</h2>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.your_raffle_wins_body') }}</p>
                </div>
                <div class="space-y-3 border-t border-ink/5 px-5 py-4 sm:px-6">
                    @foreach ($raffleWins as $win)
                        @php
                            $daysLeft = $win->claim_by && $win->status !== 'claimed' && $win->claim_by->isFuture()
                                ? (int) now()->startOfDay()->diffInDays($win->claim_by->copy()->startOfDay())
                                : null;
                            $expired = $win->claim_by && $win->status !== 'claimed' && $win->claim_by->isPast();
                        @endphp
                        <div class="rounded-2xl bg-white px-4 py-3.5 ring-1 ring-ink/5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-display text-lg font-semibold">{{ $win->raffle->prize_name }}</p>
                                    <p class="mt-0.5 text-sm text-ink-muted">{{ $win->raffle->name }} · {{ __('loop.raffle_winner_status_'.$win->status) }}</p>
                                </div>
                                @if ($daysLeft !== null)
                                    <span class="rounded-xl bg-ink px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.1em] text-lime">
                                        {{ trans_choice('loop.days_left', $daysLeft, ['count' => $daysLeft]) }}
                                    </span>
                                @elseif ($expired)
                                    <span class="rounded-xl bg-coral/15 px-3 py-1.5 text-xs font-semibold text-coral">{{ __('loop.claim_expired') }}</span>
                                @endif
                            </div>
                            @if ($win->claim_by && $win->status !== 'claimed')
                                <p class="mt-2 text-xs text-ink-muted">{{ __('loop.claim_by') }} {{ $win->claim_by->format('d M Y') }}</p>
                            @endif
                            @if ($business->hotline && $win->status !== 'claimed' && ! $expired)
                                <a href="tel:{{ preg_replace('/\s+/', '', $business->hotline) }}" class="mt-2 inline-flex items-center gap-2 text-sm font-semibold text-violet">
                                    {{ __('loop.call_to_claim') }}
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="overflow-hidden rounded-[1.75rem] border border-ink/8 bg-white">
            <div class="px-5 pt-5 sm:px-6">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.offers_at_this_shop') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.offers_at_this_shop_body') }}</p>
            </div>
            <div class="mt-3 divide-y divide-ink/5 px-2 pb-2 sm:px-3">
                @forelse ($rewards as $reward)
                    @php
                        $progress = $membership->progressTo($reward);
                        $canRedeem = $progress['ready'];
                        $needed = $progress['needed'];
                    @endphp
                    <div @class(['px-3 py-3.5 transition', $canRedeem ? 'bg-violet-soft/50' : ''])>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold leading-snug">{{ $reward->name }}</p>
                                <p class="mt-0.5 text-sm text-ink-muted">{{ $reward->label() }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-semibold {{ $canRedeem ? 'text-violet' : 'text-ink-muted' }}">{{ $reward->points_cost }} {{ __('loop.pts') }}</p>
                                @if ($canRedeem)
                                    <p class="mt-0.5 text-xs font-semibold text-violet">{{ __('loop.ready') }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="mt-2.5">
                            <div class="h-1.5 overflow-hidden rounded-full bg-ink/10">
                                <div class="h-full rounded-full transition-all duration-700 ease-out {{ $canRedeem ? 'bg-lime-deep' : 'bg-violet' }}" style="width: {{ $progress['percent'] }}%"></div>
                            </div>
                            <p class="mt-1.5 text-xs {{ $canRedeem ? 'font-semibold text-violet' : 'text-ink-muted' }}">
                                @if ($canRedeem)
                                    {{ __('loop.offer_ready_hint') }}
                                @else
                                    {{ __('loop.pts_to_unlock', ['points' => $needed]) }}
                                @endif
                            </p>
                        </div>
                        @if ($canRedeem)
                            <button
                                type="button"
                                class="loop-btn mt-3 !py-2.5 text-sm"
                                @click="show({
                                    name: @js($reward->name),
                                    pts: @js(number_format($reward->points_cost).' '.__('loop.pts')),
                                    hotline: @js($business->hotline ?: ''),
                                    business: @js($business->name),
                                })"
                            >{{ __('loop.redeem') }}</button>
                        @endif
                    </div>
                @empty
                    <p class="px-3 py-5 text-sm text-ink-muted">{{ __('loop.no_offers_yet') }}</p>
                @endforelse
            </div>

            <div class="border-t border-ink/8 px-5 pt-5 sm:px-6">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.points_activity') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.points_activity_blurb') }}</p>
            </div>
            <div class="mt-2 divide-y divide-ink/5 px-2 pb-3 sm:px-3">
                @forelse ($transactions as $tx)
                    <div class="flex items-center justify-between gap-3 px-3 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">
                                @if ($customersSeeSales)
                                    {{ $tx->description }}
                                @else
                                    {{ $tx->points >= 0 ? __('loop.points_earned_private') : __('loop.points_spent_private') }}
                                @endif
                            </p>
                            <p class="text-xs text-ink-muted">{{ $tx->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="shrink-0 font-semibold {{ $tx->points >= 0 ? 'text-violet' : 'text-coral' }}">{{ $tx->points >= 0 ? '+' : '' }}{{ $tx->points }}</span>
                    </div>
                @empty
                    <p class="px-3 py-5 text-sm text-ink-muted">{{ __('loop.no_history_yet') }}</p>
                @endforelse
            </div>
        </section>

        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 flex items-end justify-center bg-ink/55 p-0 sm:items-center sm:p-6"
            @keydown.escape.window="close()"
        >
            <div class="absolute inset-0" @click="close()"></div>
            <div
                class="relative w-full max-w-md rounded-t-[1.75rem] bg-white p-6 text-ink shadow-2xl sm:rounded-[1.75rem]"
                x-show="open"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-y-8 scale-95 opacity-0"
                x-transition:enter-end="translate-y-0 scale-100 opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0 scale-100 opacity-100"
                x-transition:leave-end="translate-y-6 scale-95 opacity-0"
                @click.stop
            >
                <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-ink/15 sm:hidden"></div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-violet" x-text="businessName"></p>
                <h3 class="mt-2 font-display text-2xl font-semibold" x-text="rewardName"></h3>
                <p class="mt-1 text-sm font-semibold text-violet" x-text="rewardPts"></p>

                <div class="loop-wallet mt-6 px-5 py-6 text-center">
                    <div class="loop-orb loop-orb--a !h-24 !w-24 !blur-2xl"></div>
                    <p class="relative text-[11px] font-semibold uppercase tracking-[0.18em] text-lime">{{ __('loop.ready_to_redeem') }}</p>
                    <p class="relative mt-3 font-display text-xl font-semibold text-white">{{ __('loop.redeem_ticket_hint') }}</p>
                    <template x-if="hotline">
                        <a :href="'tel:' + hotline.replace(/\s+/g, '')" class="relative mt-5 inline-flex items-center gap-2 rounded-2xl bg-lime px-4 py-3 text-sm font-semibold text-ink">
                            <span x-text="hotline"></span>
                        </a>
                    </template>
                </div>

                <button type="button" class="loop-btn-ghost mt-4 w-full" @click="close()">{{ __('loop.close') }}</button>
            </div>
        </div>
    </div>
</x-app-layout>
