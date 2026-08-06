<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">My wallets</h1>
        <p class="mt-1 text-ink-muted">Points you’ve earned across Loop businesses.</p>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2">
        @forelse ($memberships as $membership)
            <a href="{{ route('memberships.show', $membership->business) }}" class="loop-panel block p-5 transition hover:bg-white">
                <p class="font-display text-lg font-semibold">{{ $membership->business->name }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ $membership->member_code }}</p>
                <p class="mt-4 font-display text-3xl font-semibold">{{ $membership->points_balance }} <span class="text-base font-medium text-ink-muted">pts</span></p>
            </a>
        @empty
            <div class="loop-panel p-8 text-ink-muted">You haven’t joined any businesses yet.</div>
        @endforelse
    </div>
</x-app-layout>
