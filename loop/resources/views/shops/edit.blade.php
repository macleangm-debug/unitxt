<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">Edit shop</h1>
        <p class="mt-1 text-ink-muted">Check-in code: <span class="font-semibold text-ink">{{ $shop->code }}</span></p>
    </x-slot>

    <form method="POST" action="{{ route('shops.update', $shop) }}" class="loop-panel max-w-xl space-y-4 p-6">
        @csrf
        @method('PUT')
        <div>
            <label class="loop-label" for="name">Shop name</label>
            <input id="name" name="name" value="{{ old('name', $shop->name) }}" class="loop-input" required>
        </div>
        <div>
            <label class="loop-label" for="address">Address</label>
            <input id="address" name="address" value="{{ old('address', $shop->address) }}" class="loop-input">
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="loop-label" for="city">City</label>
                <input id="city" name="city" value="{{ old('city', $shop->city) }}" class="loop-input">
            </div>
            <div>
                <label class="loop-label" for="phone">Phone</label>
                <input id="phone" name="phone" value="{{ old('phone', $shop->phone) }}" class="loop-input">
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $shop->is_active))>
            Shop is active
        </label>
        <div class="flex gap-3">
            <button class="loop-btn">Update shop</button>
        </div>
    </form>

    <form method="POST" action="{{ route('shops.destroy', $shop) }}" class="mt-4 max-w-xl" onsubmit="return confirm('Delete this shop?')">
        @csrf
        @method('DELETE')
        <button class="text-sm font-semibold text-coral hover:underline">Delete shop</button>
    </form>
</x-app-layout>
