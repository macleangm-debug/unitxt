@php
    $selectedShops = old('shop_ids', $campaign->shops->pluck('id')->all());
    $selectedRewards = old('reward_ids', $campaign->rewards->pluck('id')->all());
@endphp
<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.edit') }} · {{ $campaign->displayName() }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.campaigns_blurb') }}</p>
    </x-slot>

    <form method="POST" action="{{ route('campaigns.update', $campaign) }}" class="mx-auto max-w-2xl space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8">
        @csrf
        @method('PUT')

        <div>
            <label class="loop-label">{{ __('loop.campaign_name') }}</label>
            <input name="name" class="loop-input" value="{{ old('name', $campaign->name) }}" required>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.type') }}</label>
            <select name="type" class="loop-input">
                @foreach ([
                    'earn' => __('loop.type_earn'),
                    'product_push' => __('loop.type_product_push'),
                    'streak' => __('loop.type_streak'),
                    'birthday' => __('loop.type_birthday'),
                    'welcome' => __('loop.type_welcome'),
                ] as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', $campaign->type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.description') }}</label>
            <textarea name="description" rows="2" class="loop-input">{{ old('description', $campaign->description) }}</textarea>
        </div>

        <div class="rounded-2xl bg-chalk/80 p-4">
            <p class="text-sm font-semibold">{{ __('loop.earn_rules') }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.spend_step_help') }}</p>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                <div>
                    <label class="loop-label">{{ __('loop.spend_step') }} ({{ $business->currency }})</label>
                    <input type="number" name="spend_step" class="loop-input" value="{{ old('spend_step', $campaign->spend_step) }}">
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.points_per_step') }}</label>
                    <input type="number" name="points_per_step" class="loop-input" value="{{ old('points_per_step', $campaign->points_per_step) }}">
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.bonus_points') }}</label>
                    <input type="number" name="bonus_points" class="loop-input" value="{{ old('bonus_points', $campaign->bonus_points) }}">
                    <p class="mt-1 text-[11px] text-ink-muted">{{ __('loop.bonus_points_help') }}</p>
                </div>
            </div>
        </div>

        <section class="space-y-3">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.section_tie_offers') }}</p>
            <p class="text-sm text-ink-muted">{{ __('loop.tie_offers_body') }}</p>
            <div class="grid gap-2">
                @foreach ($offers as $offer)
                    <label class="flex items-center gap-3 rounded-2xl border border-ink/10 bg-white px-4 py-3 has-[:checked]:border-mint has-[:checked]:bg-mint-soft/40">
                        <input type="checkbox" name="reward_ids[]" value="{{ $offer->id }}" @checked(in_array($offer->id, $selectedRewards, false))>
                        <span>
                            <span class="block text-sm font-semibold">{{ $offer->name }}</span>
                            <span class="text-xs text-ink-muted">{{ $offer->points_cost }} {{ __('loop.pts') }} · {{ $offer->label() }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('reward_ids')" class="mt-1" />
        </section>

        <div class="grid gap-3 sm:grid-cols-2">
            <x-date-field name="starts_at" :label="__('loop.starts')" :value="old('starts_at', $campaign->starts_at?->format('Y-m-d'))" required />
            <x-date-field name="ends_at" :label="__('loop.ends')" :value="old('ends_at', $campaign->ends_at?->format('Y-m-d'))" optional />
        </div>

        <div>
            <p class="loop-label">{{ __('loop.shops_optional') }}</p>
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                @foreach ($shops as $shop)
                    <label class="flex items-center gap-2 rounded-xl bg-chalk px-3 py-2 text-sm">
                        <input type="checkbox" name="shop_ids[]" value="{{ $shop->id }}" @checked(in_array($shop->id, $selectedShops, true))>
                        {{ $shop->name }}
                    </label>
                @endforeach
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $campaign->is_active))>
            {{ __('loop.live') }}
        </label>

        <button class="loop-btn-mint w-full">{{ __('loop.save') }}</button>
    </form>
</x-app-layout>
