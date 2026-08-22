<x-app-layout>
    @php
        $shop = $business->shops->first();
        $logoUrl = $business->logoUrl();
        $morphId = 'business-'.$business->id;
        $points = (int) $membership->points_balance;
        $readyCount = $rewards->filter(fn ($r) => ($membership->progressTo($r)['ready'] ?? false))->count();
    @endphp

    {{-- Business wallet hero — same language as customer home --}}
    <section class="loop-wallet relative mb-8 overflow-hidden px-5 py-7 sm:px-8 sm:py-9" x-data>
        <div class="loop-orb loop-orb--a"></div>
        <div class="loop-orb loop-orb--b"></div>
        <div class="loop-orb loop-orb--c"></div>

        <div class="relative">
            <div class="flex items-end justify-between gap-4">
                <div class="flex min-w-0 flex-1 flex-col justify-between self-stretch">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <div
                                data-loop-morph-target="{{ $morphId }}"
                                class="loop-morph-logo loop-vt-logo flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-[1.1rem] bg-white/10 ring-2 ring-white/20 sm:h-16 sm:w-16"
                                style="view-transition-name: loop-biz-logo"
                            >
                                @if ($logoUrl)
                                    <img src="{{ $logoUrl }}" alt="{{ $business->name }}" class="h-full w-full object-cover" draggable="false">
                                @elseif ($shop)
                                    <x-shop-logo :shop="$shop" class="h-full w-full rounded-[1.1rem]" />
                                @else
                                    <span class="font-display text-2xl font-semibold text-lime">{{ mb_substr($business->name, 0, 1) }}</span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-lime/85">Loop</p>
                                <h1 class="mt-1 truncate font-display text-xl font-semibold leading-tight tracking-tight text-white sm:text-2xl">
                                    {{ $business->name }}
                                </h1>
                                <p class="mt-0.5 truncate text-[11px] font-semibold uppercase tracking-[0.14em] text-white/45">
                                    {{ $business->sectorLabel() }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-7">
                        @php
                            $readyReward = $rewards->first(fn ($r) => ($membership->progressTo($r)['ready'] ?? false));
                        @endphp
                        @if (! empty($paused))
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-lime">{{ __('loop.member_paused_title') }}</p>
                            <p class="mt-2 font-display text-[clamp(3.25rem,13vw,5rem)] font-semibold leading-none tracking-tight text-lime">
                                {{ number_format($points) }}
                            </p>
                            <p class="mt-2 text-sm text-white/70">{{ __('loop.member_paused_points', ['points' => number_format($points)]) }}</p>
                            @if ($readyReward)
                                <p class="mt-3 text-sm text-white/80">{{ __('loop.member_paused_reward', ['reward' => $readyReward->name, 'name' => $business->name]) }}</p>
                            @endif
                            <x-want-loop-back :business="$business" :wanted-back="$wantedBack ?? false" />
                        @elseif ($readyReward)
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-lime">{{ __('loop.youve_got_something') }}</p>
                            <p class="mt-2 font-display text-[clamp(1.85rem,7vw,2.75rem)] font-semibold leading-tight tracking-tight text-white">{{ $readyReward->name }}</p>
                            <p class="mt-4 font-display text-2xl font-semibold text-white/80">
                                <x-count-up :value="$points" class="font-display" />
                                <span class="text-sm font-medium uppercase tracking-[0.16em] text-white/45">{{ __('loop.pts') }}</span>
                            </p>
                        @else
                            <div x-data="loopCountUp({{ $points }})">
                                <p class="font-display text-[clamp(3.25rem,13vw,5rem)] font-semibold leading-none tracking-tight text-lime" x-text="formatted()">
                                    {{ number_format($points) }}
                                </p>
                            </div>
                            <p class="mt-2 text-sm font-medium uppercase tracking-[0.16em] text-white/55">{{ __('loop.pts') }}</p>
                            <p class="mt-2 text-sm text-white/70">{{ __('loop.at_this_shop_balance') }}</p>
                        @endif
                    </div>
                </div>

                <div x-ref="qrBox" class="shrink-0">
                    <x-wallet-qr :size="112" />
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                @if ($readyCount > 0)
                    <button type="button" class="loop-btn-lime" @click="$refs.qrBox.querySelector('button')?.click()">{{ __('loop.show_at_till') }}</button>
                @else
                    <a href="#offers" class="loop-btn-lime">{{ __('loop.see_rewards') }}</a>
                @endif
                @if ($business->hotline)
                    <a
                        href="tel:{{ preg_replace('/\s+/', '', $business->hotline) }}"
                        class="inline-flex items-center justify-center rounded-2xl border border-white/20 bg-white/5 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/10"
                    >
                        {{ __('loop.call_hotline_cta') }}
                    </a>
                @endif
            </div>
        </div>
    </section>

    <div x-data="loopRedeem()">
        @if ($raffleWins->isNotEmpty())
            <section class="mb-8 overflow-hidden rounded-[1.75rem] border border-violet/20 bg-violet-soft/40">
                <div class="px-5 py-4 sm:px-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.raffle') }}</p>
                    <h2 class="mt-1 font-display text-xl font-semibold">{{ __('loop.your_raffle_wins') }}</h2>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.your_raffle_wins_body') }}</p>
                </div>
                <div class="space-y-3 border-t border-ink/5 px-5 py-4 sm:px-6">
                    @foreach ($raffleWins as $win)
                        @php
                            $expired = $win->status !== 'claimed' && $win->daysUntilClaim() !== null && $win->daysUntilClaim() < 0;
                        @endphp
                        <div class="rounded-2xl bg-white px-4 py-3.5 ring-1 ring-ink/5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-display text-lg font-semibold">{{ $win->raffle->prize_name }}</p>
                                    <p class="mt-0.5 text-sm text-ink-muted">{{ $win->raffle->name }} · {{ __('loop.raffle_winner_status_'.$win->status) }}</p>
                                </div>
                                @if ($win->status !== 'claimed')
                                    <span class="rounded-xl px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.1em] {{ $expired ? 'bg-coral/15 text-coral' : 'bg-ink text-lime' }}">
                                        {{ $win->claimHeadline() }}
                                    </span>
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

        {{-- Offers --}}
        <section id="offers" class="mb-10 scroll-mt-24">
            <x-section-heading
                :eyebrow="__('loop.offers')"
                :title="__('loop.offers_at_this_shop')"
                :blurb="__('loop.offers_at_this_shop_body')"
                class="mb-5"
            />

            <div class="mb-5 flex items-center justify-between gap-3 rounded-[1.35rem] border border-ink/8 bg-white/70 px-4 py-3.5 shadow-[0_10px_30px_rgba(17,17,20,0.04)] backdrop-blur-xl">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-ink-muted">{{ __('loop.your_balance_here') }}</p>
                    <p class="mt-0.5 font-display text-2xl font-semibold tracking-tight text-ink">
                        {{ number_format($points) }}
                        <span class="text-sm font-medium text-ink-muted">{{ __('loop.pts') }}</span>
                    </p>
                </div>
                @if ($readyCount > 0)
                    <span class="rounded-xl bg-violet px-3 py-1.5 text-xs font-semibold text-white">
                        {{ __('loop.offers_ready_count', ['count' => $readyCount]) }}
                    </span>
                @endif
            </div>

            <div class="loop-carousel items-stretch" x-data="loopParallaxCarousel()">
                @forelse ($rewards as $reward)
                    @php
                        $progress = $membership->progressTo($reward);
                        $canRedeem = $progress['ready'];
                        $needed = $progress['needed'];
                    @endphp
                    <div
                        data-loop-card
                        class="loop-shop-card loop-offer-card w-[16.75rem] shrink-0 {{ $canRedeem ? 'loop-offer-card--ready' : '' }}"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                @if ($canRedeem)
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-lime">{{ __('loop.reward_unlocked') }}</p>
                                @endif
                                <p class="font-display text-lg font-semibold leading-snug {{ $canRedeem ? 'text-white' : 'text-ink' }}">{{ $reward->name }}</p>
                                <p class="mt-0.5 text-sm {{ $canRedeem ? 'text-white/65' : 'text-ink-muted' }}">{{ $reward->label() }}</p>
                            </div>
                            <p class="shrink-0 rounded-xl px-2.5 py-1 text-xs font-semibold {{ $canRedeem ? 'bg-lime text-ink' : 'bg-ink/5 text-ink-muted' }}">
                                {{ $reward->points_cost }} {{ __('loop.pts') }}
                            </p>
                        </div>
                        <div class="mt-4">
                            <div class="h-1.5 overflow-hidden rounded-full {{ $canRedeem ? 'bg-white/20' : 'bg-ink/10' }}">
                                <div
                                    class="loop-fill h-full rounded-full {{ $canRedeem ? 'bg-lime' : 'bg-violet' }}"
                                    style="width: {{ $progress['percent'] }}%"
                                ></div>
                            </div>
                            <p class="mt-2 text-xs {{ $canRedeem ? 'font-semibold text-lime' : 'text-ink-muted' }}">
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
                                class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-lime px-3 py-2.5 text-sm font-semibold text-ink"
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
                    <p class="py-6 text-sm text-ink-muted">{{ __('loop.no_offers_yet') }}</p>
                @endforelse
            </div>
        </section>

        {{-- Activity --}}
        <section class="mb-4">
            <x-section-heading
                :eyebrow="__('loop.pts')"
                :title="__('loop.points_activity')"
                :blurb="__('loop.points_activity_blurb')"
                class="mb-5"
            />

            <div class="overflow-hidden rounded-[1.6rem] border border-ink/8 bg-white/75 shadow-[0_12px_36px_rgba(17,17,20,0.04)] backdrop-blur-xl">
                @forelse ($transactions as $tx)
                    @php
                        $label = $tx->points >= 0
                            ? ($tx->visit_id ? __('loop.points_visit_earned') : __('loop.points_earned_private'))
                            : ($tx->visit_id ? __('loop.points_visit_spent') : __('loop.points_spent_private'));
                    @endphp
                    <div class="flex items-center justify-between gap-3 border-b border-ink/5 px-5 py-4 last:border-b-0">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink">{{ $label }}</p>
                            <p class="mt-0.5 text-xs text-ink-muted">{{ $tx->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="shrink-0 font-display text-xl font-semibold {{ $tx->points >= 0 ? 'text-violet' : 'text-coral' }}">
                            {{ $tx->points >= 0 ? '+' : '' }}{{ $tx->points }}
                        </span>
                    </div>
                @empty
                    <p class="px-5 py-8 text-sm text-ink-muted">{{ __('loop.no_history_yet') }}</p>
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
                x-transition:enter-start="translate-y-10 scale-95 opacity-0"
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
            </div>
        </div>
    </div>
</x-app-layout>
