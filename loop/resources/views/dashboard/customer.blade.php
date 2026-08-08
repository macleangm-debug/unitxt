<x-app-layout>
    {{-- Slim wallet chrome: not competing with redeem --}}
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4 border-b border-ink/8 pb-5">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-mint-deep">Loop</p>
                <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight text-ink sm:text-4xl">{{ __('loop.your_loop') }}</h1>
                <p class="mt-1.5 text-sm text-ink-muted">
                    <span class="font-semibold text-ink">{{ number_format($totalPoints) }}</span> {{ __('loop.pts') }}
                    <span class="mx-1.5 text-ink/25">·</span>
                    {{ $memberships->count() }} {{ __('loop.places') }}
                </p>
            </div>
            <a href="{{ route('discover') }}" class="text-sm font-semibold text-mint-deep hover:text-ink">{{ __('loop.browse_campaigns') }} →</a>
        </div>
    </x-slot>

    @if ($showWelcome ?? false)
        <div x-data="{ i: 0 }" class="mb-8 overflow-hidden border-b border-ink/8 pb-6">
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
                    <button type="button" class="h-1.5 w-6 rounded-full" :class="i===0 ? 'bg-mint' : 'bg-ink/15'" @click="i=0"></button>
                    <button type="button" class="h-1.5 w-6 rounded-full" :class="i===1 ? 'bg-mint' : 'bg-ink/15'" @click="i=1"></button>
                </div>
                <button type="button" class="text-sm font-semibold text-mint-deep" @click="i = i === 0 ? 1 : 0">{{ __('loop.next') }} →</button>
            </div>
        </div>
    @endif

    {{-- Primary job: ready to redeem --}}
    <section class="mb-10">
        @if ($featuredRedeem)
            <a href="{{ route('memberships.show', $featuredRedeem['business']) }}" class="group block overflow-hidden rounded-[1.75rem] bg-ink px-6 py-7 text-white shadow-[0_28px_70px_rgba(11,31,42,0.22)] transition hover:-translate-y-0.5 sm:px-8 sm:py-8">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-mint">{{ $featuredRedeem['business']->name }}</p>
                <div class="mt-3 flex flex-wrap items-end justify-between gap-5">
                    <div class="min-w-0">
                        <h2 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ $featuredRedeem['reward']->name }}</h2>
                        <p class="mt-2 text-sm text-white/65">{{ $featuredRedeem['reward']->points_cost }} {{ __('loop.pts') }} · {{ $featuredRedeem['reward']->label() }}</p>
                    </div>
                    <span class="inline-flex rounded-xl bg-mint px-4 py-2.5 text-sm font-semibold text-ink transition group-hover:bg-mint-deep">{{ __('loop.ready') }} →</span>
                </div>
            </a>
        @elseif ($redeemables->isNotEmpty())
            <div class="mb-4">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.ready_to_redeem') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.ready_to_redeem_home_blurb') }}</p>
            </div>
            <div class="loop-carousel">
                @foreach ($redeemables as $item)
                    <a href="{{ route('memberships.show', $item['business']) }}" class="w-56 shrink-0 rounded-2xl bg-ink p-4 text-white transition hover:-translate-y-0.5">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-mint">{{ $item['business']->name }}</p>
                        <p class="mt-2 font-display text-lg font-semibold leading-snug">{{ $item['reward']->name }}</p>
                        <p class="mt-1 text-sm text-white/60">{{ $item['reward']->points_cost }} {{ __('loop.pts') }} · {{ $item['reward']->label() }}</p>
                        <p class="mt-4 text-sm font-semibold text-mint">{{ __('loop.ready') }} →</p>
                    </a>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-ink/15 bg-white/50 px-5 py-5">
                <h2 class="font-display text-lg font-semibold">{{ __('loop.ready_to_redeem') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.no_ready_offers') }}</p>
                <a href="{{ route('discover') }}" class="mt-3 inline-flex text-sm font-semibold text-mint-deep">{{ __('loop.browse_campaigns') }} →</a>
            </div>
        @endif
    </section>

    {{-- Your places --}}
    <section class="mb-10">
        <div class="mb-4 flex items-baseline justify-between gap-3">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.your_places') }}</h2>
        </div>
        <div class="loop-carousel">
            @forelse ($memberships as $membership)
                @php
                    $target = $membership->home_target_reward;
                    $progress = $membership->home_progress ?? ['percent' => 0, 'needed' => 0, 'ready' => false];
                    $shop = $membership->business->shops->first();
                @endphp
                <a href="{{ route('memberships.show', $membership->business) }}" class="w-40 shrink-0">
                    @if ($shop)
                        <x-shop-logo :shop="$shop" class="h-14 w-14 rounded-2xl" />
                    @else
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-ink font-display text-lg text-mint">{{ mb_substr($membership->business->name,0,1) }}</div>
                    @endif
                    <p class="mt-2.5 truncate text-sm font-semibold leading-snug">{{ $membership->business->name }}</p>
                    <p class="font-display text-2xl font-semibold leading-none tracking-tight">{{ $membership->points_balance }}</p>
                    <p class="mt-0.5 text-[11px] text-ink-muted">{{ __('loop.pts') }}</p>
                    @if ($target)
                        <div class="mt-2.5">
                            <div class="h-1 overflow-hidden rounded-full bg-ink/10">
                                <div class="h-full rounded-full {{ $progress['ready'] ? 'bg-mint' : 'bg-ink/35' }}" style="width: {{ $progress['percent'] }}%"></div>
                            </div>
                            <p class="mt-1 truncate text-[10px] {{ $progress['ready'] ? 'font-semibold text-mint-deep' : 'text-ink-muted' }}">
                                @if ($progress['ready'])
                                    {{ __('loop.ready') }} · {{ $target->name }}
                                @else
                                    {{ __('loop.pts_to_unlock', ['points' => $progress['needed']]) }}
                                @endif
                            </p>
                        </div>
                    @endif
                </a>
            @empty
                <div class="w-full py-2 text-sm text-ink-muted">{{ __('loop.visit_or_browse') }}</div>
            @endforelse
            <a href="{{ route('discover') }}" class="flex w-28 shrink-0 flex-col items-start justify-center border-l border-dashed border-ink/15 pl-4">
                <span class="text-2xl leading-none text-ink/35">+</span>
                <span class="mt-2 text-xs font-semibold text-ink-muted">{{ __('loop.explore') }}</span>
            </a>
        </div>
    </section>

    {{-- Where offers work --}}
    <section class="mb-10">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.where_points_work') }}</h2>
                <p class="mt-1 max-w-md text-sm text-ink-muted">{{ __('loop.where_points_work_blurb') }}</p>
            </div>
            <a href="{{ route('discover') }}" class="text-sm font-semibold text-mint-deep">{{ __('loop.browse_campaigns') }}</a>
        </div>
        <div class="loop-carousel">
            @forelse ($topShops as $business)
                @php
                    $cheapest = $business->rewards->first();
                    $shop = $business->shops->first();
                @endphp
                <a href="{{ route('discover.show', $business) }}" class="w-40 shrink-0">
                    @if ($shop)
                        <x-shop-logo :shop="$shop" class="h-16 w-full rounded-2xl object-cover" />
                    @else
                        <div class="flex h-16 items-center justify-center rounded-2xl bg-ink font-display text-lg text-mint">{{ mb_substr($business->name,0,1) }}</div>
                    @endif
                    <p class="mt-2 truncate text-sm font-semibold leading-snug">{{ $business->name }}</p>
                    <p class="truncate text-[11px] text-ink-muted">{{ $sectors[$business->sector] ?? '' }} · {{ $business->city }}</p>
                    @if ($cheapest)
                        <p class="mt-1.5 text-[11px] font-semibold text-mint-deep">{{ __('loop.from_points', ['points' => $cheapest->points_cost]) }}</p>
                    @endif
                </a>
            @empty
                <div class="w-full py-2 text-sm text-ink-muted">{{ __('loop.explore_nearby') }}</div>
            @endforelse
        </div>
    </section>

    @if ($otherShops->isNotEmpty())
        <section class="mb-12">
            <div class="mb-4">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.more_businesses') }}</h2>
            </div>
            <div class="loop-carousel">
                @foreach ($otherShops as $business)
                    @php $shop = $business->shops->first(); @endphp
                    <a href="{{ route('discover.show', $business) }}" class="w-40 shrink-0">
                        @if ($shop)
                            <x-shop-logo :shop="$shop" class="h-16 w-full rounded-2xl object-cover" />
                        @else
                            <div class="flex h-16 items-center justify-center rounded-2xl bg-ink font-display text-lg text-mint">{{ mb_substr($business->name,0,1) }}</div>
                        @endif
                        <p class="mt-2 truncate text-sm font-semibold leading-snug">{{ $business->name }}</p>
                        <p class="truncate text-[11px] text-ink-muted">{{ $sectors[$business->sector] ?? '' }} · {{ $business->city }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Scout: share-first, not a bolted-on lead form --}}
    <section class="rounded-[1.75rem] bg-ink px-6 py-7 text-white sm:px-8">
        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-mint">{{ __('loop.be_a_loop_scout') }}</p>
        <h2 class="mt-2 font-display text-2xl font-semibold tracking-tight">{{ __('loop.invite_a_business') }}</h2>
        <p class="mt-2 max-w-lg text-sm text-white/65">{{ __('loop.invite_a_business_share_blurb') }}</p>
        <form method="POST" action="{{ route('business-invites.store') }}" class="mt-6 space-y-3">
            @csrf
            <input name="city" value="{{ auth()->user()->city }}" type="hidden">
            <input name="country_code" value="{{ \App\Support\Countries::dial(auth()->user()->country ?? 'TZ') }}" type="hidden">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-stretch">
                <input name="business_name" class="loop-input !mt-0 !border-white/15 !bg-white/10 !text-white placeholder:!text-white/40 sm:flex-1" placeholder="{{ __('loop.business_name') }}" required>
                <button name="share_via" value="whatsapp" class="loop-btn-mint inline-flex shrink-0 items-center justify-center gap-2">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.5 3.5A11 11 0 004.1 17.7L3 21l3.4-1A11 11 0 1020.5 3.5zm-8.5 17a9 9 0 01-4.6-1.3l-.3-.2-2.5.7.7-2.4-.2-.3A9 9 0 1120 12a9 9 0 01-8 8.5zm5-6.6c-.3-.1-1.6-.8-1.8-.9s-.4-.1-.6.1-.7.9-.8 1-.3.2-.6.1a7.3 7.3 0 01-2.1-1.3 8 8 0 01-1.5-1.8c-.2-.3 0-.4.1-.6l.4-.5.3-.4c.1-.2 0-.3 0-.5l-.8-2c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3s-1 1-1 2.4 1 2.8 1.2 3 .2.3 2 3.1a13.4 13.4 0 005.2 3.4c.6.2 1.1.2 1.5.1.5-.1 1.6-.6 1.8-1.3s.2-1.1.2-1.2-.2-.2-.5-.3z"/></svg>
                    {{ __('loop.send_invite') }}
                </button>
            </div>
            <input name="phone" class="loop-input !mt-0 !border-white/15 !bg-white/10 !text-white placeholder:!text-white/40" placeholder="{{ __('loop.phone_optional_share') }}">
            <button name="share_via" value="sms" class="inline-flex items-center gap-2 text-sm font-semibold text-white/70 transition hover:text-white">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20 2H4a2 2 0 00-2 2v18l4-4h14a2 2 0 002-2V4a2 2 0 00-2-2zm0 14H5.2L4 17.2V4h16v12z"/></svg>
                {{ __('loop.share_by_sms') }}
            </button>
            <p class="text-xs text-white/45">{{ __('loop.scout_share_hint') }}</p>
        </form>
    </section>
</x-app-layout>
