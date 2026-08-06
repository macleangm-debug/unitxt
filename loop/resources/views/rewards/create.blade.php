<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">Add reward</h1>
        <p class="mt-1 text-ink-muted">Customers redeem points for this offer.</p>
    </x-slot>

    <form method="POST" action="{{ route('rewards.store') }}" class="loop-panel max-w-xl space-y-4 p-6">
        @csrf
        <div>
            <label class="loop-label" for="name">Reward name</label>
            <input id="name" name="name" value="{{ old('name') }}" class="loop-input" required>
        </div>
        <div>
            <label class="loop-label" for="description">Description</label>
            <textarea id="description" name="description" rows="3" class="loop-input">{{ old('description') }}</textarea>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="loop-label" for="points_cost">Points cost</label>
                <input id="points_cost" type="number" min="1" name="points_cost" value="{{ old('points_cost', 50) }}" class="loop-input" required>
            </div>
            <div>
                <label class="loop-label" for="stock">Stock (optional)</label>
                <input id="stock" type="number" min="0" name="stock" value="{{ old('stock') }}" class="loop-input">
            </div>
        </div>
        <button class="loop-btn">Create reward</button>
    </form>
</x-app-layout>
