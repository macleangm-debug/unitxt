<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ $business->name }}</h1>
                <p class="mt-1 text-ink-muted">Member {{ $membership->member_code }} · Lifetime {{ $membership->lifetime_points }} pts</p>
            </div>
            <p class="font-display text-4xl font-semibold text-ink">{{ $membership->points_balance }} <span class="text-lg text-ink-muted">pts</span></p>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="loop-panel p-6">
            <h2 class="font-display text-xl font-semibold">Point history</h2>
            <div class="mt-4 space-y-3">
                @forelse ($transactions as $tx)
                    <div class="flex items-center justify-between rounded-xl bg-chalk px-4 py-3">
                        <div>
                            <p class="font-medium">{{ $tx->description }}</p>
                            <p class="text-xs text-ink-muted">{{ $tx->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="font-semibold {{ $tx->points >= 0 ? 'text-mint-deep' : 'text-coral' }}">
                            {{ $tx->points >= 0 ? '+' : '' }}{{ $tx->points }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">No transactions yet.</p>
                @endforelse
            </div>
        </section>

        <section class="loop-panel p-6">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-xl font-semibold">Rewards</h2>
                <a href="{{ route('rewards.catalog', $business) }}" class="text-sm font-semibold text-mint-deep hover:underline">View all</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($rewards as $reward)
                    <div class="rounded-xl bg-chalk px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $reward->name }}</p>
                                <p class="text-sm text-ink-muted">{{ $reward->points_cost }} points</p>
                            </div>
                            <form method="POST" action="{{ route('rewards.redeem', $reward) }}">
                                @csrf
                                <button class="loop-btn-ghost !px-3 !py-2" @disabled($membership->points_balance < $reward->points_cost)>
                                    Redeem
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">No rewards available yet.</p>
                @endforelse
            </div>

            <h2 class="mt-8 font-display text-xl font-semibold">Recent visits</h2>
            <div class="mt-4 space-y-3">
                @forelse ($visits as $visit)
                    <div class="rounded-xl bg-chalk px-4 py-3">
                        <p class="font-semibold">{{ $visit->shop->name }}</p>
                        <p class="text-sm text-ink-muted">{{ $visit->campaign?->name }} · +{{ $visit->points_earned }} pts</p>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">No visits yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
