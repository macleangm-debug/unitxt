<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.offers') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.offers_blurb') }}</p>
    </x-slot>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-ink-muted">{{ __('loop.offers_vs_campaigns') }}</p>
        <a href="{{ route('rewards.create') }}" class="loop-btn-mint">{{ __('loop.add_offer') }}</a>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        @forelse ($rewards as $reward)
            <div class="loop-panel p-5">
                <p class="font-display text-lg font-semibold">{{ $reward->name }}</p>
                <p class="text-sm text-ink-muted">{{ number_format((int) $reward->points_cost) }} pts · {{ $reward->label() }}</p>
                @if ($reward->product_name)
                    <p class="mt-2 text-sm">{{ __('loop.product') }}: <span class="font-semibold">{{ $reward->product_name }}</span>
                        @if ($reward->product_sku) <span class="text-ink-muted">({{ $reward->product_sku }})</span> @endif
                    </p>
                @endif
                @if ($reward->stock !== null)
                    <p class="mt-1 text-xs text-ink-muted">{{ __('loop.stock') }}: {{ $reward->stock }}</p>
                @endif
            </div>
        @empty
            <p class="text-ink-muted">{{ __('loop.no_offers_yet') }}</p>
        @endforelse
    </div>
</x-app-layout>
