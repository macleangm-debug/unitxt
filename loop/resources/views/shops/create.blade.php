<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">Add a shop</h1>
        <p class="mt-1 text-ink-muted">Country: {{ $business->country }} · city helps customers find you.</p>
    </x-slot>

    <form method="POST" action="{{ route('shops.store') }}" enctype="multipart/form-data" class="loop-panel max-w-xl space-y-4 p-6">
        @csrf
        <div>
            <label class="loop-label">Shop name</label>
            <input id="name" name="name" value="{{ old('name') }}" class="loop-input" required>
        </div>
        <div>
            <label class="loop-label">City</label>
            <input name="city" list="cities" value="{{ old('city', $business->city) }}" class="loop-input" required>
            <datalist id="cities">
                @foreach ($cities as $city)
                    <option value="{{ $city }}"></option>
                @endforeach
            </datalist>
        </div>
        <div>
            <label class="loop-label">Address</label>
            <input name="address" value="{{ old('address') }}" class="loop-input" required>
            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.shop_address_required_help') }}</p>
        </div>
        <div>
            <label class="loop-label">Phone</label>
            <input name="phone" value="{{ old('phone') }}" class="loop-input">
        </div>
        <div>
            <label class="loop-label">Logo</label>
            <input type="file" name="logo" accept="image/*" class="loop-input">
        </div>
        <button class="loop-btn">Save shop</button>
    </form>
</x-app-layout>
