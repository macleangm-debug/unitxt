<x-app-layout>
    @php
        $shop = $business->shops->first();
        $logoUrl = $business->logoUrl();
        $morphId = 'business-'.$business->id;
        $points = (int) $membership->points_balance;
        $readyCount = $rewards->filter(fn ($r) => ($membership->progressTo($r)['ready'] ?? false))->count();
    @endphp

    {{-- Same wallet hero as the public business page --}}
    <div x-data>
    <section class="loop-wallet relative overflow-hidden p-5 sm:p-8">
        <div class="relative grid gap-5 sm:gap-6 lg:grid-cols-[auto_1fr_auto] lg:items-center">
            <div class="mx-auto lg:mx-0">
                <div
                    data-loop-morph-target="{{ $morphId }}"
                    class="loop-morph-logo loop-vt-logo flex h-28 w-28 items-center justify-center overflow-hidden rounded-[1.5rem] bg-ink ring-4 ring-white/15 sm:h-36 sm:w-36"
                    style="view-transition-name: loop-biz-logo"
                >
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $business->name }}" class="h-full w-full object-cover" draggable="false">
                    @elseif ($shop)
                        <x-shop-logo :shop="$shop" class="h-full w-full rounded-[1.5rem]" />
                    @else
                        <span class="font-display text-4xl font-semibold text-lime">{{ mb_substr($business->name, 0, 1) }}</span>
                    @endif
                </div>
            </div>
            <div class="min-w-0 text-center lg:text-left">
                <h1 class="font-display text-3xl font-semibold sm:text-4xl">{{ $business->name }}</h1>
                <p class="mt-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-lime">{{ $business->sectorLabel() }}</p>
                <p class="mt-1.5 text-sm text-white/70">
                    {{ $business->city ?: $shop?->city }} · {{ $business->country }}
                    @if ($business->shops->count() > 1)
                        · {{ $business->shops->count() }} {{ __('loop.branches') }}
                    @endif
                </p>
                @if ($business->description)
                    <p class="mt-1.5 max-w-xl text-sm text-white/75 lg:mx-0 mx-auto">{{ $business->description }}</p>
                @endif
                @if (! empty($paused))
                    <p class="mt-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-lime">{{ __('loop.member_paused_title') }}</p>
                    <p class="mt-1 text-sm text-white/70">{{ __('loop.member_paused_points', ['points' => number_format($points)]) }}</p>
                    @php $readyReward = $rewards->first(fn ($r) => ($membership->progressTo($r)['ready'] ?? false)); @endphp
                    @if ($readyReward)
                        <p class="mt-1 text-sm text-white/80">{{ __('loop.member_paused_reward', ['reward' => $readyReward->name, 'name' => $business->name]) }}</p>
                    @endif
                    <div class="mt-3 text-left">
                        <x-want-loop-back :business="$business" :wanted-back="$wantedBack ?? false" />
                    </div>
                @endif
            </div>
            <div class="text-center">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white/50">{{ __('loop.pts') }}</p>
                <div class="mt-1.5" x-data="loopCountUp({{ $points }})">
                    <p class="font-display text-3xl font-semibold text-lime" x-text="formatted()">{{ number_format($points) }}</p>
                </div>
            </div>
        </div>
        <div class="mt-6 flex flex-col items-center">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-white/50">{{ __('loop.wallet_qr_title') }}</p>
            <div x-ref="qrBox" class="mt-3">
                <x-wallet-qr :size="148" />
            </div>
            <p class="mt-3 text-sm font-semibold text-lime">{{ __('loop.show_at_till') }}</p>
            @if ($business->hotline && empty($paused))
                <a href="tel:{{ preg_replace('/\s+/', '', $business->hotline) }}" class="mt-5 inline-flex items-center gap-2.5 rounded-2xl bg-lime px-4 py-2.5 font-display text-base font-semibold text-ink transition hover:bg-white">
                    <span>{{ $business->hotline }}</span>
                </a>
                <p class="mt-1.5 text-xs text-white/55">{{ __('loop.call_hotline_cta') }}</p>
            @endif
        </div>
    </section>

    @if (empty($paused))
        <div class="mt-4 flex flex-wrap gap-3">
            @if ($readyCount > 0)
                <button type="button" class="loop-btn-lime" @click="$refs.qrBox.querySelector('button')?.click()">{{ __('loop.show_at_till') }}</button>
            @else
                <a href="#offers" class="loop-btn-lime">{{ __('loop.see_rewards') }}</a>
            @endif
        </div>
    @endif
    </div>

    @if ($business->shops->isNotEmpty())
        <section class="mt-6">
            <h2 class="font-display text-lg font-semibold">{{ __('loop.branches') }}</h2>
            <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
                @foreach ($business->shops as $branch)
                    <div class="rounded-2xl border border-ink/10 bg-white px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $branch->name }}</p>
                                <p class="mt-0.5 text-sm text-ink-muted">
                                    @if ($branch->address){{ $branch->address }} · @endif{{ $branch->city }}
                                </p>
                            </div>
                            @if ($membership->shop_id === $branch->id || $business->shops->count() === 1)
                                <span class="shrink-0 rounded-lg bg-violet-soft px-2.5 py-1 text-xs font-semibold text-violet-deep">
                                    {{ number_format($points) }} {{ __('loop.pts') }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <div x-data="loopRedeem()">
        @if ($raffleWins->isNotEmpty())
            <section class="mt-6">
                <h2 class="font-display text-lg font-semibold">{{ __('loop.your_raffle_wins') }}</h2>
                <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
                    @foreach ($raffleWins as $win)
                        @php
                            $expired = $win->status !== 'claimed' && $win->daysUntilClaim() !== null && $win->daysUntilClaim() < 0;
                            $ready = $win->isOpenToClaim() && ! $expired;
                        @endphp
                        <div class="loop-offer-card {{ $ready ? 'loop-offer-card--ready' : '' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    @if ($ready)
                                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-lime">{{ __('loop.raffle_won_badge') }}</p>
                                    @endif
                                    <p class="font-display text-lg font-semibold {{ $ready ? 'text-white' : 'text-ink' }}">{{ $win->raffle->prize_name }}</p>
                                    <p class="mt-0.5 text-sm {{ $ready ? 'text-white/65' : 'text-ink-muted' }}">{{ $win->raffle->name }}</p>
                                </div>
                                <p class="shrink-0 rounded-xl px-2.5 py-1 text-xs font-semibold {{ $ready ? 'bg-lime text-ink' : 'bg-ink/5 text-ink-muted' }}">
                                    {{ __('loop.raffle_winner_status_'.$win->status) }}
                                </p>
                            </div>
                            <p class="mt-2 text-xs {{ $ready ? 'font-semibold text-lime' : 'text-ink-muted' }}">{{ $win->claimHeadline() }}</p>
                            @if ($win->claim_by && $win->status !== 'claimed')
                                <p class="mt-0.5 text-xs {{ $ready ? 'text-white/70' : 'text-ink-muted' }}">{{ __('loop.claim_by') }} {{ $win->claim_by->format('d M Y') }}</p>
                            @endif
                            @if ($ready && $business->hotline)
                                <a href="tel:{{ preg_replace('/\s+/', '', $business->hotline) }}" class="mt-3 inline-flex w-full items-center justify-center rounded-xl bg-lime px-3 py-2.5 text-sm font-semibold text-ink">
                                    {{ __('loop.call_to_claim') }}
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Offers --}}
        <section id="offers" class="mt-6 scroll-mt-24">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-display text-lg font-semibold">{{ __('loop.offers_at_till') }}</h2>
                @if ($readyCount > 0)
                    <span class="rounded-lg bg-violet-soft px-2.5 py-1 text-xs font-semibold text-violet-deep">
                        {{ __('loop.offers_ready_count', ['count' => $readyCount]) }}
                    </span>
                @endif
            </div>

            <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
                @forelse ($rewards as $reward)
                    @php
                        $progress = $membership->progressTo($reward);
                        $canRedeem = $progress['ready'];
                        $needed = $progress['needed'];
                    @endphp
                    <div class="loop-offer-card {{ $canRedeem ? 'loop-offer-card--ready' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                @if ($canRedeem)
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-lime">{{ __('loop.reward_unlocked') }}</p>
                                @endif
                                <p class="font-semibold {{ $canRedeem ? 'text-white' : 'text-ink' }}">{{ $reward->name }}</p>
                                <p class="mt-0.5 text-sm {{ $canRedeem ? 'text-white/65' : 'text-ink-muted' }}">{{ $reward->label() }}</p>
                            </div>
                            <p class="shrink-0 rounded-lg px-2.5 py-1 text-xs font-semibold {{ $canRedeem ? 'bg-lime text-ink' : 'bg-ink/5 text-ink-muted' }}">
                                {{ number_format((int) $reward->points_cost) }} {{ __('loop.pts') }}
                            </p>
                        </div>
                        <div class="mt-3">
                            <div class="h-1.5 overflow-hidden rounded-full {{ $canRedeem ? 'bg-white/20' : 'bg-ink/10' }}">
                                <div
                                    class="loop-fill h-full rounded-full {{ $canRedeem ? 'bg-lime' : 'bg-violet' }}"
                                    style="width: {{ $progress['percent'] }}%"
                                ></div>
                            </div>
                            <p class="mt-1.5 text-xs {{ $canRedeem ? 'font-semibold text-lime' : 'text-ink-muted' }}">
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
                                class="mt-3 inline-flex w-full items-center justify-center rounded-xl bg-lime px-3 py-2.5 text-sm font-semibold text-ink"
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
                    <p class="text-sm text-ink-muted">{{ __('loop.no_offers_yet') }}</p>
                @endforelse
            </div>
        </section>

        {{-- Activity --}}
        <section class="mt-6">
            <h2 class="font-display text-lg font-semibold">{{ __('loop.points_activity') }}</h2>
            <div class="mt-3 overflow-hidden rounded-2xl border border-ink/10 bg-white">
                @forelse ($transactions as $tx)
                    @php
                        $label = $tx->points >= 0
                            ? ($tx->visit_id ? __('loop.points_visit_earned') : __('loop.points_earned_private'))
                            : ($tx->visit_id ? __('loop.points_visit_spent') : __('loop.points_spent_private'));
                    @endphp
                    <div class="flex items-center justify-between gap-3 border-b border-ink/5 px-4 py-3 last:border-b-0">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink">{{ $label }}</p>
                            <p class="mt-0.5 text-xs text-ink-muted">{{ $tx->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="shrink-0 font-display text-lg font-semibold {{ $tx->points >= 0 ? 'text-violet' : 'text-coral' }}">
                            {{ $tx->points >= 0 ? '+' : '' }}{{ number_format((int) $tx->points) }}
                        </span>
                    </div>
                @empty
                    <p class="px-4 py-6 text-sm text-ink-muted">{{ __('loop.no_history_yet') }}</p>
                @endforelse
            </div>
        </section>

        <x-loop-sheet model="open">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-violet" x-text="businessName"></p>
                <h3 class="mt-2 font-display text-2xl font-semibold" x-text="rewardName"></h3>
                <p class="mt-1 text-sm font-semibold text-violet" x-text="rewardPts"></p>

                <div class="loop-wallet loop-wallet--liquid mt-6 px-5 py-6 text-center">
                    <div class="loop-orb loop-orb--a !h-24 !w-24 !blur-2xl"></div>
                    <div class="loop-orb loop-orb--b !h-20 !w-20 !blur-2xl"></div>
                    <p class="relative text-[11px] font-semibold uppercase tracking-[0.18em] text-lime">{{ __('loop.ready_to_redeem') }}</p>
                    <p class="relative mt-3 font-display text-xl font-semibold text-white">{{ __('loop.redeem_ticket_hint') }}</p>
                    <div class="loop-ticket-qr relative" aria-hidden="true">
                        @for ($i = 0; $i < 25; $i++)
                            <span></span>
                        @endfor
                    </div>
                    <p class="loop-ticket-code relative" x-text="code"></p>
                    <template x-if="hotline">
                        <a :href="'tel:' + hotline.replace(/\s+/g, '')" class="relative mt-5 inline-flex items-center gap-2 rounded-2xl bg-lime px-4 py-3 text-sm font-semibold text-ink">
                            <span x-text="hotline"></span>
                        </a>
                    </template>
                </div>

                <button type="button" class="loop-btn-ghost mt-4 w-full" @click="close()">{{ __('loop.close') }}</button>
        </x-loop-sheet>
    </div>
</x-app-layout>
