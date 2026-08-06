@php
    $selectedShops = old('shop_ids', isset($campaign) ? $campaign->shops->pluck('id')->all() : []);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ isset($campaign) ? 'Edit campaign' : 'New campaign' }}</h1>
        <p class="mt-1 text-ink-muted">Define how many points customers earn when they visit selected shops.</p>
    </x-slot>

    <form method="POST" action="{{ isset($campaign) ? route('campaigns.update', $campaign) : route('campaigns.store') }}" class="loop-panel max-w-2xl space-y-4 p-6">
        @csrf
        @if (isset($campaign))
            @method('PUT')
        @endif

        <div>
            <label class="loop-label" for="name">Campaign name</label>
            <input id="name" name="name" value="{{ old('name', $campaign->name ?? '') }}" class="loop-input" required>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <label class="loop-label" for="description">Description</label>
            <textarea id="description" name="description" rows="3" class="loop-input">{{ old('description', $campaign->description ?? '') }}</textarea>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="loop-label" for="points_per_visit">Points / visit</label>
                <input id="points_per_visit" type="number" min="1" name="points_per_visit" value="{{ old('points_per_visit', $campaign->points_per_visit ?? 10) }}" class="loop-input" required>
            </div>
            <div>
                <label class="loop-label" for="bonus_points">Bonus points</label>
                <input id="bonus_points" type="number" min="0" name="bonus_points" value="{{ old('bonus_points', $campaign->bonus_points ?? 0) }}" class="loop-input">
            </div>
            <div>
                <label class="loop-label" for="max_visits_per_day">Max visits / day</label>
                <input id="max_visits_per_day" type="number" min="1" name="max_visits_per_day" value="{{ old('max_visits_per_day', $campaign->max_visits_per_day ?? 1) }}" class="loop-input" required>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="loop-label" for="starts_at">Starts</label>
                <input id="starts_at" type="date" name="starts_at" value="{{ old('starts_at', isset($campaign) ? $campaign->starts_at->format('Y-m-d') : now()->format('Y-m-d')) }}" class="loop-input" required>
            </div>
            <div>
                <label class="loop-label" for="ends_at">Ends (optional)</label>
                <input id="ends_at" type="date" name="ends_at" value="{{ old('ends_at', isset($campaign) && $campaign->ends_at ? $campaign->ends_at->format('Y-m-d') : '') }}" class="loop-input">
            </div>
        </div>

        <div>
            <p class="loop-label">Participating shops</p>
            <p class="mt-1 text-xs text-ink-muted">Leave all unchecked to include every shop.</p>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                @forelse ($shops as $shop)
                    <label class="flex items-center gap-2 rounded-xl bg-chalk px-3 py-2 text-sm">
                        <input type="checkbox" name="shop_ids[]" value="{{ $shop->id }}" @checked(in_array($shop->id, $selectedShops, true))>
                        {{ $shop->name }}
                    </label>
                @empty
                    <p class="text-sm text-ink-muted">Add shops first so you can target specific locations.</p>
                @endforelse
            </div>
        </div>

        @isset($campaign)
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $campaign->is_active))>
                Campaign is active
            </label>
        @endisset

        <button class="loop-btn">{{ isset($campaign) ? 'Update campaign' : 'Launch campaign' }}</button>
    </form>

    @isset($campaign)
        <form method="POST" action="{{ route('campaigns.destroy', $campaign) }}" class="mt-4 max-w-2xl" onsubmit="return confirm('Delete this campaign?')">
            @csrf
            @method('DELETE')
            <button class="text-sm font-semibold text-coral hover:underline">Delete campaign</button>
        </form>
    @endisset
</x-app-layout>
