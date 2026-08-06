<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">Rewards</h1>
        <p class="mt-1 text-ink-muted">Applied by staff at the till when the customer buys.</p>
    </x-slot>
    <div class="mb-6"><a href="{{ route('rewards.create') }}" class="loop-btn-mint">Add reward</a></div>
    <div class="grid gap-4 sm:grid-cols-2">
        @forelse ($rewards as $reward)
            <div class="loop-panel p-5">
                <p class="font-display text-lg font-semibold">{{ $reward->name }}</p>
                <p class="text-sm text-ink-muted">{{ $reward->points_cost }} pts · {{ $reward->label() }}</p>
            </div>
        @empty
            <p class="text-ink-muted">No rewards yet.</p>
        @endforelse
    </div>
</x-app-layout>
