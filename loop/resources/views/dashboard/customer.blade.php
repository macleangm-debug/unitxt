<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.your_loop') }}</h1>
        <p class="mt-1 text-ink-muted">{{ $totalPoints }} pts · {{ $memberships->count() }} {{ Str::plural('place', $memberships->count()) }}</p>
    </x-slot>

    @if ($showWelcome ?? false)
        <div x-data="{ i: 0 }" class="mb-8 overflow-hidden rounded-3xl border border-ink/10 bg-white p-6 shadow-[0_18px_50px_rgba(11,31,42,0.06)]">
            <div x-show="i===0">
                <p class="font-display text-2xl font-semibold">{{ __('Everybody wins.') }}</p>
                <p class="mt-2 text-sm text-ink-muted">{{ __('Your points live on your phone. Offers are applied when you buy.') }}</p>
            </div>
            <div x-show="i===1" x-cloak>
                <p class="font-display text-2xl font-semibold">{{ __('Follow shops you love') }}</p>
                <p class="mt-2 text-sm text-ink-muted">{{ __('We’ll surface campaigns in your city and interests first.') }}</p>
            </div>
            <div class="mt-5 flex justify-between">
                <button type="button" class="text-sm font-semibold text-ink-muted" @click="i=0">1</button>
                <button type="button" class="text-sm font-semibold text-mint-deep" @click="i=1">{{ __('loop.next') }} →</button>
            </div>
        </div>
    @endif

    <section>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-display text-lg font-semibold">{{ __('Your places') }}</h2>
            <a href="{{ route('discover') }}" class="text-sm font-semibold text-mint-deep">{{ __('loop.browse_campaigns') }}</a>
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
                    <p class="text-[11px] text-ink-muted">pts</p>
                </a>
            @empty
                <div class="loop-panel w-full p-6 text-sm text-ink-muted">{{ __('Visit a Loop shop, or browse campaigns near you.') }}</div>
            @endforelse
            <a href="{{ route('discover') }}" class="flex w-36 shrink-0 flex-col items-center justify-center rounded-3xl border border-dashed border-ink/20 bg-chalk/60 p-3 text-center">
                <span class="text-2xl text-ink-muted">+</span>
                <span class="mt-2 text-xs font-semibold text-ink-muted">{{ __('Explore') }}</span>
            </a>
        </div>
    </section>

    <section class="mt-10">
        <h2 class="font-display text-lg font-semibold">{{ __('For you') }}</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($discover as $business)
                <a href="{{ route('discover.show', $business) }}" class="loop-panel flex items-center gap-3 p-4">
                    @php $shop = $business->shops->first(); @endphp
                    @if ($shop)
                        <x-shop-logo :shop="$shop" class="h-12 w-12 rounded-2xl" />
                    @endif
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $business->name }}</p>
                        <p class="truncate text-xs text-ink-muted">{{ $sectors[$business->sector] ?? '' }} · {{ $business->city }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
</x-app-layout>
