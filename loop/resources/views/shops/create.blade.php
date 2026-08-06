<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">Add a shop</h1>
        <p class="mt-1 text-ink-muted">Each shop gets a unique check-in code for customers.</p>
    </x-slot>

    <form method="POST" action="{{ route('shops.store') }}" class="loop-panel max-w-xl space-y-4 p-6">
        @csrf
        <div>
            <label class="loop-label" for="name">Shop name</label>
            <input id="name" name="name" value="{{ old('name') }}" class="loop-input" required>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <label class="loop-label" for="address">Address</label>
            <input id="address" name="address" value="{{ old('address') }}" class="loop-input">
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="loop-label" for="city">City</label>
                <input id="city" name="city" value="{{ old('city') }}" class="loop-input">
            </div>
            <div>
                <label class="loop-label" for="phone">Phone</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" class="loop-input">
            </div>
        </div>
        <button class="loop-btn">Save shop</button>
    </form>
</x-app-layout>
