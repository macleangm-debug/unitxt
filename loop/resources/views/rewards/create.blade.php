<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-3xl font-semibold">Add reward</h1></x-slot>
    <form method="POST" action="{{ route('rewards.store') }}" class="loop-panel max-w-xl space-y-4 p-6">
        @csrf
        <div><label class="loop-label">Name</label><input name="name" class="loop-input" required></div>
        <div><label class="loop-label">Description</label><textarea name="description" class="loop-input" rows="2"></textarea></div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="loop-label">Points cost</label><input type="number" name="points_cost" value="100" class="loop-input" required></div>
            <div>
                <label class="loop-label">Type</label>
                <select name="reward_type" class="loop-input">
                    <option value="percent_off">Percent off</option>
                    <option value="fixed_off">Fixed amount off</option>
                    <option value="free_item">Free item</option>
                    <option value="custom">Custom</option>
                </select>
            </div>
        </div>
        <div><label class="loop-label">Value (e.g. 5 for 5%)</label><input type="number" step="0.01" name="reward_value" value="5" class="loop-input"></div>
        <div><label class="loop-label">Stock (optional)</label><input type="number" name="stock" class="loop-input"></div>
        <button class="loop-btn">Save reward</button>
    </form>
</x-app-layout>
