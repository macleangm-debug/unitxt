<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold">Rewards</h1>
                <p class="mt-1 text-ink-muted">Offer redemptions that bring members back into your shops.</p>
            </div>
            <a href="{{ route('rewards.create') }}" class="loop-btn-mint">Add reward</a>
        </div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2">
        @forelse ($rewards as $reward)
            <div class="loop-panel p-5">
                <p class="font-display text-lg font-semibold">{{ $reward->name }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ $reward->description }}</p>
                <p class="mt-4 text-sm"><span class="font-semibold">{{ $reward->points_cost }}</span> points
                    @if ($reward->stock !== null)
                        · {{ $reward->stock }} left
                    @endif
                </p>
            </div>
        @empty
            <div class="loop-panel p-8 text-ink-muted sm:col-span-2">No rewards yet.</div>
        @endforelse
    </div>
</x-app-layout>
