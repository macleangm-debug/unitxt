<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ $business->name }} rewards</h1>
        <p class="mt-1 text-ink-muted">
            @if ($membership)
                You have {{ number_format((int) $membership->points_balance) }} points available.
            @else
                Join this business to redeem rewards.
            @endif
        </p>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2">
        @forelse ($rewards as $reward)
            <div class="loop-panel p-5">
                <p class="font-display text-lg font-semibold">{{ $reward->name }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ $reward->description }}</p>
                <p class="mt-3 font-semibold">{{ number_format((int) $reward->points_cost) }} points</p>
                @if ($membership)
                    <form method="POST" action="{{ route('rewards.redeem', $reward) }}" class="mt-4">
                        @csrf
                        <button class="loop-btn-ghost" @disabled($membership->points_balance < $reward->points_cost || ! $reward->isAvailable())>
                            Redeem
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="loop-panel p-8 text-ink-muted sm:col-span-2">No rewards listed.</div>
        @endforelse
    </div>
</x-app-layout>
