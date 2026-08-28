@php
    $t = $selectedTemplate;
    $typeMeta = $selectedType;
    $queryType = old('reward_type', $typeMeta['reward_type'] ?? $t['reward_type'] ?? '');
    $defaultName = old('name', '');
    $defaultPoints = old('points_cost');
    $defaultValue = old('reward_value');
    $defaultProduct = old('product_name', '');
    $defaultDesc = old('description', '');
    $spendPerPoint = $earnCampaign && $earnCampaign->points_per_step
        ? ($earnCampaign->spend_step / $earnCampaign->points_per_step)
        : 0;
    $biz = $business->name;
    $valueSeed = '';
    if ($defaultValue !== null && $defaultValue !== '') {
        $valueSeed = ($queryType === 'fixed_off')
            ? number_format((float) $defaultValue)
            : (string) $defaultValue;
    }
    $starterLabel = $typeMeta['name'] ?? ($queryType ? __('loop.'.$queryType) : '');
    $namePlaceholder = $typeMeta['default_name'] ?? $typeMeta['name'] ?? $t['name'] ?? __('loop.offer_type_percent_name', ['value' => 5]);
    $descPlaceholder = $typeMeta['description'] ?? $t['description'] ?? __('loop.offer_desc_placeholder');
    $pointsPlaceholder = (string) ($typeMeta['points_cost'] ?? $t['points_cost'] ?? 100);
    $valuePlaceholder = $queryType === 'fixed_off'
        ? number_format((float) ($typeMeta['reward_value'] ?? 2000))
        : (string) ($typeMeta['reward_value'] ?? 5);
    $pointsInit = ($defaultPoints !== null && $defaultPoints !== '') ? (int) $defaultPoints : 'null';
    $offerSteps = [
        1 => __('loop.section_offer_type'),
        2 => __('loop.section_basics'),
        3 => __('loop.section_offer_reward'),
        4 => __('loop.section_offer_cost'),
        5 => __('loop.section_limits'),
    ];
    $errorStep = 2;
    if ($errors->hasAny(['reward_value', 'product_name'])) {
        $errorStep = 3;
    } elseif ($errors->has('points_cost')) {
        $errorStep = 4;
    } elseif ($errors->hasAny(['stock', 'max_redemptions_per_member'])) {
        $errorStep = 5;
    }
    $startStep = $queryType || $errors->any() ? 2 : 1;
    $initialStep = $errors->any() ? $errorStep : (int) old('_step', $startStep);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.offers') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.add_offer') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.add_offer_owner_hint') }}</p>
        </div>
    </x-slot>

    <div
        class="mx-auto max-w-2xl"
        x-data="offerWizard({
            step: {{ (int) $initialStep }},
            total: 5,
            hasPick: true,
            persistKey: 'loop.offerWizard.create',
            type: @js($queryType),
            typeLabel: @js($starterLabel),
            name: @js($defaultName),
            namePlaceholder: @js($namePlaceholder),
            descPlaceholder: @js($descPlaceholder),
            points: {{ $pointsInit }},
            pointsPlaceholder: @js($pointsPlaceholder),
            valueDisplay: @js($valueSeed),
            valuePlaceholder: @js($valuePlaceholder),
            product: @js($defaultProduct),
            productPlaceholder: @js(__('loop.tie_to_product_placeholder')),
            productRequired: @js(__('loop.product_required_free_item')),
            spendPerPoint: {{ (float) $spendPerPoint }},
            currency: @js($business->currency),
            businessName: @js($biz),
            pickRequired: @js(__('loop.pick_required')),
            valueRequired: @js(__('loop.offer_value_required')),
            pointsRequired: @js(__('loop.offer_points_required')),
        })"
        x-effect="persist()"
    >
        <x-form-stepper :steps="$offerSteps" />

        <form
            x-ref="form"
            method="POST"
            action="{{ route('rewards.store') }}"
            class="space-y-6 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8"
            @submit="submitForm($event)"
        >
            @csrf
            <input type="hidden" name="_step" :value="step">
            <input type="hidden" name="reward_type" :value="type">
            <input type="hidden" name="reward_value" :value="type === 'free_item' || type === 'custom' ? 0 : valueNumber()">

            @if ($errors->any())
                <div class="rounded-xl border border-coral/30 bg-coral/10 px-4 py-3 text-sm text-ink" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- 1 · Type --}}
            <div data-step="1" x-show="step === 1" class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">1 · {{ __('loop.section_offer_type') }}</p>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.offer_give_title') }}</h2>
                <p class="text-sm text-ink-muted">{{ __('loop.offer_give_body') }}</p>
                <button type="button" x-ref="pickAnchor" class="sr-only" tabindex="-1">{{ __('loop.pick_required') }}</button>

                @if ($earnCampaign)
                    <p class="rounded-2xl border border-ink/10 bg-chalk/50 px-4 py-3 text-sm font-medium text-ink">
                        {{ __('loop.customer_gets') }}:
                        {{ number_format((int) $earnCampaign->points_per_step) }} {{ __('loop.pts') }} /
                        {{ $business->currency }} {{ number_format($earnCampaign->spend_step) }}
                    </p>
                @endif

                <div class="space-y-3">
                    @foreach ($typeStarters as $starter)
                        <button
                            type="button"
                            @click="selectType(@js($starter))"
                            class="flex w-full flex-col rounded-3xl border bg-white p-5 text-left transition"
                            :class="type === @js($starter['reward_type']) ? 'border-mint ring-2 ring-mint/20' : 'border-ink/10 hover:border-mint'"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <p class="font-display text-lg font-semibold">{{ $starter['name'] }}</p>
                                <span class="shrink-0 rounded-lg bg-mint-soft px-2.5 py-1 text-xs font-semibold text-ink">{{ number_format((int) $starter['points_cost']) }} {{ __('loop.pts') }}</span>
                            </div>
                            <p class="mt-2 text-sm text-ink-muted">{{ $starter['description'] }}</p>
                            <div x-show="type === @js($starter['reward_type'])" x-cloak x-transition class="mt-3 border-t border-ink/5 pt-3">
                                <p class="text-sm font-semibold text-ink">{{ $starter['default_name'] }}</p>
                                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.offer_type_selected_hint') }}</p>
                            </div>
                        </button>
                    @endforeach
                </div>

                <button type="button" class="loop-btn-mint w-full" @click="next()">{{ __('loop.continue') }}</button>
            </div>

            {{-- 2 · Basics --}}
            <div data-step="2" x-show="step === 2" x-cloak class="space-y-4">
                <div x-show="typeLabel" class="rounded-2xl border border-ink/10 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.start_from_template') }}</p>
                    <p class="mt-1 font-display text-xl font-semibold" x-text="typeLabel"></p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.edit_template_step_hint') }}</p>
                </div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">2 · {{ __('loop.section_basics') }}</p>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.name_your_offer') }}</h2>
                <p class="text-sm text-ink-muted">{{ __('loop.offer_name_hint', ['business' => $biz]) }}</p>
                <div>
                    <label class="loop-label">{{ __('loop.offer_name') }}</label>
                    <input name="name" class="loop-input" x-model="name" placeholder="{{ $namePlaceholder }}" :placeholder="namePlaceholder" :required="step === 2" autocomplete="off">
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="rounded-full border border-ink/10 px-3 py-1.5 text-xs font-semibold" @click="applyIdea('5% off')">{{ $biz }} 5% off</button>
                    <button type="button" class="rounded-full border border-ink/10 px-3 py-1.5 text-xs font-semibold" @click="applyIdea('10% off')">{{ $biz }} 10% off</button>
                    <button type="button" class="rounded-full border border-ink/10 px-3 py-1.5 text-xs font-semibold" @click="applyIdea(@js(__('loop.free_item')))">{{ $biz }} {{ __('loop.free_item') }}</button>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.description') }}</label>
                    <textarea name="description" class="loop-input" rows="2" placeholder="{{ $descPlaceholder }}" :placeholder="descPlaceholder">{{ $defaultDesc }}</textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(1)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                </div>
            </div>

            {{-- 3 · Reward --}}
            <div data-step="3" x-show="step === 3" x-cloak class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">3 · {{ __('loop.section_offer_reward') }}</p>

                <div x-show="type === 'percent_off'" x-cloak>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.percent_off_value') }}</h2>
                    <p class="text-sm text-ink-muted">{{ __('loop.percent_off_form_help') }}</p>
                    <input type="number" min="1" max="100" class="loop-input" x-model="valueDisplay" data-value-input :placeholder="valuePlaceholder" :required="step === 3 && type === 'percent_off'">
                    <p class="mt-2 text-sm font-semibold text-ink">
                        <span x-text="valueDisplay || valuePlaceholder || 0"></span>% {{ __('loop.off_every_eligible_sale') }}
                    </p>
                </div>

                <div x-show="type === 'fixed_off'" x-cloak>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.fixed_off_value') }}</h2>
                    <p class="text-sm text-ink-muted">{{ __('loop.fixed_off_form_help') }}</p>
                    <input type="text" inputmode="numeric" class="loop-input" x-model="valueDisplay" data-value-input @input="formatValue()" :placeholder="valuePlaceholder" :required="step === 3 && type === 'fixed_off'">
                    <p class="mt-2 text-sm font-semibold text-ink">
                        {{ $business->currency }} <span x-text="valueDisplay || valuePlaceholder || 0"></span> {{ __('loop.off_every_eligible_sale') }}
                    </p>
                </div>

                <div x-show="type === 'free_item' || type === 'custom'" x-cloak>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.tie_to_product') }}</h2>
                    <p class="text-sm text-ink-muted">{{ __('loop.tie_to_product_required_hint') }}</p>
                    <input name="product_name" class="loop-input" x-model="product" :placeholder="productPlaceholder" :required="step === 3 && type === 'free_item'">
                </div>

                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(2)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                </div>
            </div>

            {{-- 4 · Points --}}
            <div data-step="4" x-show="step === 4" x-cloak class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">4 · {{ __('loop.section_offer_cost') }}</p>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.points_to_unlock') }}</h2>
                <p class="text-sm text-ink-muted">{{ __('loop.points_to_unlock_help') }}</p>
                <input type="number" name="points_cost" x-model.number="points" class="loop-input" :placeholder="pointsPlaceholder" :required="step === 4" min="1">
                <p class="text-center font-display text-xl font-bold" x-show="unlockSpend() > 0">
                    {{ __('loop.customer_spend_to_unlock_prefix') }}
                    <span x-text="currency + ' ' + unlockSpend().toLocaleString()"></span>
                    {{ __('loop.customer_spend_to_unlock_suffix') }}
                </p>
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(3)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" x-show="type !== 'free_item'" @click="next()">{{ __('loop.continue') }}</button>
                    <button type="submit" class="loop-btn-mint flex-1" x-show="type === 'free_item'" x-cloak :disabled="saving" :class="{ 'opacity-70': saving }">
                        <span x-show="!saving">{{ __('loop.confirm_launch_offer') }}</span>
                        <span x-show="saving" x-cloak>{{ __('loop.saving') }}</span>
                    </button>
                </div>
            </div>

            {{-- 5 · Limits (not used for free-item offers) --}}
            <div data-step="5" x-show="step === 5 && type !== 'free_item'" x-cloak class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">5 · {{ __('loop.section_limits') }}</p>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.section_limits') }}</h2>
                <p class="text-sm text-ink-muted">{{ __('loop.limits_optional_hint') }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="loop-label">{{ __('loop.stock_optional') }}</label>
                        <input type="number" name="stock" class="loop-input" value="{{ old('stock') }}" placeholder="∞">
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.max_per_member') }}</label>
                        <input type="number" name="max_redemptions_per_member" class="loop-input" min="1" value="{{ old('max_redemptions_per_member') }}">
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(4)">{{ __('loop.back') }}</button>
                    <button type="submit" class="loop-btn-mint flex-1" :disabled="saving" :class="{ 'opacity-70': saving }">
                        <span x-show="!saving">{{ __('loop.confirm_launch_offer') }}</span>
                        <span x-show="saving" x-cloak>{{ __('loop.saving') }}</span>
                    </button>
                </div>
                <button type="submit" class="w-full text-sm font-semibold text-ink-muted underline">{{ __('loop.skip_for_now') }} — {{ __('loop.confirm_launch_offer') }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
