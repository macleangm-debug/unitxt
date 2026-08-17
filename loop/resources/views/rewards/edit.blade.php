@php
    $editSteps = [
        1 => __('loop.section_basics'),
        2 => __('loop.section_offer_reward'),
        3 => __('loop.section_offer_cost'),
        4 => __('loop.section_limits'),
    ];
    $defaultType = old('reward_type', $reward->reward_type);
    $valueSeed = $defaultType === 'fixed_off'
        ? number_format((float) old('reward_value', $reward->reward_value))
        : (string) old('reward_value', $reward->reward_value);
    $errorStep = 1;
    if ($errors->hasAny(['reward_value', 'product_name'])) {
        $errorStep = 2;
    } elseif ($errors->has('points_cost')) {
        $errorStep = 3;
    } elseif ($errors->hasAny(['stock', 'max_redemptions_per_member'])) {
        $errorStep = 4;
    }
    $initialStep = $errors->any() ? $errorStep : (int) old('_step', 1);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.offers') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.edit') }} · {{ $reward->name }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.edit_offer_blurb') }}</p>
        </div>
    </x-slot>

    <div
        class="mx-auto max-w-lg"
        x-data="offerWizard({
            step: {{ (int) $initialStep }},
            total: 4,
            hasPick: false,
            type: @js($defaultType),
            typeLabel: @js(__('loop.'.$defaultType)),
            name: @js(old('name', $reward->name)),
            points: {{ (int) old('points_cost', $reward->points_cost) }},
            valueDisplay: @js($valueSeed),
            product: @js(old('product_name', $reward->product_name)),
            productPlaceholder: @js(__('loop.tie_to_product_placeholder')),
            valueRequired: @js(__('loop.offer_value_required')),
            pointsRequired: @js(__('loop.offer_points_required')),
        })"
    >
        <x-form-stepper :steps="$editSteps" />

        <form
            x-ref="form"
            method="POST"
            action="{{ route('rewards.update', $reward) }}"
            class="space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8"
            @submit="submitForm($event)"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="_step" :value="step">
            <input type="hidden" name="reward_type" :value="type">
            <input type="hidden" name="reward_value" :value="type === 'free_item' || type === 'custom' ? 0 : valueNumber()">

            @if ($errors->any())
                <div class="rounded-xl border border-coral/30 bg-coral/10 px-4 py-3 text-sm text-ink" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <div data-step="1" x-show="step === 1" class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">1 · {{ __('loop.section_basics') }}</p>
                <div>
                    <label class="loop-label">{{ __('loop.offer_name') }}</label>
                    <input name="name" class="loop-input" x-model="name" :required="step === 1">
                </div>
                <p class="rounded-xl bg-chalk/60 px-3 py-2 text-sm text-ink-muted">
                    {{ __('loop.type') }}:
                    <span class="font-semibold text-ink">{{ __('loop.'.$defaultType) }}</span>
                </p>
                <div>
                    <label class="loop-label">{{ __('loop.description') }}</label>
                    <textarea name="description" class="loop-input" rows="2" placeholder="{{ __('loop.offer_desc_placeholder') }}">{{ old('description', $reward->description) }}</textarea>
                </div>
                <button type="button" class="loop-btn-mint w-full" @click="next()">{{ __('loop.continue') }}</button>
            </div>

            <div data-step="2" x-show="step === 2" x-cloak class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">2 · {{ __('loop.section_offer_reward') }}</p>
                <div x-show="type === 'percent_off'" x-cloak>
                    <label class="loop-label">{{ __('loop.percent_off_value') }}</label>
                    <input type="number" min="1" max="100" class="loop-input" x-model="valueDisplay" data-value-input :required="step === 2 && type === 'percent_off'">
                </div>
                <div x-show="type === 'fixed_off'" x-cloak>
                    <label class="loop-label">{{ __('loop.fixed_off_value') }}</label>
                    <input type="text" inputmode="numeric" class="loop-input" x-model="valueDisplay" data-value-input @input="formatValue()" :required="step === 2 && type === 'fixed_off'">
                </div>
                <div x-show="type === 'free_item' || type === 'custom'" x-cloak>
                    <label class="loop-label">{{ __('loop.tie_to_product_optional') }}</label>
                    <input name="product_name" class="loop-input" x-model="product" :placeholder="productPlaceholder">
                </div>
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(1)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                </div>
            </div>

            <div data-step="3" x-show="step === 3" x-cloak class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">3 · {{ __('loop.section_offer_cost') }}</p>
                <label class="loop-label">{{ __('loop.points_to_unlock') }}</label>
                <input type="number" name="points_cost" x-model.number="points" class="loop-input" min="1" :required="step === 3">
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(2)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                </div>
            </div>

            <div data-step="4" x-show="step === 4" x-cloak class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">4 · {{ __('loop.section_limits') }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="loop-label">{{ __('loop.stock_optional') }}</label>
                        <input type="number" name="stock" class="loop-input" value="{{ old('stock', $reward->stock) }}" placeholder="∞">
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.max_per_member') }}</label>
                        <input type="number" name="max_redemptions_per_member" class="loop-input" min="1" value="{{ old('max_redemptions_per_member', $reward->max_redemptions_per_member) }}">
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $reward->is_active)) class="rounded border-ink/20 text-mint-deep focus:ring-mint-deep">
                    {{ __('loop.live') }}
                </label>
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(3)">{{ __('loop.back') }}</button>
                    <button type="submit" class="loop-btn-mint flex-1" :disabled="saving" :class="{ 'opacity-70': saving }">
                        <span x-show="!saving">{{ __('loop.save') }}</span>
                        <span x-show="saving" x-cloak>{{ __('loop.saving') }}</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
