@php
    $selectedShops = old('shop_ids', isset($campaign) ? $campaign->shops->pluck('id')->all() : []);
    $t = $template ?? null;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ isset($campaign) ? 'Edit campaign' : 'New campaign' }}</h1>
        <p class="mt-1 text-ink-muted">Map spend to points, or set birthday / welcome bonuses.</p>
    </x-slot>

    <form method="POST" action="{{ isset($campaign) ? route('campaigns.update', $campaign) : route('campaigns.store') }}" class="loop-panel max-w-2xl space-y-4 p-6">
        @csrf
        @isset($campaign) @method('PUT') @endisset
        @if ($templateKey ?? false)
            <input type="hidden" name="template_key" value="{{ $templateKey }}">
        @endif

        <div>
            <label class="loop-label">Name</label>
            <input name="name" class="loop-input" value="{{ old('name', $campaign->name ?? $t['name'] ?? '') }}" required>
        </div>
        <div>
            <label class="loop-label">Type</label>
            <select name="type" class="loop-input">
                @foreach (['earn' => 'Earn on spend', 'birthday' => 'Birthday', 'welcome' => 'Welcome'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', $campaign->type ?? $t['type'] ?? 'earn') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="loop-label">Description</label>
            <textarea name="description" rows="2" class="loop-input">{{ old('description', $campaign->description ?? $t['description'] ?? '') }}</textarea>
        </div>
        <div class="grid gap-3 sm:grid-cols-3">
            <div>
                <label class="loop-label">Spend step ({{ $business->currency }})</label>
                <input type="number" name="spend_step" class="loop-input" value="{{ old('spend_step', $campaign->spend_step ?? $t['spend_step'] ?? 1000) }}">
            </div>
            <div>
                <label class="loop-label">Points per step</label>
                <input type="number" name="points_per_step" class="loop-input" value="{{ old('points_per_step', $campaign->points_per_step ?? $t['points_per_step'] ?? 2) }}">
            </div>
            <div>
                <label class="loop-label">Bonus points</label>
                <input type="number" name="bonus_points" class="loop-input" value="{{ old('bonus_points', $campaign->bonus_points ?? $t['bonus_points'] ?? 0) }}">
            </div>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="loop-label">Starts</label>
                <input type="date" name="starts_at" class="loop-input" value="{{ old('starts_at', isset($campaign) ? $campaign->starts_at->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
            </div>
            <div>
                <label class="loop-label">Ends</label>
                <input type="date" name="ends_at" class="loop-input" value="{{ old('ends_at', isset($campaign) && $campaign->ends_at ? $campaign->ends_at->format('Y-m-d') : '') }}">
            </div>
        </div>
        <div>
            <p class="loop-label">Shops (empty = all)</p>
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                @foreach ($shops as $shop)
                    <label class="flex items-center gap-2 rounded-xl bg-chalk px-3 py-2 text-sm">
                        <input type="checkbox" name="shop_ids[]" value="{{ $shop->id }}" @checked(in_array($shop->id, $selectedShops, true))>
                        {{ $shop->name }}
                    </label>
                @endforeach
            </div>
        </div>

        @isset($campaign)
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $campaign->is_active))> Active</label>
        @endisset

        @if (!isset($campaign) && !empty($t['reward']))
            <div class="rounded-xl border border-mint/30 bg-mint-soft/50 p-4">
                <label class="flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" name="create_reward" value="1" checked>
                    Also create reward: {{ $t['reward']['name'] }}
                </label>
                <input type="hidden" name="reward_name" value="{{ $t['reward']['name'] }}">
                <input type="hidden" name="reward_points_cost" value="{{ $t['reward']['points_cost'] }}">
                <input type="hidden" name="reward_type" value="{{ $t['reward']['reward_type'] }}">
                <input type="hidden" name="reward_value" value="{{ $t['reward']['reward_value'] }}">
            </div>
        @endif

        <button class="loop-btn">{{ isset($campaign) ? 'Save' : 'Launch campaign' }}</button>
    </form>
</x-app-layout>
