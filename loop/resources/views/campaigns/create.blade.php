@php
    $selectedShops = old('shop_ids', []);
    $t = $template ?? null;
    $own = (bool) ($createOwn ?? false);
    $defaultType = old('type', $t['type'] ?? 'earn');
    if (! in_array($defaultType, ['earn', 'product_push'], true)) {
        $defaultType = 'earn';
    }
    $preselectedKey = old('template_key', $templateKey);
    if ($own && ! $preselectedKey) {
        $preselectedKey = 'own';
    }
    $fromTemplate = (bool) ($t && $preselectedKey && $preselectedKey !== 'own');
    $createSteps = [
        1 => __('loop.section_pick'),
        2 => __('loop.section_basics'),
        3 => __('loop.customer_gets'),
        4 => __('loop.section_bonuses'),
        5 => __('loop.section_schedule'),
    ];
    $oldSpend = old('spend_step');
    $spendDisplayInit = ($oldSpend !== null && $oldSpend !== '' && (int) $oldSpend > 0)
        ? number_format((int) $oldSpend)
        : '';
    $oldPoints = old('points_per_step');
    $pointsInit = ($oldPoints !== null && $oldPoints !== '') ? (int) $oldPoints : 'null';
    $oldBonus = old('bonus_points');
    $bonusInit = ($oldBonus !== null && $oldBonus !== '') ? (int) $oldBonus : 'null';
    $errorStep = 2;
    if ($errors->hasAny(['spend_step', 'points_per_step', 'featured_product_name', 'bonus_points'])) {
        $errorStep = 3;
    } elseif ($errors->hasAny(['welcome_points', 'birthday_points', 'streak_target', 'streak_period', 'streak_points'])) {
        $errorStep = 4;
    } elseif ($errors->hasAny(['starts_at', 'ends_at', 'shop_ids'])) {
        $errorStep = 5;
    }
    $startStep = ($preselectedKey || $t || $own || $errors->any()) ? 2 : 1;
    $initialStep = $errors->any() ? $errorStep : (int) old('_step', $startStep);
    $wizardTemplates = [];
    foreach ($groupedTemplates as $intention => $group) {
        if ($intention === 'retention') {
            continue;
        }
        foreach ($group['templates'] as $key => $item) {
            $wizardTemplates[$key] = [
                'key' => $key,
                'name' => $item['name'],
                'description' => $item['description'],
                'type' => $item['type'],
                'spend_step' => $item['spend_step'],
                'points_per_step' => $item['points_per_step'],
            ];
        }
    }
    $namePlaceholder = $t['name'] ?? __('loop.campaign_name_placeholder', ['business' => $business->name]);
    $descPlaceholder = $t['description'] ?? __('loop.campaign_desc_placeholder');
    $spendPlaceholder = isset($t['spend_step']) && $t['spend_step']
        ? number_format((int) $t['spend_step'])
        : number_format(1000);
    $pointsPlaceholder = (string) ($t['points_per_step'] ?? 2);
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
            total: 5,
            hasPick: true,
            templateKey: @js($preselectedKey ?: ''),
            fromTemplate: {{ $fromTemplate ? 'true' : 'false' }},
            pickedLabel: @js($t['name'] ?? ''),
            namePlaceholder: @js($namePlaceholder),
            descPlaceholder: @js($descPlaceholder),
            spendPlaceholder: @js($spendPlaceholder),
            pointsPlaceholder: @js($pointsPlaceholder),
            templates: @js($wizardTemplates),
            type: @js($defaultType),
            enableWelcome: {{ old('enable_welcome') ? 'true' : 'false' }},
            enableBirthday: {{ old('enable_birthday') ? 'true' : 'false' }},
            enableStreak: {{ old('enable_streak') ? 'true' : 'false' }},
            spendDisplay: @js($spendDisplayInit),
            pointsPerStep: {{ $pointsInit }},
            bonusPoints: {{ $bonusInit }},
            currency: @js($business->currency),
            spendRequired: @js(__('loop.campaign_spend_required')),
            pointsRequired: @js(__('loop.campaign_points_required')),
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
            <input type="hidden" name="template_key" :value="templateKey === 'own' ? '' : templateKey">
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

                @forelse ($groupedTemplates as $intention => $group)
                    @if ($intention === 'retention')
                        @continue
                    @endif
                    <section class="space-y-3">
                        <h3 class="font-display text-base font-semibold">{{ $group['label'] }}</h3>
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

                <button
                    type="button"
                    @click="pickOwn()"
                    class="flex w-full min-h-[7.5rem] flex-col rounded-3xl border border-dashed bg-chalk/50 p-5 text-left transition"
                    :class="templateKey === 'own' ? 'border-mint ring-2 ring-mint/20 bg-white' : 'border-ink/20 hover:border-mint'"
                >
                    <p class="font-display text-lg font-semibold">{{ __('loop.create_own') }}</p>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.create_own_campaign_body') }}</p>
                </button>

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
                <p x-show="fromTemplate" x-cloak class="rounded-xl bg-chalk/60 px-3 py-2 text-sm text-ink-muted">
                    {{ __('loop.type') }}:
                    <span class="font-semibold text-ink" x-text="type === 'product_push' ? @js(__('loop.type_product_push')) : @js(__('loop.type_earn'))"></span>
                </p>
                <div x-show="!fromTemplate" x-cloak>
                    <label class="loop-label">{{ __('loop.type') }}</label>
                    <select class="loop-input" x-model="type">
                        <option value="earn">{{ __('loop.type_earn') }}</option>
                        <option value="product_push">{{ __('loop.type_product_push') }}</option>
                    </select>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.description') }}</label>
                    <textarea name="description" rows="2" class="loop-input" placeholder="{{ $descPlaceholder }}" :placeholder="descPlaceholder">{{ old('description') }}</textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(1)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                </div>
            </div>

            {{-- 3 · Customer gets --}}
            <div data-step="3" x-show="step === 3" x-cloak class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">3 · {{ __('loop.customer_gets') }}</p>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.customer_gets') }}</h2>
                <p class="text-sm text-ink-muted">{{ __('loop.min_spend_section_help') }}</p>
                <input type="hidden" name="spend_step" :value="spendValue()">
                <input type="hidden" name="bonus_points" :value="type === 'product_push' ? (bonusPoints || '') : 0">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="loop-label">{{ __('loop.min_spend_to_earn') }} ({{ $business->currency }})</label>
                        <input type="text" inputmode="numeric" class="loop-input" x-model="spendDisplay" @input="formatSpend()" data-spend-input :placeholder="spendPlaceholder" :required="step === 3">
                        <x-input-error :messages="$errors->get('spend_step')" class="mt-1" />
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.points_earned') }}</label>
                        <input type="number" name="points_per_step" class="loop-input" x-model="pointsPerStep" :placeholder="pointsPlaceholder" :required="step === 3" min="1">
                        <x-input-error :messages="$errors->get('points_per_step')" class="mt-1" />
                    </div>
                </div>
                <p class="text-center font-display text-xl font-bold">
                    <span x-text="pointsPerStep || '—'"></span> {{ __('loop.pts') }} /
                    <span x-text="spendDisplay || '0'"></span> <span x-text="currency"></span>
                </p>
                <div x-show="type === 'product_push'" x-cloak class="space-y-3 rounded-2xl border border-violet/20 bg-violet-soft/40 p-4">
                    <div>
                        <label class="loop-label">{{ __('loop.featured_product_name') }}</label>
                        <input type="text" name="featured_product_name" class="loop-input" value="{{ old('featured_product_name') }}" :required="step === 3 && type === 'product_push'" placeholder="{{ __('loop.featured_product_placeholder') }}">
                        <p class="mt-1 text-xs text-ink-muted">{{ __('loop.featured_product_till_hint') }}</p>
                        <x-input-error :messages="$errors->get('featured_product_name')" class="mt-1" />
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.featured_bonus_points') }}</label>
                        <input type="number" min="1" class="loop-input" x-model="bonusPoints" data-bonus-input :required="step === 3 && type === 'product_push'" placeholder="10">
                        <x-input-error :messages="$errors->get('bonus_points')" class="mt-1" />
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(2)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                </div>
            </div>

            {{-- 4 · Bonuses (optional) --}}
            <div data-step="4" x-show="step === 4" x-cloak class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">4 · {{ __('loop.section_bonuses') }}</p>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.bonuses_title') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.bonuses_body') }}</p>

                <label class="flex items-start gap-3 rounded-2xl bg-chalk/50 px-4 py-3 ring-1 ring-ink/5">
                    <input type="checkbox" name="enable_welcome" value="1" class="mt-1" x-model="enableWelcome">
                    <span class="flex-1">
                        <span class="block text-sm font-semibold">{{ __('loop.type_welcome') }}</span>
                        <span class="text-xs text-ink-muted">{{ __('loop.welcome_bonus_hint') }}</span>
                        <input type="number" name="welcome_points" min="1" class="loop-input mt-2" value="{{ old('welcome_points') }}" placeholder="20" x-show="enableWelcome" x-cloak :required="step === 4 && enableWelcome">
                        <x-input-error :messages="$errors->get('welcome_points')" class="mt-1" />
                    </span>
                </label>

                <label class="flex items-start gap-3 rounded-2xl bg-chalk/50 px-4 py-3 ring-1 ring-ink/5">
                    <input type="checkbox" name="enable_birthday" value="1" class="mt-1" x-model="enableBirthday">
                    <span class="flex-1">
                        <span class="block text-sm font-semibold">{{ __('loop.type_birthday') }}</span>
                        <span class="text-xs text-ink-muted">{{ __('loop.birthday_bonus_hint') }}</span>
                        <input type="number" name="birthday_points" min="1" class="loop-input mt-2" value="{{ old('birthday_points') }}" placeholder="50" x-show="enableBirthday" x-cloak :required="step === 4 && enableBirthday">
                        <x-input-error :messages="$errors->get('birthday_points')" class="mt-1" />
                    </span>
                </label>

                <label class="flex items-start gap-3 rounded-2xl bg-chalk/50 px-4 py-3 ring-1 ring-ink/5">
                    <input type="checkbox" name="enable_streak" value="1" class="mt-1" x-model="enableStreak">
                    <span class="flex-1">
                        <span class="block text-sm font-semibold">{{ __('loop.type_streak') }}</span>
                        <span class="text-xs text-ink-muted">{{ __('loop.streak_advice') }}</span>
                        <div class="mt-2 grid gap-2 sm:grid-cols-3" x-show="enableStreak" x-cloak>
                            <input type="number" name="streak_target" min="2" class="loop-input" placeholder="{{ __('loop.streak_target') }}" value="{{ old('streak_target') }}" :required="step === 4 && enableStreak">
                            <select name="streak_period" class="loop-input" :required="step === 4 && enableStreak">
                                <option value="week">{{ __('loop.streak_period_week') }}</option>
                                <option value="month">{{ __('loop.streak_period_month') }}</option>
                            </select>
                            <input type="number" name="streak_points" min="1" class="loop-input" placeholder="{{ __('loop.bonus_points') }}" value="{{ old('streak_points') }}" :required="step === 4 && enableStreak">
                        </div>
                        <x-input-error :messages="$errors->get('streak_points')" class="mt-1" />
                    </span>
                </label>

                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(3)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                </div>
                <button type="button" class="w-full text-sm font-semibold text-ink-muted underline" @click="go(5)">{{ __('loop.skip_for_now') }}</button>
            </div>

            {{-- 5 · Schedule --}}
            <div data-step="5" x-show="step === 5" x-cloak class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">5 · {{ __('loop.section_schedule') }}</p>
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
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(4)">{{ __('loop.back') }}</button>
                    <button type="submit" class="loop-btn-mint flex-1" :disabled="saving" :class="{ 'opacity-70': saving }">
                        <span x-show="!saving">{{ __('loop.launch_campaign') }}</span>
                        <span x-show="saving" x-cloak>{{ __('loop.saving') }}</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
