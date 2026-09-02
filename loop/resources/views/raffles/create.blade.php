@php
    $prizeType = old('prize_type', request('prize_type', ''));
    if (! in_array($prizeType, ['free_item', 'percent_off', 'fixed_off', 'custom'], true)) {
        $prizeType = '';
    }
    $frequency = old('frequency', request('frequency', 'once'));
    if (! in_array($frequency, ['once', 'weekly', 'monthly', 'yearly'], true)) {
        $frequency = 'once';
    }
    $drawAt = old('draw_at', request('draw_at', ''));
    $oldValue = old('prize_value');
    $valueSeed = '';
    if ($oldValue !== null && $oldValue !== '') {
        $valueSeed = ($prizeType === 'fixed_off')
            ? number_format((float) $oldValue)
            : (string) $oldValue;
    }
    $typeLabels = collect($prizeTypes)->mapWithKeys(fn ($row) => [$row['key'] => $row['title']])->all();
    $frequencyOptions = [
        'once' => __('loop.freq_once'),
        'weekly' => __('loop.freq_weekly'),
        'monthly' => __('loop.freq_monthly'),
        'yearly' => __('loop.freq_yearly'),
    ];
    $createSteps = [
        1 => __('loop.section_prize'),
        2 => __('loop.section_prize_details'),
        3 => __('loop.section_basics'),
        4 => __('loop.section_schedule'),
    ];
    $errorStep = 1;
    if ($errors->hasAny(['prize_name', 'prize_value', 'prize_type'])) {
        $errorStep = 2;
    } elseif ($errors->hasAny(['name', 'description'])) {
        $errorStep = 3;
    } elseif ($errors->hasAny(['winners_count', 'frequency', 'draw_at', 'claim_days'])) {
        $errorStep = 4;
    }
    $initialStep = $errors->any()
        ? $errorStep
        : (int) old('_step', request('step', $prizeType ? 2 : 1));
    if ($initialStep < 1 || $initialStep > 4) {
        $initialStep = 1;
    }
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start gap-3">
            <x-back-icon :href="route('raffles.index')" />
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ __('loop.create_raffle') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.create_raffle_blurb') }}</p>
            </div>
        </div>
    </x-slot>

    <div
        class="mx-auto max-w-2xl"
        x-data="raffleWizard({
            step: {{ (int) $initialStep }},
            persistKey: 'loop.raffleWizard.create.v2',
            type: @js($prizeType),
            typeLabels: @js($typeLabels),
            typeLabel: @js($prizeType ? ($typeLabels[$prizeType] ?? '') : ''),
            name: @js(old('name', '')),
            description: @js(old('description', '')),
            prizeName: @js(old('prize_name', '')),
            valueDisplay: @js($valueSeed),
            frequency: @js($frequency),
            drawAt: @js($drawAt),
            winners: @js(old('winners_count', '')),
            claimDays: @js(old('claim_days', '')),
            pickRequired: @js(__('loop.pick_required')),
            valueRequired: @js(__('loop.offer_value_required')),
            prizeNameRequired: @js(__('loop.raffle_prize_name_required')),
        })"
        x-effect="persist()"
        @raffle-pick-type="pickType($event.detail.key)"
        @sheet-selected="if ($event.detail.name === 'frequency') { frequency = $event.detail.value; persist(); syncUrl(); }"
    >
        <x-form-stepper :steps="$createSteps" />

        <form
            x-ref="form"
            method="POST"
            action="{{ route('raffles.store') }}"
            class="space-y-6 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8"
            @submit="submitForm($event)"
        >
            @csrf
            <input type="hidden" name="_step" :value="step">
            <input type="hidden" name="prize_type" :value="type">
            <input type="hidden" name="prize_value" :value="type === 'percent_off' || type === 'fixed_off' || (type === 'custom' && valueNumber() > 0) ? valueNumber() : ''">

            @if ($errors->any())
                <div class="rounded-xl border border-coral/30 bg-coral/10 px-4 py-3 text-sm text-ink" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- 1 · Prize type --}}
            <div data-step="1" x-show="step === 1" class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">1 · {{ __('loop.section_prize') }}</p>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.raffle_pick_prize_title') }}</h2>
                <p class="text-sm text-ink-muted">{{ __('loop.prize_like_offer') }}</p>
                <button type="button" x-ref="pickAnchor" class="sr-only" tabindex="-1">{{ __('loop.pick_required') }}</button>

                <button type="button" class="loop-input flex w-full items-center justify-between text-left sm:hidden" @click="$dispatch('open-prize-sheet')">
                    <span :class="type ? 'text-ink' : 'text-ink-muted'" x-text="typeLabel || @js(__('loop.raffle_pick_prize_placeholder'))"></span>
                    <span class="ml-2 shrink-0 text-violet">▾</span>
                </button>
                <div class="sm:hidden" x-data="{ open: false, q: '', selected: @js($prizeType) }" @open-prize-sheet.window="open = true">
                    <x-picker-layer :title="__('loop.raffle_pick_prize_title')" :search="false" search-placeholder="">
                        @foreach ($prizeTypes as $starter)
                            <button
                                type="button"
                                class="loop-picker-option !items-start !whitespace-normal"
                                :class="{ 'is-selected': selected === @js($starter['key']) }"
                                @click="selected = @js($starter['key']); $dispatch('raffle-pick-type', { key: @js($starter['key']) }); open = false"
                            >
                                <span class="block">
                                    <span class="block font-semibold">{{ $starter['title'] }}</span>
                                    <span class="mt-1 block text-sm font-normal text-ink-muted">{{ $starter['body'] }}</span>
                                </span>
                            </button>
                        @endforeach
                    </x-picker-layer>
                </div>

                <div class="hidden space-y-3 sm:block">
                    @foreach ($prizeTypes as $starter)
                        <button
                            type="button"
                            @click="pickType(@js($starter['key']))"
                            class="flex w-full flex-col rounded-3xl border bg-white p-5 text-left transition"
                            :class="type === @js($starter['key']) ? 'border-mint ring-2 ring-mint/20' : 'border-ink/10 hover:border-mint'"
                        >
                            <p class="font-display text-lg font-semibold">{{ $starter['title'] }}</p>
                            <p class="mt-2 text-sm text-ink-muted">{{ $starter['body'] }}</p>
                            <p x-show="type === @js($starter['key'])" x-cloak class="mt-3 text-sm font-semibold text-mint-deep">{{ __('loop.offer_type_selected_hint') }}</p>
                        </button>
                    @endforeach
                </div>

                <button type="button" class="loop-btn-mint w-full" @click="next()">{{ __('loop.continue') }}</button>
            </div>

            {{-- 2 · Prize details --}}
            <div data-step="2" x-show="step === 2" x-cloak class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">2 · {{ __('loop.section_prize_details') }}</p>
                <div x-show="typeLabel" class="rounded-2xl border border-ink/10 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.section_prize') }}</p>
                    <p class="mt-1 font-display text-xl font-semibold" x-text="typeLabel"></p>
                </div>

                <div x-show="!type" x-cloak>
                    <p class="text-sm text-ink-muted">{{ __('loop.pick_required') }}</p>
                    <button type="button" class="loop-btn-mint mt-4 w-full" @click="go(1)">{{ __('loop.back') }}</button>
                </div>

                <div x-show="type === 'percent_off'" x-cloak>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.percent_off_value') }}</h2>
                    <p class="text-sm text-ink-muted">{{ __('loop.raffle_prize_percent_help') }}</p>
                    <input type="number" min="1" max="100" class="loop-input" x-model="valueDisplay" data-value-input placeholder="10" :required="step === 2 && type === 'percent_off'">
                    <p class="mt-2 text-sm font-semibold text-ink">
                        <span x-text="valueDisplay || 0"></span>% {{ __('loop.off_every_eligible_sale') }}
                    </p>
                </div>

                <div x-show="type === 'fixed_off'" x-cloak>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.fixed_off_value') }}</h2>
                    <p class="text-sm text-ink-muted">{{ __('loop.raffle_prize_fixed_help') }}</p>
                    <input type="text" inputmode="numeric" class="loop-input" x-model="valueDisplay" data-value-input @input="formatValue()" :placeholder="@js('2,000')" :required="step === 2 && type === 'fixed_off'">
                    <p class="mt-2 text-sm font-semibold text-ink">
                        {{ $business->currency }} <span x-text="valueDisplay || 0"></span> {{ __('loop.off_every_eligible_sale') }}
                    </p>
                </div>

                <div x-show="type === 'free_item'" x-cloak>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.tie_to_product') }}</h2>
                    <p class="text-sm text-ink-muted">{{ __('loop.raffle_prize_free_help') }}</p>
                    <input name="prize_name" class="loop-input" x-model="prizeName" placeholder="{{ __('loop.prize_name_placeholder') }}" :required="step === 2 && type === 'free_item'" :disabled="type !== 'free_item'" autocomplete="off">
                </div>

                <div x-show="type === 'custom'" x-cloak>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.prize_name') }}</h2>
                    <p class="text-sm text-ink-muted">{{ __('loop.raffle_prize_custom_help') }}</p>
                    <input name="prize_name" class="loop-input" x-model="prizeName" placeholder="{{ __('loop.prize_name_placeholder') }}" :required="step === 2 && type === 'custom'" :disabled="type !== 'custom'" autocomplete="off">
                    <label class="loop-label mt-4">{{ __('loop.value_hint') }}</label>
                    <input type="text" inputmode="decimal" class="loop-input" x-model="valueDisplay" data-value-input placeholder="{{ __('loop.value_hint') }}">
                </div>

                <div class="flex gap-3" x-show="type">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(1)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                </div>
            </div>

            {{-- 3 · Basics --}}
            <div data-step="3" x-show="step === 3" x-cloak class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">3 · {{ __('loop.section_basics') }}</p>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.raffle_name') }}</h2>
                <div>
                    <label class="loop-label">{{ __('loop.raffle_name') }}</label>
                    <input name="name" class="loop-input" x-model="name" placeholder="{{ __('loop.raffle_name_placeholder') }}" :required="step === 3" autocomplete="off">
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.description') }}</label>
                    <textarea name="description" rows="2" class="loop-input" x-model="description">{{ old('description') }}</textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(2)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                </div>
            </div>

            {{-- 4 · Schedule --}}
            <div data-step="4" x-show="step === 4" x-cloak class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">4 · {{ __('loop.section_schedule') }}</p>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.section_draw_rules') }}</h2>

                <x-sheet-select
                    name="frequency"
                    :label="__('loop.frequency')"
                    :options="$frequencyOptions"
                    :value="$frequency"
                    :required="true"
                    :search="false"
                    :placeholder="__('loop.frequency')"
                />

                <p x-show="frequency === 'weekly'" x-cloak class="rounded-xl bg-mint-soft/60 px-3 py-2 text-sm text-ink">{{ __('loop.raffle_weekly_remind_hint') }}</p>
                <p x-show="frequency === 'monthly' || frequency === 'yearly'" x-cloak class="text-sm text-ink-muted">{{ __('loop.raffle_repeat_draw_hint') }}</p>

                <x-date-field name="draw_at" :label="__('loop.start_draw_date')" :value="$drawAt" :min="now()->format('Y-m-d')" required />
                <p class="text-xs text-ink-muted">{{ __('loop.start_draw_date_help') }}</p>
                <x-input-error :messages="$errors->get('draw_at')" class="mt-1" />

                <div>
                    <label class="loop-label">{{ __('loop.how_many_winners') }}</label>
                    <input type="number" min="1" max="{{ $maxWinners }}" name="winners_count" class="loop-input" x-model="winners" placeholder="1" :required="step === 4">
                    <p class="mt-1 text-xs text-ink-muted">{{ __('loop.raffle_winners_cap_help', ['max' => $maxWinners, 'pct' => $maxWinnersPercent, 'members' => $memberCount]) }}</p>
                    <x-input-error :messages="$errors->get('winners_count')" class="mt-1" />
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.claim_days') }}</label>
                    <input type="number" min="1" max="30" name="claim_days" class="loop-input" x-model="claimDays" placeholder="{{ (int) $defaultClaimDays }}" :required="step === 4">
                    <p class="mt-1 text-xs text-ink-muted">{{ __('loop.claim_days_help') }}</p>
                </div>

                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(3)">{{ __('loop.back') }}</button>
                    <button type="submit" class="loop-btn-mint flex-1" :disabled="saving" :class="{ 'opacity-70': saving }">
                        <span x-show="!saving">{{ __('loop.save_raffle') }}</span>
                        <span x-show="saving" x-cloak>{{ __('loop.saving') }}</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
