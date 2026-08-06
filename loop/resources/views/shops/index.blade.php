<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold">Shops</h1>
                <p class="mt-1 text-ink-muted">Locations customers find by city — add a logo for a premium presence.</p>
            </div>
            <a href="{{ route('shops.create') }}" class="loop-btn-mint">Add shop</a>
        </div>
    </x-slot>

    @if ($errors->has('plan'))
        <div class="mb-4 rounded-xl border border-coral/30 bg-coral/10 px-4 py-3 text-sm text-ink">{{ $errors->first('plan') }}</div>
    @endif

    <div class="grid gap-4">
        @forelse ($shops as $shop)
            <div class="loop-panel flex flex-wrap items-center justify-between gap-4 p-5">
                <div class="flex items-center gap-4">
                    <x-shop-logo :shop="$shop" class="h-14 w-14 rounded-2xl" />
                    <div>
                        <p class="font-display text-lg font-semibold">{{ $shop->name }}</p>
                        <p class="text-sm text-ink-muted">{{ $shop->city ?: 'No city' }} · Code <span class="font-semibold text-ink">{{ $shop->code }}</span></p>
                        @if ($shop->address)
                            <p class="mt-1 text-sm text-ink-muted">{{ $shop->address }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $shop->is_active ? 'bg-mint-soft text-ink' : 'bg-chalk text-ink-muted' }}">
                        {{ $shop->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <a href="{{ route('shops.edit', $shop) }}" class="loop-btn-ghost !px-3 !py-2">Edit</a>
                </div>
            </div>
        @empty
            <div class="loop-panel p-8 text-center">
                <p class="text-ink-muted">No shops yet. Add your first location to start collecting visits.</p>
                <a href="{{ route('shops.create') }}" class="loop-btn mt-4">Add shop</a>
            </div>
        @endforelse
    </div>
</x-app-layout>
