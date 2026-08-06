<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ __('loop.sale') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('Look up a phone · record the sale · apply offers. Works for walk-ins and phone orders.') }}</p>
            </div>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('till.lookup') }}" class="loop-panel max-w-xl space-y-4 p-6 animate-fade-up">
        @csrf
        <div>
            <label class="loop-label">Shop</label>
            <select name="shop_id" class="loop-input" required>
                @foreach ($shops as $shop)
                    <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="loop-label">Channel</label>
            <div class="mt-2 grid grid-cols-2 gap-3">
                <label class="rounded-xl border border-ink/10 bg-chalk px-4 py-3 text-sm has-[:checked]:border-mint has-[:checked]:bg-mint-soft">
                    <input type="radio" name="channel" value="in_store" class="sr-only" checked> In store
                </label>
                <label class="rounded-xl border border-ink/10 bg-chalk px-4 py-3 text-sm has-[:checked]:border-mint has-[:checked]:bg-mint-soft">
                    <input type="radio" name="channel" value="phone_order" class="sr-only"> Phone order
                </label>
            </div>
        </div>
        <div class="grid gap-3 sm:grid-cols-[8rem_1fr]">
            <div>
                <label class="loop-label">Code</label>
                <select name="country_code" class="loop-input">
                    @foreach ($countries as $meta)
                        <option value="{{ $meta['dial'] }}" @selected($meta['dial'] === '+255')>{{ $meta['dial'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="loop-label">Customer phone</label>
                <input name="phone" class="loop-input text-lg" placeholder="712 345 678" required autofocus>
            </div>
        </div>
        <button class="loop-btn-mint w-full">Look up on Loop</button>
    </form>

    <section class="mt-8">
        <h2 class="font-display text-xl font-semibold">Recent till activity</h2>
        <div class="mt-4 space-y-3">
            @forelse ($recent as $visit)
                <div class="loop-panel flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                    <div>
                        <p class="font-semibold">{{ $visit->customer->name }} · {{ $visit->shop->name }}</p>
                        <p class="text-sm text-ink-muted">
                            {{ $business->currency }} {{ number_format($visit->amount_spent, 0) }}
                            · {{ $visit->channel === 'phone_order' ? 'Phone order' : 'In store' }}
                            · {{ $visit->recorder?->name }}
                            · {{ $visit->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <span class="rounded-lg bg-mint-soft px-2.5 py-1 text-sm font-semibold">+{{ $visit->points_earned }} pts</span>
                </div>
            @empty
                <p class="text-sm text-ink-muted">No sales recorded yet.</p>
            @endforelse
        </div>
    </section>
</x-app-layout>
