@php
    $editSteps = [
        1 => __('loop.section_basics'),
        2 => __('loop.section_offer_reward'),
        3 => __('loop.save'),
    ];
    $defaultType = old('reward_type', $reward->reward_type);
    $valueSeed = $defaultType === 'fixed_off'
        ? number_format((float) old('reward_value', $reward->reward_value))
        : (string) old('reward_value', $reward->reward_value);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.offers') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.edit') }} · {{ $reward->name }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.edit_offer_blurb') }}</p>
            </div>
            <x-settings-back :href="route('campaigns.index').'#offers'" :label="__('loop.back')" />
        </div>
    </x-slot>

    <div
        class="mx-auto max-w-lg"
        x-data="loopWizard({
            step: {{ (int) request('_step', old('_step', 1)) }},
            total: 3,
            type: @js($defaultType),
            points: {{ (int) old('points_cost', $reward->points_cost) }},
            valueDisplay: @js($valueSeed),
            product: @js(old('product_name', $reward->product_name)),
            stock: @js(old('stock', $reward->stock)),
            maxPerMember: @js(old('max_redemptions_per_member', $reward->max_redemptions_per_member)),
            limitsCopy: {
                none: @js(__('loop.limits_summary_none')),
                stock: @js(__('loop.limits_summary_stock')),
                maxOnce: @js(__('loop.limits_summary_max_once')),
                maxMany: @js(__('loop.limits_summary_max_many')),
                bothOnce: @js(__('loop.limits_summary_both_once')),
                bothMany: @js(__('loop.limits_summary_both_many')),
            },
            formatValue() {
                if (this.type !== 'fixed_off') return;
                let raw = String(this.valueDisplay).replace(/[^\d]/g, '');
                this.valueDisplay = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
            },
            valueNumber() { return parseFloat(String(this.valueDisplay).replace(/,/g, '')) || 0; },
            limitsSummary() {
                const stockRaw = String(this.stock ?? '').trim();
                const maxRaw = String(this.maxPerMember ?? '').trim();
                const stock = parseInt(stockRaw, 10);
                const max = parseInt(maxRaw, 10);
                const hasStock = stockRaw !== '' && Number.isFinite(stock) && stock > 0;
                const hasMax = maxRaw !== '' && Number.isFinite(max) && max > 0;
                const n = (v) => Number(v).toLocaleString();
                if (! hasStock && ! hasMax) return this.limitsCopy.none;
                if (hasStock && hasMax) {
                    return (max === 1 ? this.limitsCopy.bothOnce : this.limitsCopy.bothMany)
                        .replaceAll(':stock', n(stock))
                        .replaceAll(':max', n(max));
                }
                if (hasStock) return this.limitsCopy.stock.replaceAll(':stock', n(stock));
                return (max === 1 ? this.limitsCopy.maxOnce : this.limitsCopy.maxMany)
                    .replaceAll(':max', n(max));
            },
        })"
    >
        <x-form-stepper :steps="$editSteps" />

        <form
            x-ref="form"
            method="POST"
            action="{{ route('rewards.update', $reward) }}"
            class="space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8"
            @submit="return startSave()"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="_step" :value="step">
            <input type="hidden" name="reward_type" value="{{ $defaultType }}">
            <input type="hidden" name="reward_value" :value="type === 'free_item' || type === 'custom' ? 0 : valueNumber()">

            <div data-step="1" x-show="step === 1" class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">1 · {{ __('loop.section_basics') }}</p>
                <div>
                    <label class="loop-label">{{ __('loop.offer_name') }}</label>
                    <input name="name" class="loop-input" value="{{ old('name', $reward->name) }}" :required="step === 1">
                </div>
                <p class="rounded-xl bg-chalk/60 px-3 py-2 text-sm text-ink-muted">
                    {{ __('loop.type') }}:
                    <span class="font-semibold text-ink">{{ __('loop.'.$defaultType) }}</span>
                </p>
                <div>
                    <label class="loop-label">{{ __('loop.description') }}</label>
                    <textarea name="description" class="loop-input" rows="2">{{ old('description', $reward->description) }}</textarea>
                </div>
                <button type="button" class="loop-btn-mint w-full" @click.prevent="next()">{{ __('loop.continue') }}</button>
            </div>

            <div data-step="2" x-show="step === 2" x-cloak class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">2 · {{ __('loop.section_offer_reward') }}</p>
                @if ($defaultType === 'percent_off')
                    <label class="loop-label">{{ __('loop.percent_off_value') }}</label>
                    <input type="number" min="1" max="100" class="loop-input" x-model="valueDisplay" :required="step === 2">
                @elseif ($defaultType === 'fixed_off')
                    <label class="loop-label">{{ __('loop.fixed_off_value') }}</label>
                    <input type="text" inputmode="numeric" class="loop-input" x-model="valueDisplay" @input="formatValue()" :required="step === 2">
                @else
                    <label class="loop-label">{{ __('loop.tie_to_product_optional') }}</label>
                    <input name="product_name" class="loop-input" x-model="product" placeholder="{{ __('loop.tie_to_product_placeholder') }}">
                @endif
                <div class="rounded-2xl border border-ink/8 bg-chalk/40 p-4">
                    <label class="loop-label">{{ __('loop.points_to_unlock') }}</label>
                    <input type="number" name="points_cost" x-model.number="points" class="loop-input" min="1" :required="step === 2">
                </div>
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click.prevent="go(1)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click.prevent="next()">{{ __('loop.continue') }}</button>
                </div>
            </div>

            <div data-step="3" x-show="step === 3" x-cloak class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">3 · {{ __('loop.save') }}</p>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.section_limits') }}</h2>
                <p class="text-sm text-ink-muted">{{ __('loop.limits_optional_hint') }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="loop-label">{{ __('loop.stock_optional') }}</label>
                        <input type="number" name="stock" class="loop-input" x-model="stock" min="1" placeholder="∞">
                        <p class="mt-1 text-xs text-ink-muted">{{ __('loop.stock_help') }}</p>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.max_per_member') }}</label>
                        <input type="number" name="max_redemptions_per_member" class="loop-input" x-model="maxPerMember" min="1" placeholder="∞">
                        <p class="mt-1 text-xs text-ink-muted">{{ __('loop.max_per_member_help') }}</p>
                    </div>
                </div>
                <p class="rounded-2xl border border-mint/25 bg-mint-soft/40 px-4 py-3 text-center font-display text-base font-semibold text-ink" x-text="limitsSummary()"></p>
                <label class="flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $reward->is_active)) class="rounded border-ink/20 text-mint-deep focus:ring-mint-deep">
                    {{ __('loop.live') }}
                </label>
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click.prevent="go(2)">{{ __('loop.back') }}</button>
                    <button class="loop-btn-mint flex-1" :disabled="saving" :class="{ 'opacity-70': saving }">
                        <span x-show="!saving">{{ __('loop.save') }}</span>
                        <span x-show="saving" x-cloak>{{ __('loop.saving') }}</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
