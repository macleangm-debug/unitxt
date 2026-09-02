<x-app-layout>
    @php
        $phoneLabel = auth()->user()->full_phone ?? auth()->user()->phone;
        $customerName = auth()->user()->name ?: __('loop.member');
        $pointsEarned = (int) ($pointsEarned ?? 0);
    @endphp

    {{-- Living Wallet — name + points aligned to QR bottom --}}
    <section
        class="loop-wallet px-5 py-6 sm:px-7 sm:py-7"
        :class="{ 'loop-wallet--pulse': pulsing }"
        x-data="loopLivingWallet({{ $pointsEarned }})"
    >
        <div class="loop-orb loop-orb--a"></div>
        <div class="loop-orb loop-orb--b"></div>
        <div class="loop-orb loop-orb--c"></div>
        <div class="relative">
            <div class="flex items-end justify-between gap-4">
                <div class="flex min-w-0 flex-1 flex-col justify-between self-stretch">
                    <div class="min-w-0 pt-0.5">
                        <h1 class="font-display text-[clamp(1.75rem,7vw,2.5rem)] font-semibold leading-tight tracking-tight text-white">
                            {{ $customerName }}
                        </h1>
                        <p class="mt-1.5 text-sm text-white/55">{{ $phoneLabel }}</p>
                    </div>

                    <div class="mt-6">
                        @if ($featuredRedeem ?? null)
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-lime">🎁</p>
                            <p class="mt-2 font-display text-[clamp(1.85rem,7vw,2.75rem)] font-semibold leading-tight tracking-tight text-white">{{ __('loop.reward_ready_title', ['reward' => $featuredRedeem['reward']->name]) }}</p>
                            <p class="mt-1 text-sm text-white/65">{{ __('loop.reward_unlocked_at', ['shop' => $featuredRedeem['business']->name]) }}</p>
                            <a href="{{ route('memberships.show', $featuredRedeem['business']) }}" class="loop-btn-lime mt-4 inline-flex">{{ __('loop.use_reward') }}</a>
                            <p class="mt-4 font-display text-2xl font-semibold text-white/80">
                                <span class="relative" x-data="loopCountUp({{ (int) $totalPoints }}, 800, {{ $pointsEarned }})" x-text="formatted()">{{ number_format($totalPoints) }}</span>
                                <span class="text-sm font-medium uppercase tracking-[0.16em] text-white/45">{{ __('loop.pts') }}</span>
                            </p>
                        @else
                        <div class="relative" x-data="loopCountUp({{ (int) $totalPoints }}, 800, {{ $pointsEarned }})">
                            <template x-if="earned">
                                <span class="loop-points-float" x-text="'+' + earned"></span>
                            </template>
                            <p class="font-display text-[clamp(3.25rem,13vw,5rem)] font-semibold leading-none tracking-tight text-lime" x-text="formatted()">{{ number_format($totalPoints) }}</p>
                        </div>
                        <p class="mt-2 text-sm font-medium uppercase tracking-[0.16em] text-white/55">{{ __('loop.pts') }}</p>
                        <p class="mt-2 text-sm text-white/70">
                            {{ __('loop.across_shops', ['count' => $memberships->count()]) }}
                        </p>
                        @if ($redeemables->isNotEmpty())
                            <a href="#ready" class="mt-3 inline-flex items-center gap-2 rounded-full bg-lime px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-ink">
                                {{ trans_choice('loop.offer_ready_badge', $redeemables->count(), ['count' => $redeemables->count()]) }}
                            </a>
                        @endif
                        @endif
                    </div>
                </div>

                <div class="flex shrink-0 flex-col items-center">
                    <x-wallet-qr :size="120" class="shrink-0" />
                    <p class="mt-2 text-center text-[10px] font-semibold uppercase tracking-[0.14em] text-white/50">{{ __('loop.wallet_qr_title') }}</p>
                    <p class="mt-0.5 text-center text-[10px] text-white/45">{{ __('loop.show_at_till') }}</p>
                </div>
            </div>

            @if (($featuredRedeem ?? null) === null && $redeemables->isNotEmpty())
                <div class="mt-6">
                    <a href="#ready" class="loop-btn-lime w-full sm:w-auto">{{ __('loop.see_rewards') }}</a>
                </div>
            @endif
        </div>
    </section>

    @if (($pendingPlays ?? collect())->isNotEmpty())
        <section class="mt-6">
            @foreach ($pendingPlays as $play)
                <a href="{{ route('games.play', $play) }}" class="loop-panel mb-3 flex items-center justify-between gap-3 p-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ $play->game?->typeLabel() }}</p>
                        <p class="mt-1 font-display text-xl font-semibold">{{ __('loop.you_have_a_play') }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ $play->business?->name }}</p>
                    </div>
                    <span class="loop-btn-mint !py-2">{{ __('loop.play_now') }}</span>
                </a>
            @endforeach
        </section>
    @endif

    @if (($pendingRaffleWins ?? collect())->isNotEmpty())
        <section class="mt-6">
            <x-section-heading
                :eyebrow="__('loop.raffle')"
                :title="__('loop.ready_to_claim_raffle')"
                :blurb="__('loop.raffle_won_home_blurb')"
                class="mb-3"
            />
            <div class="loop-carousel mt-4" x-data="loopParallaxCarousel()">
                @foreach ($pendingRaffleWins as $win)
                    <a
                        href="{{ route('memberships.show', $win->raffle->business) }}"
                        data-loop-card
                        class="loop-shop-card loop-unlock-card is-ready w-[15.5rem] shrink-0 rounded-[1.35rem] bg-violet p-4 text-white"
                    >
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-lime">{{ __('loop.raffle_won_badge') }}</p>
                        <p class="mt-1 truncate text-sm text-white/70">{{ $win->raffle->business?->name }}</p>
                        <p class="mt-2 font-display text-lg font-semibold leading-snug">{{ $win->raffle->prize_name }}</p>
                        <p class="mt-2 text-sm font-semibold text-lime">{{ $win->claimHeadline() }}</p>
                        <p class="mt-4 text-sm font-semibold">{{ __('loop.call_to_claim') }} →</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($showWelcome ?? false)
        <div x-data="{ i: 0 }" class="mt-6 loop-divider pb-4">
            <div x-show="i===0" x-transition.opacity>
                <p class="font-display text-2xl font-semibold">{{ __('loop.tagline') }}</p>
                <p class="mt-2 text-sm text-ink-muted">{{ __('loop.customer_welcome_1') }}</p>
            </div>
            <div x-show="i===1" x-cloak x-transition.opacity>
                <p class="font-display text-2xl font-semibold">{{ __('loop.customer_welcome_2_title') }}</p>
                <p class="mt-2 text-sm text-ink-muted">{{ __('loop.customer_welcome_2') }}</p>
            </div>
            <div class="mt-5 flex justify-between">
                <div class="flex gap-1.5">
                    <button type="button" class="h-1.5 w-6 rounded-full" :class="i===0 ? 'bg-violet' : 'bg-ink/15'" @click="i=0"></button>
                    <button type="button" class="h-1.5 w-6 rounded-full" :class="i===1 ? 'bg-violet' : 'bg-ink/15'" @click="i=1"></button>
                </div>
                <button type="button" class="text-sm font-semibold text-violet" @click="i = i === 0 ? 1 : 0">{{ __('loop.next') }} →</button>
            </div>
        </div>
    @endif

    @if ($redeemables->isNotEmpty())
        <section id="ready" class="mt-6 scroll-mt-24">
            <x-section-heading
                :eyebrow="__('loop.nav_rewards')"
                :title="__('loop.ready_for_you')"
                :href="route('memberships.index')"
                :link="__('loop.see_all_rewards')"
                class="mb-3"
            />
            <p class="text-sm text-ink-muted">{{ trans_choice('loop.rewards_waiting_count', $redeemables->count(), ['count' => $redeemables->count()]) }}</p>
            <div class="mt-3 space-y-2">
                @foreach ($homeRedeemables as $item)
                    <a href="{{ route('memberships.show', $item['business']) }}" class="flex items-center justify-between gap-3 rounded-[1.25rem] border border-ink/8 bg-white/90 px-4 py-3">
                        <span class="min-w-0">
                            <span class="block truncate font-semibold">{{ $item['business']->name }}</span>
                            <span class="mt-0.5 block truncate text-sm text-violet">{{ $item['reward']->name }}</span>
                        </span>
                        <span class="shrink-0 text-[12px] font-semibold text-ink-muted">{{ __('loop.use_reward') }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Your businesses — memberships you already have --}}
    <section class="mt-6">
        <x-section-heading
            :eyebrow="__('loop.member')"
            :title="__('loop.your_businesses')"
            :href="route('memberships.index')"
            :link="__('loop.see_all').' →'"
            class="mb-5"
        />
        @if (($homeMemberships ?? $memberships)->isNotEmpty())
            <div class="loop-carousel items-stretch" x-data="loopParallaxCarousel()">
                @foreach ($homeMemberships ?? $memberships as $membership)
                    @php
                        $progress = $membership->home_progress ?? ['percent' => 0, 'needed' => 0, 'ready' => false];
                        $target = $membership->home_target_reward;
                        $footnote = null;
                        if ($target) {
                            $footnote = ($progress['ready'] ?? false)
                                ? __('loop.reward_unlocked')
                                : __('loop.pts_to_unlock_named', [
                                    'points' => $progress['needed'],
                                    'offer' => $target->name,
                                ]);
                        }
                    @endphp
                    <x-discover-tile
                        :business="$membership->business"
                        :points="$membership->points_balance"
                        :show-points="true"
                        :carousel="true"
                        :footnote="$footnote"
                        data-loop-card
                    />
                @endforeach
                <a href="{{ route('discover') }}" data-loop-card class="group flex w-40 shrink-0 flex-col sm:w-44">
                    <div class="flex flex-1 flex-col items-center justify-center overflow-hidden rounded-[1.5rem] border border-dashed border-ink/20 bg-white/60 px-3 py-8 shadow-[0_12px_40px_rgba(17,17,20,0.04)]">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-soft text-2xl font-semibold text-violet">+</span>
                        <span class="mt-3 text-center text-sm font-semibold text-ink-muted">{{ __('loop.explore') }}</span>
                    </div>
                </a>
            </div>
        @else
            <div class="flex items-stretch gap-4">
                <a href="{{ route('discover') }}" class="group flex w-40 shrink-0 flex-col sm:w-44">
                    <div class="flex flex-1 flex-col items-center justify-center overflow-hidden rounded-[1.5rem] border border-dashed border-ink/20 bg-white/60 px-3 py-8 shadow-[0_12px_40px_rgba(17,17,20,0.04)]">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-soft text-2xl font-semibold text-violet">+</span>
                        <span class="mt-3 text-center text-sm font-semibold text-ink-muted">{{ __('loop.explore') }}</span>
                    </div>
                </a>
                <div class="flex max-w-xs flex-col justify-center py-2">
                    <p class="font-semibold">{{ __('loop.redeem_places_empty_title') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.redeem_places_empty') }}</p>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.visit_or_browse') }}</p>
                </div>
            </div>
        @endif
    </section>

    @if (($offersForYou ?? collect())->isNotEmpty())
        <section class="mt-6">
            <x-section-heading
                :eyebrow="__('loop.offers')"
                :title="__('loop.offers_for_you')"
                :href="route('discover', ['status' => 'offers'])"
                :link="__('loop.see_all').' →'"
                class="mb-5"
            />
            <div class="loop-carousel items-stretch" x-data="loopParallaxCarousel()">
                @foreach ($offersForYou as $business)
                    <x-discover-tile :business="$business" :carousel="true" data-loop-card />
                @endforeach
            </div>
        </section>
    @endif

    @if (($recent ?? collect())->isNotEmpty())
        <section class="mt-6">
            <x-section-heading
                :eyebrow="__('loop.activity')"
                :title="__('loop.recent_activity')"
                :href="route('member.activity')"
                :link="__('loop.see_all_activity')"
                class="mb-3"
            />
            <div class="space-y-2">
                @foreach ($recent as $row)
                    <div class="flex items-center justify-between rounded-[1.25rem] border border-ink/8 bg-white/80 px-4 py-3">
                        <p class="text-sm font-semibold">{{ $row->shop_name }}</p>
                        <p class="font-display text-sm font-semibold {{ $row->points >= 0 ? 'text-mint-deep' : 'text-coral' }}">
                            {{ $row->points >= 0 ? '+' : '' }}{{ number_format((int) $row->points) }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if (($nearYou ?? collect())->isNotEmpty())
        <section class="mt-6">
            <x-section-heading
                :eyebrow="__('loop.discover')"
                :title="__('loop.near_you')"
                :href="route('discover')"
                :link="__('loop.see_all').' →'"
                class="mb-5"
            />
            <div class="loop-carousel items-stretch" x-data="loopParallaxCarousel()">
                @foreach ($nearYou as $business)
                    <x-discover-tile :business="$business" :carousel="true" data-loop-card />
                @endforeach
            </div>
        </section>
    @endif

    @if (($popularAround ?? collect())->isNotEmpty())
        <section class="mt-6">
            <x-section-heading
                :eyebrow="__('loop.discover')"
                :title="__('loop.popular_around_you')"
                :href="route('discover')"
                :link="__('loop.see_all').' →'"
                class="mb-5"
            />
            <div class="loop-carousel items-stretch" x-data="loopParallaxCarousel()">
                @foreach ($popularAround as $business)
                    <x-discover-tile :business="$business" :carousel="true" data-loop-card />
                @endforeach
            </div>
        </section>
    @endif

    @if (($newOnLoop ?? collect())->isNotEmpty())
        <section class="mt-6">
            <x-section-heading
                :eyebrow="__('loop.discover')"
                :title="__('loop.new_on_loop')"
                :href="route('discover')"
                :link="__('loop.see_all').' →'"
                class="mb-5"
            />
            <div class="loop-carousel items-stretch" x-data="loopParallaxCarousel()">
                @foreach ($newOnLoop as $business)
                    <x-discover-tile :business="$business" :carousel="true" data-loop-card />
                @endforeach
            </div>
        </section>
    @endif

    <section class="loop-invite-card mb-4 mt-6 overflow-hidden rounded-[1.75rem] p-5 text-white sm:p-7">
        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-lime">{{ __('loop.invite_card_eyebrow') }}</p>
        <h2 class="mt-2 font-display text-2xl font-semibold tracking-tight">{{ __('loop.know_a_shop_title') }}</h2>
        <p class="mt-2 max-w-md text-sm text-white/70">{{ __('loop.know_a_shop_body') }}</p>
        <x-invite-business
            button-class="loop-btn-lime mt-5"
            :button-label="__('loop.share_invite')"
        />
    </section>
</x-app-layout>

