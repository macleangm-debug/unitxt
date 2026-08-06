<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">Your Loop</h1>
        <p class="mt-1 text-ink-muted">{{ $totalPoints }} points across {{ $memberships->count() }} {{ Str::plural('shop brand', $memberships->count()) }}.</p>
    </x-slot>

    @forelse ($grouped as $sector => $items)
        <section class="mb-8">
            <h2 class="font-display text-lg font-semibold">{{ $sectors[$sector] ?? 'Other' }}</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach ($items as $membership)
                    <a href="{{ route('memberships.show', $membership->business) }}" class="loop-panel block p-5 transition hover:bg-white">
                        <p class="font-display text-lg font-semibold">{{ $membership->business->name }}</p>
                        <p class="text-sm text-ink-muted">{{ $membership->business->city }}</p>
                        <p class="mt-3 font-display text-3xl font-semibold">{{ $membership->points_balance }} <span class="text-base text-ink-muted">pts</span></p>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <div class="loop-panel p-8 text-center">
            <p class="text-ink-muted">No wallets yet. Visit a Loop shop, or browse campaigns.</p>
            <a href="{{ route('discover') }}" class="loop-btn mt-4">Discover shops</a>
        </div>
    @endforelse

    <section class="mt-4">
        <h2 class="font-display text-xl font-semibold">Discover in Tanzania</h2>
        @foreach ($discover as $sector => $businesses)
            <div class="mt-4">
                <p class="text-sm font-semibold uppercase tracking-wide text-ink-muted">{{ $sectors[$sector] ?? 'Other' }}</p>
                <div class="mt-2 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($businesses as $business)
                        <a href="{{ route('discover.show', $business) }}" class="loop-panel block p-4">
                            <p class="font-semibold">{{ $business->name }}</p>
                            <p class="text-sm text-ink-muted">{{ $business->city }} · {{ $business->shops_count }} shops</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </section>
</x-app-layout>
