<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ $business->name }}</h1>
                <p class="mt-1 text-ink-muted">Show this at the till — staff apply rewards when you buy.</p>
            </div>
            <p class="font-display text-4xl font-semibold">{{ $membership->points_balance }} <span class="text-lg text-ink-muted">pts</span></p>
        </div>
    </x-slot>

    <section class="loop-panel p-6 mb-6">
        <h2 class="font-display text-xl font-semibold">Your rewards here</h2>
        <div class="mt-4 space-y-3">
            @foreach ($rewards as $reward)
                <div class="rounded-xl bg-chalk px-4 py-3 flex justify-between gap-3">
                    <div>
                        <p class="font-semibold">{{ $reward->name }}</p>
                        <p class="text-sm text-ink-muted">{{ $reward->label() }}</p>
                    </div>
                    <span class="text-sm font-semibold {{ $membership->points_balance >= $reward->points_cost ? 'text-mint-deep' : 'text-ink-muted' }}">
                        {{ $reward->points_cost }} pts
                        @if ($membership->points_balance >= $reward->points_cost) · Ready @endif
                    </span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="loop-panel p-6">
        <h2 class="font-display text-xl font-semibold">History</h2>
        <div class="mt-4 space-y-3">
            @foreach ($transactions as $tx)
                <div class="flex justify-between rounded-xl bg-chalk px-4 py-3">
                    <div>
                        <p class="font-medium">{{ $tx->description }}</p>
                        <p class="text-xs text-ink-muted">{{ $tx->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="font-semibold {{ $tx->points >= 0 ? 'text-mint-deep' : 'text-coral' }}">{{ $tx->points >= 0 ? '+' : '' }}{{ $tx->points }}</span>
                </div>
            @endforeach
        </div>
    </section>
</x-app-layout>
