<x-app-layout>
    <x-slot name="header">
        <div class="relative overflow-hidden rounded-[2rem] bg-ink px-6 py-8 text-white shadow-[0_24px_70px_rgba(11,31,42,0.16)] sm:px-8">
            <div class="pointer-events-none absolute -right-10 top-0 h-48 w-48 rounded-full bg-mint/25 blur-3xl"></div>
            <div class="pointer-events-none absolute -left-8 bottom-0 h-40 w-40 rounded-full bg-coral/20 blur-3xl"></div>
            <div class="relative flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint">Loop</p>
                    <h1 class="mt-2 font-display text-4xl font-semibold">{{ __('loop.your_loop') }}</h1>
                    <p class="mt-2 text-white/70">{{ number_format($totalPoints) }} {{ __('loop.pts') }} · {{ $memberships->count() }} {{ __('loop.places') }}</p>
                </div>
                <a href="{{ route('discover') }}" class="rounded-2xl bg-mint px-4 py-2.5 text-sm font-semibold text-ink">{{ __('loop.browse_campaigns') }}</a>
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

    <section class="mb-10 rounded-[2rem] border border-ink/8 bg-gradient-to-br from-white to-mint/10 p-6 sm:p-7">
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

    <section>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-display text-lg font-semibold">{{ __('loop.for_you') }}</h2>
            <a href="{{ route('discover') }}" class="text-sm font-semibold text-mint-deep">{{ __('loop.browse_campaigns') }}</a>
        </div>
        <div class="flex gap-3 overflow-x-auto pb-2 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            @forelse ($discover as $business)
                <a href="{{ route('discover.show', $business) }}" class="w-40 shrink-0 rounded-3xl border border-ink/10 bg-white p-3 shadow-sm transition hover:-translate-y-0.5">
                    @php $shop = $business->shops->first(); @endphp
                    @if ($shop)
                        <x-shop-logo :shop="$shop" class="h-20 w-full rounded-2xl object-cover" />
                    @else
                        <div class="flex h-20 items-center justify-center rounded-2xl bg-ink font-display text-lg text-mint">{{ mb_substr($business->name,0,1) }}</div>
                    @endif
                    <p class="mt-3 truncate text-sm font-semibold">{{ $business->name }}</p>
                    <p class="truncate text-[11px] text-ink-muted">{{ $sectors[$business->sector] ?? '' }} · {{ $business->city }}</p>
                </a>
            @empty
                <div class="loop-panel w-full p-6 text-sm text-ink-muted">{{ __('loop.explore_nearby') }}</div>
            @endforelse
        </div>
    </section>

    <section class="mt-10">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-display text-lg font-semibold">{{ __('loop.your_places') }}</h2>
        </div>
        <div class="flex gap-3 overflow-x-auto pb-2 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            @forelse ($memberships as $membership)
                <a href="{{ route('memberships.show', $membership->business) }}" class="w-36 shrink-0 rounded-3xl border border-ink/10 bg-white p-3 shadow-sm transition hover:-translate-y-0.5">
                    @php $shop = $membership->business->shops->first(); @endphp
                    @if ($shop)
                        <x-shop-logo :shop="$shop" class="h-16 w-16 rounded-2xl" />
                    @else
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-ink font-display text-lg text-mint">{{ mb_substr($membership->business->name,0,1) }}</div>
                    @endif
                    <p class="mt-3 truncate text-sm font-semibold">{{ $membership->business->name }}</p>
                    <p class="font-display text-xl font-semibold">{{ $membership->points_balance }}</p>
                    <p class="text-[11px] text-ink-muted">{{ __('loop.pts') }}</p>
                </a>
            @empty
                <div class="loop-panel w-full p-6 text-sm text-ink-muted">{{ __('loop.visit_or_browse') }}</div>
            @endforelse
            <a href="{{ route('discover') }}" class="flex w-36 shrink-0 flex-col items-center justify-center rounded-3xl border border-dashed border-ink/20 bg-chalk/60 p-3 text-center">
                <span class="text-2xl text-ink-muted">+</span>
                <span class="mt-2 text-xs font-semibold text-ink-muted">{{ __('loop.explore') }}</span>
            </a>
        </div>
    </section>
</x-app-layout>
