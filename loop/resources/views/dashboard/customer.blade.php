<x-app-layout>
    @php
        $phoneLabel = auth()->user()->full_phone ?? auth()->user()->phone;
        $customerName = auth()->user()->name ?: __('loop.member');
        $pointsEarned = (int) ($pointsEarned ?? 0);
    @endphp

    {{-- Living Wallet — name + points aligned to QR bottom --}}
    <section
        class="loop-wallet mb-8 px-5 py-7 sm:px-8 sm:py-9"
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

                <x-wallet-qr :size="120" class="shrink-0" />
            </div>

            @if (($featuredRedeem ?? null) === null && $redeemables->isNotEmpty())
                <div class="mt-6">
                    <a href="#ready" class="loop-btn-lime w-full sm:w-auto">{{ __('loop.see_rewards') }}</a>
                </div>
            @endif
        </div>
    </section>

    @if ($showWelcome ?? false)
        <div x-data="{ i: 0 }" class="mb-8 loop-divider pb-6">
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

    @if (($featuredRedeem ?? null) === null && $redeemables->isNotEmpty())
        <section
            id="ready"
            class="mb-10 scroll-mt-24"
        >
            <x-section-heading
                :eyebrow="__('loop.offers')"
                :title="__('loop.ready_to_redeem')"
                :blurb="__('loop.ready_to_redeem_home_blurb')"
                class="mb-5"
            />

            @if ($featuredRedeem)
                <div class="loop-unlock-card is-ready mt-4 overflow-hidden rounded-[1.5rem] bg-violet text-white">
                    <a href="{{ route('memberships.show', $featuredRedeem['business']) }}" class="block px-5 py-5 sm:px-6">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-lime">{{ __('loop.reward_unlocked') }}</p>
                        <p class="mt-1 text-sm text-white/70">{{ $featuredRedeem['business']->name }}</p>
                        <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
                            <div>
                                <p class="font-display text-2xl font-semibold tracking-tight">{{ $featuredRedeem['reward']->name }}</p>
                                <p class="mt-1 text-sm text-lime">{{ number_format($featuredRedeem['reward']->points_cost) }} {{ __('loop.pts') }}</p>
                            </div>
                            <span class="inline-flex rounded-xl bg-lime px-4 py-2.5 text-sm font-semibold text-ink">{{ __('loop.redeem') }}</span>
                        </div>
                    </a>
                </div>
            @else
                <div class="loop-carousel mt-4" x-data="loopParallaxCarousel()">
                    @foreach ($redeemables as $item)
                        <a
                            href="{{ route('memberships.show', $item['business']) }}"
                            data-loop-card
                            class="loop-shop-card loop-unlock-card is-ready w-[15.5rem] shrink-0 rounded-[1.35rem] bg-violet p-4 text-white"
                        >
                            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-lime">{{ __('loop.reward_unlocked') }}</p>
                            <p class="mt-1 truncate text-sm text-white/70">{{ $item['business']->name }}</p>
                            <p class="mt-2 font-display text-lg font-semibold leading-snug">{{ $item['reward']->name }}</p>
                            <p class="mt-2 text-sm font-semibold text-lime">{{ number_format($item['reward']->points_cost) }} {{ __('loop.pts') }}</p>
                            <p class="mt-4 text-sm font-semibold">{{ __('loop.redeem') }} →</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    {{-- Your Loop — same tile language as browse / discover --}}
    <section class="mb-10">
        <x-section-heading
            :eyebrow="__('loop.member')"
            :title="__('loop.your_loop')"
            :blurb="__('loop.my_wallets_blurb')"
            :href="route('discover')"
            :link="__('loop.browse_campaigns').' →'"
            class="mb-5"
        />
        @if ($memberships->isNotEmpty())
            <div class="loop-carousel items-stretch" x-data="loopParallaxCarousel()">
                @foreach ($memberships as $membership)
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
                    <p class="text-sm text-ink-muted">{{ __('loop.visit_or_browse') }}</p>
                </div>
            </div>
        @endif
    </section>

    @if (($recent ?? collect())->isNotEmpty())
        <section class="mb-10">
            <x-section-heading
                :eyebrow="__('loop.activity')"
                :title="__('loop.recent')"
                class="mb-5"
            />
            <div class="space-y-2">
                @foreach ($recent as $row)
                    <div class="flex items-center justify-between rounded-[1.25rem] border border-ink/8 bg-white/80 px-4 py-3">
                        <p class="text-sm font-semibold">{{ $row->shop_name }}</p>
                        <p class="font-display text-sm font-semibold {{ $row->points >= 0 ? 'text-mint-deep' : 'text-coral' }}">
                            {{ $row->points >= 0 ? '+' : '' }}{{ $row->points }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Where offers work — browse-tile carousel --}}
    <section class="mb-10">
        <x-section-heading
            :eyebrow="__('loop.discover')"
            :title="__('loop.where_points_work')"
            :blurb="__('loop.where_points_work_blurb')"
            :href="route('discover')"
            :link="__('loop.explore').' →'"
            class="mb-5"
        />
        @if ($topShops->isNotEmpty())
            <div class="loop-carousel items-stretch" x-data="loopParallaxCarousel()">
                @foreach ($topShops as $business)
                    @php
                        $cheapest = $business->rewards->first();
                        $headline = $cheapest?->name;
                        $footnote = $cheapest
                            ? __('loop.from_points', ['points' => $cheapest->points_cost])
                            : null;
                        $memberPoints = $memberships->firstWhere('business_id', $business->id)?->points_balance;
                    @endphp
                    <x-discover-tile
                        :business="$business"
                        :points="$memberPoints"
                        :show-points="$memberPoints !== null"
                        :carousel="true"
                        :headline="$headline"
                        :footnote="$footnote"
                        data-loop-card
                    />
                @endforeach
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
                </div>
            </div>
        @endif
    </section>

    @if ($otherShops->isNotEmpty())
        <section class="mb-12">
            <x-section-heading
                :eyebrow="__('loop.more_businesses_eyebrow')"
                :title="__('loop.more_businesses')"
                :blurb="__('loop.more_businesses_blurb')"
                :href="route('discover')"
                :link="__('loop.explore').' →'"
                class="mb-5"
            />
            <div class="loop-carousel items-stretch" x-data="loopParallaxCarousel()">
                @foreach ($otherShops as $business)
                    <x-discover-tile
                        :business="$business"
                        :carousel="true"
                        data-loop-card
                    />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Scout as social share --}}
    <section class="mb-4 rounded-[1.5rem] bg-ink px-5 py-6 text-white sm:px-7">
        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-lime">Loop</p>
        <h2 class="mt-2 font-display text-2xl font-semibold tracking-tight">{{ __('loop.know_a_shop_title') }}</h2>
        <p class="mt-2 max-w-md text-sm text-white/65">{{ __('loop.know_a_shop_body') }}</p>
        <x-invite-business />
    </section>
</x-app-layout>
