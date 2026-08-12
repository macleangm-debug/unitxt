<x-app-layout>
    @php
        $shareLink = \App\Support\PlatformUrl::route('business.register', ['scout' => auth()->id()]);
        $phoneLabel = auth()->user()->full_phone ?? auth()->user()->phone;
        $customerName = auth()->user()->name ?: __('loop.member');
        $pointsEarned = (int) ($pointsEarned ?? 0);
    @endphp

    {{-- Living Wallet — name + premium Loop QR --}}
    <section
        class="loop-wallet mb-8 px-5 py-7 sm:px-8 sm:py-9"
        :class="{ 'loop-wallet--pulse': pulsing }"
        x-data="loopLivingWallet({{ $pointsEarned }})"
    >
        <div class="loop-orb loop-orb--a"></div>
        <div class="loop-orb loop-orb--b"></div>
        <div class="loop-orb loop-orb--c"></div>
        <div class="relative">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-display text-2xl font-semibold tracking-tight text-white sm:text-3xl">Loop</p>
                    <p class="mt-1 truncate font-display text-lg font-semibold text-white/95 sm:text-xl">{{ $customerName }}</p>
                    <p class="mt-0.5 text-sm text-white/55">{{ $phoneLabel }}</p>
                </div>
                <a
                    href="{{ route('discover') }}"
                    class="shrink-0 rounded-2xl border border-white/15 bg-white/5 px-3 py-1.5 text-xs font-semibold text-white/85 transition hover:bg-white/10"
                    @click="$store.loopNav.go(@js(route('discover')), $event)"
                >{{ __('loop.browse_campaigns') }}</a>
            </div>

            <div class="mt-6 flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0 flex-1">
                    <div class="relative" x-data="loopCountUp({{ (int) $totalPoints }}, 800, {{ $pointsEarned }})">
                        <template x-if="earned">
                            <span class="loop-points-float" x-text="'+' + earned"></span>
                        </template>
                        <p class="font-display text-[clamp(3.5rem,14vw,5.5rem)] font-semibold leading-none tracking-tight text-lime" x-text="formatted()">{{ number_format($totalPoints) }}</p>
                    </div>
                    <p class="mt-2 text-sm font-medium uppercase tracking-[0.16em] text-white/55">{{ __('loop.pts') }}</p>
                    <p class="mt-3 text-base text-white/75">
                        {{ __('loop.across_shops', ['count' => $memberships->count()]) }}
                    </p>
                    <div class="mt-6">
                        <a href="#ready" class="loop-btn-lime w-full sm:w-auto">{{ __('loop.see_rewards') }}</a>
                    </div>
                    <p class="mt-4 max-w-xs text-[11px] leading-relaxed text-white/45">{{ __('loop.wallet_qr_hint') }}</p>
                </div>

                <x-wallet-qr
                    :name="$customerName"
                    :phone="$phoneLabel"
                    :size="128"
                    class="mx-auto sm:mx-0"
                />
            </div>
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

    {{-- Ready to redeem --}}
    <section
        id="ready"
        class=" mb-10 scroll-mt-24"
    >
        <h2 class="font-display text-xl font-semibold">{{ __('loop.ready_to_redeem') }}</h2>

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
        @elseif ($redeemables->isNotEmpty())
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
        @else
            <p class="mt-3 text-sm text-ink-muted">{{ __('loop.no_ready_offers') }}</p>
            <a href="{{ route('discover') }}" class="mt-3 inline-flex text-sm font-semibold text-violet">{{ __('loop.browse_campaigns') }} →</a>
        @endif
    </section>

    {{-- Your Loop — same tile language as browse / discover --}}
    <section class=" mb-10">
        <div class="mb-4 flex flex-wrap items-baseline justify-between gap-3">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.your_loop') }}</h2>
            <a href="{{ route('discover') }}" class="text-sm font-semibold text-violet">{{ __('loop.browse_campaigns') }} →</a>
        </div>
        <div class="loop-carousel items-stretch" x-data="loopParallaxCarousel()">
            @forelse ($memberships as $membership)
                @php
                    $progress = $membership->home_progress ?? ['percent' => 0, 'needed' => 0, 'ready' => false];
                    $target = $membership->home_target_reward;
                    $footnote = null;
                    if ($target) {
                        $footnote = ($progress['ready'] ?? false)
                            ? __('loop.reward_unlocked')
                            : __('loop.pts_to_unlock', ['points' => $progress['needed']]);
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
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.visit_or_browse') }}</p>
            @endforelse
            <a href="{{ route('discover') }}" data-loop-card class="group flex w-40 shrink-0 flex-col sm:w-44">
                <div class="flex flex-1 flex-col items-center justify-center overflow-hidden rounded-[1.5rem] border border-dashed border-ink/20 bg-white/60 px-3 py-8 shadow-[0_12px_40px_rgba(17,17,20,0.04)]">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-soft text-2xl font-semibold text-violet">+</span>
                    <span class="mt-3 text-center text-sm font-semibold text-ink-muted">{{ __('loop.explore') }}</span>
                </div>
            </a>
        </div>
    </section>

    {{-- Where offers work — browse-tile carousel --}}
    <section class="mb-10">
        <div class="mb-1 flex flex-wrap items-end justify-between gap-2">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.where_points_work') }}</h2>
            <a href="{{ route('discover') }}" class="text-sm font-semibold text-violet">{{ __('loop.browse_campaigns') }}</a>
        </div>
        <p class="mb-4 text-sm text-ink-muted">{{ __('loop.where_points_work_blurb') }}</p>
        <div class="loop-carousel items-stretch" x-data="loopParallaxCarousel()">
            @forelse ($topShops as $business)
                @php
                    $cheapest = $business->rewards->first();
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
                    :footnote="$footnote"
                    data-loop-card
                />
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.explore_nearby') }}</p>
            @endforelse
        </div>
    </section>

    @if ($otherShops->isNotEmpty())
        <section class="mb-12">
            <h2 class="mb-4 font-display text-xl font-semibold">{{ __('loop.more_businesses') }}</h2>
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
    <section
        x-data="{ open: false, copied: false }"
        class="mb-4 rounded-[1.5rem] bg-ink px-5 py-6 text-white sm:px-7"
    >
        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-lime">Loop</p>
        <h2 class="mt-2 font-display text-2xl font-semibold tracking-tight">{{ __('loop.know_a_shop_title') }}</h2>
        <p class="mt-2 max-w-md text-sm text-white/65">{{ __('loop.know_a_shop_body') }}</p>
        <button type="button" class="loop-btn-lime mt-6" @click="open = true">{{ __('loop.share_loop') }}</button>

        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 flex items-end justify-center bg-ink/50 p-0 sm:items-center sm:p-6"
            @keydown.escape.window="open = false"
        >
            <div class="absolute inset-0" @click="open = false"></div>
            <div class="relative w-full max-w-md rounded-t-[1.5rem] bg-white p-5 text-ink sm:rounded-[1.5rem] sm:p-6" @click.stop>
                <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-ink/15 sm:hidden"></div>
                <h3 class="font-display text-xl font-semibold">{{ __('loop.share_loop_sheet_title') }}</h3>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.share_loop_sheet_body') }}</p>

                <form method="POST" action="{{ route('business-invites.store') }}" class="mt-5 space-y-3">
                    @csrf
                    <input type="hidden" name="city" value="{{ auth()->user()->city }}">
                    <input type="hidden" name="country_code" value="{{ \App\Support\Countries::dial(auth()->user()->country ?? 'TZ') }}">
                    <input type="hidden" name="business_name" value="{{ __('loop.a_shop_you_love') }}">
                    <button name="share_via" value="whatsapp" class="loop-btn w-full">{{ __('loop.share_on_whatsapp') }}</button>
                    <button name="share_via" value="sms" class="loop-btn-ghost w-full">{{ __('loop.share_by_sms') }}</button>
                </form>

                <button
                    type="button"
                    class="loop-btn-ghost mt-3 w-full"
                    @click="
                        navigator.clipboard.writeText(@js($shareLink));
                        copied = true;
                        setTimeout(() => copied = false, 1800);
                    "
                    x-text="copied ? @js(__('loop.link_copied')) : @js(__('loop.copy_link'))"
                ></button>

                <button type="button" class="mt-4 w-full py-2 text-sm font-semibold text-ink-muted" @click="open = false">{{ __('loop.close') }}</button>
            </div>
        </div>
    </section>
</x-app-layout>
