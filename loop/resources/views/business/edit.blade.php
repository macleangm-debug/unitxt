<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">Business settings</h1>
        <p class="mt-1 text-ink-muted">Update how {{ $business->name }} appears on Loop.</p>
    </x-slot>

    <form method="POST" action="{{ route('business.update') }}" class="loop-panel max-w-xl space-y-4 p-6">
        @csrf
        @method('PATCH')
        <div>
            <label class="loop-label" for="name">Business name</label>
            <input id="name" name="name" value="{{ old('name', $business->name) }}" class="loop-input" required>
        </div>
        <div>
            <label class="loop-label" for="category">Category</label>
            <input id="category" name="category" value="{{ old('category', $business->category) }}" class="loop-input">
        </div>
        <div>
            <label class="loop-label" for="description">Description</label>
            <textarea id="description" name="description" rows="4" class="loop-input">{{ old('description', $business->description) }}</textarea>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $business->is_active))>
            Business is active
        </label>
        <button class="loop-btn">Save changes</button>
    </form>
</x-app-layout>
