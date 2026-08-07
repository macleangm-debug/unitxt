<x-app-layout>
    <x-slot name="header">
        <div class="relative overflow-hidden rounded-[2rem] bg-ink px-6 py-9 text-white shadow-[0_28px_80px_rgba(11,31,42,0.18)] sm:px-10 sm:py-11">
            <div class="pointer-events-none absolute -right-16 -top-10 h-56 w-56 rounded-full bg-mint/30 blur-3xl"></div>
            <div class="pointer-events-none absolute -left-12 bottom-0 h-44 w-44 rounded-full bg-coral/25 blur-3xl"></div>
            <div class="relative grid gap-6 sm:grid-cols-[1.4fr_auto] sm:items-end">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-mint">Loop</p>
                    <h1 class="mt-3 font-display text-4xl font-semibold tracking-tight sm:text-5xl">{{ __('loop.your_loop') }}</h1>
                    <p class="mt-3 max-w-md text-base text-white/70">
                        {{ number_format($totalPoints) }} {{ __('loop.pts') }}
                        <span class="mx-2 text-white/35">·</span>
                        {{ $memberships->count() }} {{ __('loop.places') }}
                    </p>
                </div>
                <a href="{{ route('discover') }}" class="inline-flex items-center justify-center rounded-2xl bg-mint px-5 py-3 text-sm font-semibold text-ink transition hover:bg-mint-deep">{{ __('loop.browse_campaigns') }}</a>
            </div>
        </div>
    </x-slot>

    @if ($showWelcome ?? false)
        <div x-data="{ i: 0 }" class="mb-8 overflow-hidden rounded-3xl border border-ink/8 bg-white/90 p-6 shadow-[0_18px_50px_rgba(11,31,42,0.06)]">
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

    <section class="mb-8">
        <div class="mb-3 flex items-end justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.ready_to_redeem') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.ready_to_redeem_home_blurb') }}</p>
            </div>
        </div>
        <div class="flex gap-3 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            @forelse ($redeemables as $item)
                <a href="{{ route('memberships.show', $item['business']) }}" class="w-56 shrink-0 rounded-[1.5rem] border border-mint/25 bg-gradient-to-br from-mint/15 to-white p-4 shadow-sm transition hover:-translate-y-0.5">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ $item['business']->name }}</p>
                    <p class="mt-2 font-display text-lg font-semibold leading-snug">{{ $item['reward']->name }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ $item['reward']->points_cost }} {{ __('loop.pts') }} · {{ $item['reward']->label() }}</p>
                    <p class="mt-3 text-sm font-semibold text-ink">{{ __('loop.ready') }} →</p>
                </a>
            @empty
                <div class="w-full rounded-[1.5rem] border border-dashed border-ink/15 bg-white/70 px-5 py-6 text-sm text-ink-muted">
                    {{ __('loop.no_ready_offers') }}
                    <a href="{{ route('discover') }}" class="mt-2 block font-semibold text-mint-deep">{{ __('loop.browse_campaigns') }} →</a>
                </div>
            @endforelse
        </div>
    </section>

    <section class="mb-8">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.your_places') }}</h2>
        </div>
        <div class="flex gap-3 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            @forelse ($memberships as $membership)
                <a href="{{ route('memberships.show', $membership->business) }}" class="w-36 shrink-0 rounded-[1.5rem] border border-ink/10 bg-white p-3 shadow-sm transition hover:-translate-y-0.5">
                    @php $shop = $membership->business->shops->first(); @endphp
                    @if ($shop)
                        <x-shop-logo :shop="$shop" class="h-14 w-14 rounded-2xl" />
                    @else
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-ink font-display text-lg text-mint">{{ mb_substr($membership->business->name,0,1) }}</div>
                    @endif
                    <p class="mt-2 truncate text-sm font-semibold leading-snug">{{ $membership->business->name }}</p>
                    <p class="font-display text-xl font-semibold leading-tight">{{ $membership->points_balance }}</p>
                    <p class="text-[11px] text-ink-muted">{{ __('loop.pts') }}</p>
                </a>
            @empty
                <div class="loop-panel w-full p-5 text-sm text-ink-muted">{{ __('loop.visit_or_browse') }}</div>
            @endforelse
            <a href="{{ route('discover') }}" class="flex w-36 shrink-0 flex-col items-center justify-center rounded-[1.5rem] border border-dashed border-ink/20 bg-chalk/60 p-3 text-center">
                <span class="text-2xl text-ink-muted">+</span>
                <span class="mt-2 text-xs font-semibold text-ink-muted">{{ __('loop.explore') }}</span>
            </a>
        </div>
    </section>

    <section class="mb-8">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.top_businesses') }}</h2>
            <a href="{{ route('discover') }}" class="text-sm font-semibold text-mint-deep">{{ __('loop.browse_campaigns') }}</a>
        </div>
        <div class="flex gap-3 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            @forelse ($topShops as $business)
                <a href="{{ route('discover.show', $business) }}" class="w-40 shrink-0 rounded-[1.5rem] border border-ink/10 bg-white p-3 shadow-sm transition hover:-translate-y-0.5">
                    @php $shop = $business->shops->first(); @endphp
                    @if ($shop)
                        <x-shop-logo :shop="$shop" class="h-16 w-full rounded-2xl object-cover" />
                    @else
                        <div class="flex h-16 items-center justify-center rounded-2xl bg-ink font-display text-lg text-mint">{{ mb_substr($business->name,0,1) }}</div>
                    @endif
                    <p class="mt-2 truncate text-sm font-semibold leading-snug">{{ $business->name }}</p>
                    <p class="truncate text-[11px] text-ink-muted">{{ $sectors[$business->sector] ?? '' }} · {{ $business->city }}</p>
                </a>
            @empty
                <div class="loop-panel w-full p-5 text-sm text-ink-muted">{{ __('loop.explore_nearby') }}</div>
            @endforelse
        </div>
    </section>

    @if ($otherShops->isNotEmpty())
        <section class="mb-10">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.more_businesses') }}</h2>
            </div>
            <div class="flex gap-3 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($otherShops as $business)
                    <a href="{{ route('discover.show', $business) }}" class="w-40 shrink-0 rounded-[1.5rem] border border-ink/10 bg-white p-3 shadow-sm transition hover:-translate-y-0.5">
                        @php $shop = $business->shops->first(); @endphp
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

    <section class="rounded-[2rem] border border-ink/8 bg-gradient-to-br from-white to-mint/10 p-6 sm:p-7">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.be_a_loop_scout') }}</p>
        <h2 class="mt-2 font-display text-2xl font-semibold">{{ __('loop.invite_a_business') }}</h2>
        <p class="mt-2 max-w-xl text-sm text-ink-muted">{{ __('loop.invite_a_business_body') }}</p>
        <form method="POST" action="{{ route('business-invites.store') }}" class="mt-5 grid gap-3 sm:grid-cols-[1fr_auto]">
            @csrf
            <input name="business_name" class="loop-input" placeholder="{{ __('loop.business_name') }}" required>
            <button class="loop-btn-mint">{{ __('loop.send_invite') }}</button>
            <input name="city" value="{{ auth()->user()->city }}" type="hidden">
            <input name="phone" class="loop-input sm:col-span-2" placeholder="{{ __('loop.phone_optional') }}">
        </form>
    </section>
</x-app-layout>
