<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">Edit shop</h1>
        <p class="mt-1 text-ink-muted">Code: <span class="font-semibold text-ink">{{ $shop->code }}</span></p>
    </x-slot>

    <form method="POST" action="{{ route('shops.update', $shop) }}" enctype="multipart/form-data" class="loop-panel max-w-xl space-y-4 p-6">
        @csrf
        @method('PUT')
        <div class="flex items-center gap-3">
            <x-shop-logo :shop="$shop" class="h-14 w-14 rounded-2xl" />
            <div class="text-sm text-ink-muted">Upload a new logo to replace the mark.</div>
        </div>
        <div>
            <label class="loop-label">Shop name</label>
            <input name="name" value="{{ old('name', $shop->name) }}" class="loop-input" required>
        </div>
        <div>
            <label class="loop-label">City</label>
            <input name="city" list="cities" value="{{ old('city', $shop->city) }}" class="loop-input" required>
            <datalist id="cities">
                @foreach ($cities as $city)
                    <option value="{{ $city }}"></option>
                @endforeach
            </datalist>
        </div>
        <div>
            <label class="loop-label">Address</label>
            <input name="address" value="{{ old('address', $shop->address) }}" class="loop-input">
        </div>
        <div>
            <label class="loop-label">Phone</label>
            <input name="phone" value="{{ old('phone', $shop->phone) }}" class="loop-input">
        </div>
        <div>
            <label class="loop-label">Logo</label>
            <input type="file" name="logo" accept="image/*" class="loop-input">
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $shop->is_active))>
            Shop is active
        </label>
        <button class="loop-btn">Update shop</button>
    </form>
</x-app-layout>
