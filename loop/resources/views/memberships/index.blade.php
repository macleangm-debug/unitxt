<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.my_wallets') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.my_wallets_blurb') }}</p>
    </x-slot>
    @forelse ($grouped as $sector => $items)
        <section class="mb-8">
            <h2 class="font-display text-lg font-semibold">{{ \App\Support\Sectors::label($sector) }}</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach ($items as $membership)
                    <a href="{{ route('memberships.show', $membership->business) }}" class="loop-panel block p-5 transition hover:-translate-y-0.5 hover:bg-white">
                        <p class="font-semibold">{{ $membership->business->name }}</p>
                        <p class="mt-2 font-display text-3xl">{{ $membership->points_balance }} <span class="text-base text-ink-muted">pts</span></p>
                        @php
                            $ready = $membership->availableRewards()->count();
                        @endphp
                        @if ($ready > 0)
                            <p class="mt-2 text-xs font-semibold text-mint-deep">{{ __('loop.offers_ready_count', ['count' => $ready]) }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <p class="text-ink-muted">{{ __('loop.no_wallets_yet') }}</p>
    @endforelse
</x-app-layout>
