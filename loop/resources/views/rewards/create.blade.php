<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-3xl font-semibold">{{ __('loop.add_offer') }}</h1></x-slot>
    <form method="POST" action="{{ route('rewards.store') }}" class="loop-panel max-w-xl space-y-4 p-6">
        @csrf
        <div>
            <label class="loop-label">{{ __('loop.offer_name') }}</label>
            <input name="name" class="loop-input" required>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.description') }}</label>
            <textarea name="description" class="loop-input" rows="2"></textarea>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="loop-label">{{ __('loop.product') }}</label>
                <input name="product_name" class="loop-input" placeholder="{{ __('loop.product_placeholder') }}">
            </div>
            <div>
                <label class="loop-label">{{ __('loop.sku') }}</label>
                <input name="product_sku" class="loop-input" placeholder="SKU-100">
            </div>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="loop-label">{{ __('loop.points_cost') }}</label>
                <input type="number" name="points_cost" value="100" class="loop-input" required>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.type') }}</label>
                <select name="reward_type" class="loop-input">
                    <option value="percent_off">{{ __('loop.percent_off') }}</option>
                    <option value="fixed_off">{{ __('loop.fixed_off') }}</option>
                    <option value="free_item">{{ __('loop.free_item') }}</option>
                    <option value="custom">{{ __('loop.custom') }}</option>
                </select>
            </div>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.value_hint') }}</label>
            <input type="number" step="0.01" name="reward_value" value="5" class="loop-input">
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="loop-label">{{ __('loop.stock_optional') }}</label>
                <input type="number" name="stock" class="loop-input">
            </div>
            <div>
                <label class="loop-label">{{ __('loop.max_per_member') }}</label>
                <input type="number" name="max_redemptions_per_member" class="loop-input" min="1">
            </div>
        </div>
        <button class="loop-btn">{{ __('loop.save_offer') }}</button>
    </form>
</x-app-layout>
