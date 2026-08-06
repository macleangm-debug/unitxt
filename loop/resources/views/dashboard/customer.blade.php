<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold text-ink">Your Loop</h1>
                <p class="mt-1 text-ink-muted">{{ $totalPoints }} points across {{ $memberships->count() }} {{ Str::plural('business', $memberships->count()) }}.</p>
            </div>
            <a href="{{ route('visits.create') }}" class="loop-btn-mint">Check in at a shop</a>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <section class="loop-panel p-6 animate-fade-up">
            <h2 class="font-display text-xl font-semibold">My wallets</h2>
            <div class="mt-4 space-y-3">
                @forelse ($memberships as $membership)
                    <a href="{{ route('memberships.show', $membership->business) }}" class="block rounded-xl bg-chalk px-4 py-4 transition hover:bg-mint-soft">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $membership->business->name }}</p>
                                <p class="text-sm text-ink-muted">Member {{ $membership->member_code }}</p>
                            </div>
                            <p class="font-display text-2xl font-semibold">{{ $membership->points_balance }}</p>
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-ink-muted">Join a business or check in at a shop to start earning.</p>
                @endforelse
            </div>
        </section>

        <section class="loop-panel p-6 animate-fade-up-delay">
            <h2 class="font-display text-xl font-semibold">Recent check-ins</h2>
            <div class="mt-4 space-y-3">
                @forelse ($recentVisits as $visit)
                    <div class="rounded-xl bg-chalk px-4 py-3">
                        <p class="font-semibold">{{ $visit->shop->name }}</p>
                        <p class="text-sm text-ink-muted">{{ $visit->business->name }} · +{{ $visit->points_earned }} pts</p>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">No visits yet.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="mt-8 animate-fade-up-delay-2">
        <h2 class="font-display text-xl font-semibold">Discover businesses</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($discover as $business)
                <div class="loop-panel p-5">
                    <p class="font-display text-lg font-semibold">{{ $business->name }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ $business->category ?: 'Local business' }} · {{ $business->shops_count }} shops</p>
                    <form method="POST" action="{{ route('memberships.join', $business) }}" class="mt-4">
                        @csrf
                        <button class="loop-btn-ghost w-full">Join & earn</button>
                    </form>
                </div>
            @endforeach
        </div>
    </section>
</x-app-layout>
