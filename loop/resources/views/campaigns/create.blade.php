@php
    $selectedShops = old('shop_ids', []);
    $t = $template ?? null;
    $allowedTypes = ['earn', 'product_push', 'birthday', 'welcome', 'streak'];
    $defaultType = old('type', $t['type'] ?? 'earn');
    if (! in_array($defaultType, $allowedTypes, true)) {
        $defaultType = 'earn';
    }
    $preselectedKey = old('template_key', $templateKey);
    $fromTemplate = (bool) ($t && $preselectedKey);
    $createSteps = [
        1 => __('loop.section_pick'),
        2 => __('loop.section_basics'),
        3 => __('loop.customer_gets'),
        4 => __('loop.section_schedule'),
    ];
    $oldSpend = old('spend_step');
    $spendDisplayInit = ($oldSpend !== null && $oldSpend !== '' && (int) $oldSpend > 0)
        ? number_format((int) $oldSpend)
        : '';
    $oldPoints = old('points_per_step');
    $pointsInit = ($oldPoints !== null && $oldPoints !== '') ? (int) $oldPoints : 'null';
    $oldBonus = old('bonus_points', $t['bonus_points'] ?? null);
    $bonusInit = ($oldBonus !== null && $oldBonus !== '') ? (int) $oldBonus : 'null';
    $oldStreakTarget = old('streak_target', $t['streak_target'] ?? 3);
    $streakTargetInit = ($oldStreakTarget !== null && $oldStreakTarget !== '') ? (int) $oldStreakTarget : 3;
    $errorStep = 2;
    if ($errors->hasAny(['spend_step', 'points_per_step', 'featured_product_name', 'bonus_points', 'streak_target', 'streak_period'])) {
        $errorStep = 3;
    } elseif ($errors->hasAny(['starts_at', 'ends_at', 'shop_ids'])) {
        $errorStep = 4;
    }
    $startStep = ($preselectedKey || $t || $errors->any()) ? 2 : 1;
    $initialStep = $errors->any() ? $errorStep : (int) old('_step', $startStep);
    $wizardTemplates = [];
    foreach ($pickerGroups as $group) {
        foreach ($group['templates'] as $key => $item) {
            $wizardTemplates[$key] = [
                'key' => $key,
                'name' => $item['name'],
                'description' => $item['description'],
                'type' => $item['type'],
                'spend_step' => $item['spend_step'] ?? null,
                'points_per_step' => $item['points_per_step'] ?? null,
                'bonus_points' => $item['bonus_points'] ?? null,
                'streak_target' => $item['streak_target'] ?? null,
                'streak_period' => $item['streak_period'] ?? null,
            ];
        }
    }
    $namePlaceholder = $t['name'] ?? __('loop.campaign_name_placeholder', ['business' => $business->name]);
    $descPlaceholder = $t['description'] ?? __('loop.campaign_desc_placeholder');
    $spendPlaceholder = isset($t['spend_step']) && $t['spend_step']
        ? number_format((int) $t['spend_step'])
        : number_format(1000);
    $pointsPlaceholder = (string) ($t['points_per_step'] ?? 2);
    $bonusPlaceholder = (string) ($t['bonus_points'] ?? 20);
    $typeLabels = [
        'earn' => __('loop.type_earn'),
        'product_push' => __('loop.type_product_push'),
        'birthday' => __('loop.type_birthday'),
        'welcome' => __('loop.type_welcome'),
        'streak' => __('loop.type_streak'),
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.new_campaign') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.campaign_create_blurb') }}</p>
    </x-slot>

    <div
        class="mx-auto max-w-2xl"
        x-data="campaignWizard({
            step: {{ (int) $initialStep }},
            total: 4,
            hasPick: true,
            skipBonuses: true,
            templateKey: @js($preselectedKey ?: ''),
            fromTemplate: {{ $fromTemplate ? 'true' : 'false' }},
            pickedLabel: @js($t['name'] ?? ''),
            namePlaceholder: @js($namePlaceholder),
            descPlaceholder: @js($descPlaceholder),
            spendPlaceholder: @js($spendPlaceholder),
            pointsPlaceholder: @js($pointsPlaceholder),
            bonusPlaceholder: @js($bonusPlaceholder),
            templates: @js($wizardTemplates),
            type: @js($defaultType),
            typeLabels: @js($typeLabels),
            spendDisplay: @js($spendDisplayInit),
            pointsPerStep: {{ $pointsInit }},
            bonusPoints: {{ $bonusInit }},
            streakTarget: {{ $streakTargetInit }},
            streakPeriod: @js(old('streak_period', $t['streak_period'] ?? 'week')),
            currency: @js($business->currency),
            spendRequired: @js(__('loop.campaign_spend_required')),
            pointsRequired: @js(__('loop.campaign_points_required')),
            bonusRequired: @js(__('loop.campaign_bonus_required')),
            pickRequired: @js(__('loop.pick_required')),
        })"
    >
        <x-form-stepper :steps="$createSteps" />

        <form
            x-ref="form"
            method="POST"
            action="{{ route('campaigns.store') }}"
            class="space-y-6 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8"
            @submit="submitForm($event)"
        >
            @csrf
            <input type="hidden" name="_step" :value="step">
            <input type="hidden" name="template_key" :value="templateKey">
            <input type="hidden" name="type" :value="type">
            @if ($errors->any())
                <div class="rounded-xl border border-coral/30 bg-coral/10 px-4 py-3 text-sm text-ink" role="alert">
                    {{ $errors->first() ?: __('loop.campaign_form_errors') }}
                </div>
            @endif

            {{-- 1 · Pick template --}}
            <div data-step="1" x-show="step === 1" class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">1 · {{ __('loop.section_pick') }}</p>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.pick_campaign_template') }}</h2>
                <p class="text-sm text-ink-muted">{{ __('loop.campaign_pick_hint') }}</p>
                <button type="button" x-ref="pickAnchor" class="sr-only" tabindex="-1">{{ __('loop.pick_required') }}</button>

                @forelse ($pickerGroups as $group)
                    <section class="space-y-3">
                        <div>
                            <h3 class="font-display text-base font-semibold">{{ $group['label'] }}</h3>
                            @if (! empty($group['hint']))
                                <p class="mt-1 text-sm text-ink-muted">{{ $group['hint'] }}</p>
                            @endif
                        </div>
                        @foreach ($group['templates'] as $key => $item)
                            <button
                                type="button"
                                @click="pickTemplate(@js($key))"
                                class="flex w-full min-h-[7.5rem] flex-col rounded-3xl border bg-white p-5 text-left transition"
                                :class="templateKey === @js($key) ? 'border-mint ring-2 ring-mint/20' : 'border-ink/10 hover:border-mint'"
                            >
                                <p class="font-display text-lg font-semibold">{{ $item['name'] }}</p>
                                <p class="mt-2 flex-1 text-sm text-ink-muted">{{ $item['description'] }}</p>
                                <p x-show="templateKey === @js($key)" x-cloak class="mt-3 text-sm font-semibold text-mint-deep">{{ __('loop.offer_type_selected_hint') }}</p>
                            </button>
                        @endforeach
                    </section>
                @empty
                    <div class="rounded-2xl border border-ink/10 bg-chalk/50 p-4 text-sm text-ink-muted">{{ __('loop.all_templates_used') }}</div>
                @endforelse

                <button type="button" class="loop-btn-mint w-full" @click="next()">{{ __('loop.continue') }}</button>
            </div>

            {{-- 2 · Basics --}}
            <div data-step="2" x-show="step === 2" x-cloak class="space-y-4">
                <div x-show="fromTemplate" x-cloak class="rounded-2xl border border-ink/10 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.start_from_template') }}</p>
                    <p class="mt-1 font-display text-xl font-semibold" x-text="pickedLabel"></p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.edit_template_step_hint') }}</p>
                </div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">2 · {{ __('loop.section_basics') }}</p>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.campaign_name') }}</h2>
                <div>
                    <label class="loop-label">{{ __('loop.campaign_name') }}</label>
                    <input name="name" class="loop-input" value="{{ old('name') }}" placeholder="{{ $namePlaceholder }}" :placeholder="namePlaceholder" :required="step === 2" autocomplete="off">
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    <p class="mt-1 text-xs text-ink-muted">{{ __('loop.campaign_name_hint', ['business' => $business->name]) }}</p>
                </div>
                <p class="rounded-xl bg-chalk/60 px-3 py-2 text-sm text-ink-muted">
                    <span x-text="type === 'earn' ? @js(__('loop.main_campaign')) : @js(__('loop.bonus_campaign'))"></span>
                    ·
                    <span class="font-semibold text-ink" x-text="typeLabel()"></span>
                </p>
                <div>
                    <label class="loop-label">{{ __('loop.description') }}</label>
                    <textarea name="description" rows="2" class="loop-input" placeholder="{{ $descPlaceholder }}" :placeholder="descPlaceholder">{{ old('description') }}</textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(1)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                </div>
            </div>

            {{-- 3 · Customer gets / bonus --}}
            <div data-step="3" x-show="step === 3" x-cloak class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">3 · {{ __('loop.customer_gets') }}</p>
                <h2 class="font-display text-xl font-semibold" x-text="type === 'earn' ? @js(__('loop.customer_gets')) : @js(__('loop.section_bonus'))"></h2>
                <input type="hidden" name="spend_step" :value="type === 'earn' ? spendValue() : ''">
                <input type="hidden" name="points_per_step" :value="type === 'earn' ? (pointsPerStep || '') : ''">
                <input type="hidden" name="bonus_points" :value="type === 'earn' ? 0 : (bonusPoints || '')">

                <div x-show="type === 'earn'" class="space-y-3">
                    <p class="text-sm text-ink-muted">{{ __('loop.min_spend_section_help') }}</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="loop-label">{{ __('loop.min_spend_to_earn') }} ({{ $business->currency }})</label>
                            <input type="text" inputmode="numeric" class="loop-input" x-model="spendDisplay" @input="formatSpend()" data-spend-input :placeholder="spendPlaceholder" :required="step === 3 && type === 'earn'">
                            <x-input-error :messages="$errors->get('spend_step')" class="mt-1" />
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.points_earned') }}</label>
                            <input type="number" class="loop-input" x-model="pointsPerStep" data-points-input :placeholder="pointsPlaceholder" :required="step === 3 && type === 'earn'" min="1">
                            <x-input-error :messages="$errors->get('points_per_step')" class="mt-1" />
                        </div>
                    </div>
                    <p class="text-center font-display text-xl font-bold">
                        <span x-text="pointsPerStep || '—'"></span> {{ __('loop.pts') }} /
                        <span x-text="spendDisplay || '0'"></span> <span x-text="currency"></span>
                    </p>
                </div>

                <div x-show="type === 'product_push'" x-cloak class="space-y-3">
                    <p class="text-sm text-ink-muted">{{ __('loop.featured_product_till_hint') }}</p>
                    <div>
                        <label class="loop-label">{{ __('loop.featured_product_name') }}</label>
                        <input type="text" name="featured_product_name" class="loop-input" value="{{ old('featured_product_name') }}" :required="step === 3 && type === 'product_push'" placeholder="{{ __('loop.featured_product_placeholder') }}">
                        <x-input-error :messages="$errors->get('featured_product_name')" class="mt-1" />
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.featured_bonus_points') }}</label>
                        <input type="number" min="1" class="loop-input" x-model="bonusPoints" data-bonus-input :placeholder="bonusPlaceholder" :required="step === 3 && type === 'product_push'">
                        <x-input-error :messages="$errors->get('bonus_points')" class="mt-1" />
                    </div>
                </div>

                <div x-show="type === 'birthday' || type === 'welcome'" x-cloak class="space-y-3">
                    <p class="text-sm text-ink-muted">{{ __('loop.bonus_section_hint') }}</p>
                    <div>
                        <label class="loop-label">{{ __('loop.bonus_points') }}</label>
                        <input type="number" min="1" class="loop-input" x-model="bonusPoints" data-bonus-input :placeholder="bonusPlaceholder" :required="step === 3 && (type === 'birthday' || type === 'welcome')">
                        <x-input-error :messages="$errors->get('bonus_points')" class="mt-1" />
                    </div>
                </div>

                <div x-show="type === 'streak'" x-cloak class="space-y-3">
                    <p class="text-sm text-ink-muted">{{ __('loop.streak_section_hint') }}</p>
                    <div class="grid gap-2 sm:grid-cols-3">
                        <input type="number" name="streak_target" min="2" class="loop-input" placeholder="{{ __('loop.streak_target') }}" x-model="streakTarget" :required="step === 3 && type === 'streak'">
                        <select name="streak_period" class="loop-input" x-model="streakPeriod" :required="step === 3 && type === 'streak'">
                            <option value="week">{{ __('loop.streak_period_week') }}</option>
                            <option value="month">{{ __('loop.streak_period_month') }}</option>
                        </select>
                        <input type="number" min="1" class="loop-input" placeholder="{{ __('loop.bonus_points') }}" x-model="bonusPoints" data-bonus-input :required="step === 3 && type === 'streak'">
                    </div>
                    <x-input-error :messages="$errors->get('streak_points')" class="mt-1" />
                    <x-input-error :messages="$errors->get('bonus_points')" class="mt-1" />
                </div>

                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(2)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                </div>
            </div>

            {{-- 4 · Schedule --}}
            <div data-step="4" x-show="step === 4" x-cloak class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">4 · {{ __('loop.section_schedule') }}</p>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.section_schedule') }}</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-date-field name="starts_at" :label="__('loop.starts')" :value="old('starts_at', now()->format('Y-m-d'))" required />
                    <x-date-field name="ends_at" :label="__('loop.ends')" :value="old('ends_at')" optional />
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
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(3)">{{ __('loop.back') }}</button>
                    <button type="submit" class="loop-btn-mint flex-1" :disabled="saving" :class="{ 'opacity-70': saving }">
                        <span x-show="!saving">{{ __('loop.launch_campaign') }}</span>
                        <span x-show="saving" x-cloak>{{ __('loop.saving') }}</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
