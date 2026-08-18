<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex items-start gap-3">
                <x-back-icon :href="route('settings')" />
                <div>
                    <h1 class="font-display text-3xl font-semibold">{{ __('loop.shops') }}</h1>
                    <p class="mt-1 text-ink-muted">{{ __('loop.shops_blurb') }}</p>
                </div>
            </div>
            <a href="{{ route('shops.create') }}" class="loop-btn-mint">{{ __('loop.add_shop') }}</a>
        </div>
    </x-slot>

    @if ($errors->has('plan'))
        <div class="mb-4 rounded-xl border border-coral/30 bg-coral/10 px-4 py-3 text-sm text-ink">{{ $errors->first('plan') }}</div>
    @endif

    <div class="grid gap-4">
        @forelse ($shops as $shop)
            <a href="{{ route('shops.show', $shop) }}" class="loop-panel flex flex-wrap items-center justify-between gap-4 p-5 transition hover:-translate-y-0.5 hover:bg-white">
                <div class="flex items-center gap-4">
                    <x-shop-logo :shop="$shop" class="h-14 w-14 rounded-2xl" />
                    <div>
                        <p class="font-display text-lg font-semibold">{{ $shop->name }}</p>
                        <p class="text-sm text-ink-muted">{{ $shop->city ?: __('loop.no_city') }} · {{ $shop->code }}</p>
                        @if ($shop->address)
                            <p class="mt-1 text-sm text-ink-muted">{{ $shop->address }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $shop->is_active ? 'bg-mint-soft text-ink' : 'bg-chalk text-ink-muted' }}">
                        {{ $shop->is_active ? __('loop.active') : __('loop.inactive') }}
                    </span>
                    <span class="text-ink-muted">→</span>
                </div>
            </a>
        @empty
            <div class="loop-panel p-8 text-center">
                <p class="font-display text-lg font-semibold">{{ __('loop.no_shops_yet') }}</p>
                <p class="mt-2 text-sm text-ink-muted">{{ __('loop.shops_blurb') }}</p>
                <a href="{{ route('shops.create') }}" class="loop-btn-mint mt-5 inline-flex">{{ __('loop.add_shop') }}</a>
            </div>
        @endforelse
    </div>
</x-app-layout>
