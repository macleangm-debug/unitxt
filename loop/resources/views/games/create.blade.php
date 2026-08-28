@php
    $createSteps = [
        1 => __('loop.game_step_type'),
        2 => __('loop.game_step_who'),
        3 => __('loop.game_step_limit'),
        4 => __('loop.game_step_prizes'),
        5 => __('loop.game_step_winning'),
        6 => __('loop.game_step_when'),
        7 => __('loop.game_step_launch'),
    ];
    $typeLabels = [
        'spin' => __('loop.game_type_spin'),
        'boxes' => __('loop.game_type_boxes'),
        'scratch' => __('loop.game_type_scratch'),
    ];
    $oldPrizes = old('prizes', [
        ['kind' => 'free_item', 'name' => '', 'quantity' => 10, 'points_value' => 20],
        ['kind' => 'points', 'name' => '', 'quantity' => 50, 'points_value' => 20],
    ]);
    $errorStep = 1;
    if ($errors->hasAny(['qualify_mode', 'spend_threshold', 'visit_threshold'])) {
        $errorStep = 2;
    } elseif ($errors->has('play_limit')) {
        $errorStep = 3;
    } elseif ($errors->has('prizes') || $errors->has('prizes.0.name')) {
        $errorStep = 4;
    } elseif ($errors->hasAny(['win_mode', 'odds_every', 'spread_count'])) {
        $errorStep = 5;
    } elseif ($errors->hasAny(['starts_at', 'ends_at'])) {
        $errorStep = 6;
    }
    $initialStep = $errors->any() ? $errorStep : (int) old('_step', 1);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start gap-3">
            <x-back-icon :href="route('games.index')" />
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ __('loop.create_game') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.create_game_blurb') }}</p>
            </div>
        </div>
    </x-slot>

    <div
        class="mx-auto max-w-2xl"
        x-data="gameWizard({
            step: {{ (int) $initialStep }},
            type: @js(old('type', '')),
            typeLabels: @js($typeLabels),
            qualify: @js(old('qualify_mode', $recommend['qualify_mode'])),
            spend: {{ (int) old('spend_threshold', $recommend['spend_threshold']) }},
            visits: {{ (int) old('visit_threshold', $recommend['visit_threshold']) }},
            playLimit: @js(old('play_limit', $recommend['play_limit'])),
            winMode: @js(old('win_mode', 'automatic')),
            oddsEvery: {{ (int) old('odds_every', 5) }},
            spreadCount: {{ (int) old('spread_count', 5) }},
            expectedPlays: {{ (int) old('expected_plays', $recommend['expected_plays']) }},
            typicalSpend: {{ (int) $recommend['typical_spend'] }},
            currency: @js($business->currency),
            pickRequired: @js(__('loop.pick_required')),
        })"
    >
        <x-form-stepper :steps="$createSteps" />

        <form method="POST" action="{{ route('games.store') }}" class="space-y-6 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8" @submit="submitForm($event)">
            @csrf
            <input type="hidden" name="_step" :value="step">
            <input type="hidden" name="type" :value="type">
            <input type="hidden" name="qualify_mode" :value="qualify">
            <input type="hidden" name="play_limit" :value="playLimit">
            <input type="hidden" name="win_mode" :value="winMode">
            <input type="hidden" name="expected_plays" :value="expectedPlays">

            @if ($errors->any())
                <div class="rounded-xl border border-coral/30 bg-coral/10 px-4 py-3 text-sm text-ink" role="alert">{{ $errors->first() }}</div>
            @endif

            <div data-step="1" x-show="step === 1" class="space-y-4">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.game_how_play') }}</h2>
                <div class="space-y-3">
                    @foreach ($types as $key)
                        <button type="button" @click="type = @js($key)" class="flex w-full flex-col rounded-3xl border bg-white p-5 text-left" :class="type === @js($key) ? 'border-mint ring-2 ring-mint/20' : 'border-ink/10'">
                            <p class="font-display text-lg font-semibold">{{ $typeLabels[$key] }}</p>
                            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.game_type_'.$key.'_body') }}</p>
                        </button>
                    @endforeach
                </div>
                <button type="button" class="loop-btn-mint w-full" @click="next()">{{ __('loop.continue') }}</button>
            </div>

            <div data-step="2" x-show="step === 2" x-cloak class="space-y-4">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.game_who_plays') }}</h2>
                <p class="rounded-2xl bg-mint-soft px-4 py-3 text-sm">{{ __('loop.game_loop_recommends') }}: <strong x-text="recommendLine()"></strong></p>
                <div class="grid gap-3">
                    <button type="button" class="rounded-3xl border p-4 text-left" :class="qualify === 'spend' ? 'border-mint ring-2 ring-mint/20' : 'border-ink/10'" @click="qualify = 'spend'">
                        <p class="font-semibold">{{ __('loop.game_qualify_spend') }}</p>
                    </button>
                    <button type="button" class="rounded-3xl border p-4 text-left" :class="qualify === 'visits' ? 'border-mint ring-2 ring-mint/20' : 'border-ink/10'" @click="qualify = 'visits'">
                        <p class="font-semibold">{{ __('loop.game_qualify_visits') }}</p>
                    </button>
                    <button type="button" class="rounded-3xl border p-4 text-left" :class="qualify === 'both' ? 'border-mint ring-2 ring-mint/20' : 'border-ink/10'" @click="qualify = 'both'">
                        <p class="font-semibold">{{ __('loop.game_qualify_both') }}</p>
                    </button>
                </div>
                <div x-show="qualify !== 'visits'">
                    <label class="loop-label">{{ $business->currency }}</label>
                    <input type="hidden" name="spend_threshold" :value="spend">
                    <input type="text" inputmode="numeric" x-model="spendDisplay" @input="formatSpend()" class="loop-input" placeholder="1,000">
                </div>
                <div x-show="qualify !== 'spend'">
                    <label class="loop-label">{{ __('loop.game_visit_threshold') }}</label>
                    <input type="number" min="2" max="50" name="visit_threshold" x-model.number="visits" class="loop-input">
                </div>
                <button type="button" class="loop-btn-mint w-full" @click="next()">{{ __('loop.continue') }}</button>
            </div>

            <div data-step="3" x-show="step === 3" x-cloak class="space-y-4">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.game_how_often') }}</h2>
                @foreach (['daily' => __('loop.game_limit_daily'), 'transaction' => __('loop.game_limit_transaction'), 'game' => __('loop.game_limit_game')] as $key => $label)
                    <button type="button" class="w-full rounded-3xl border p-4 text-left" :class="playLimit === @js($key) ? 'border-mint ring-2 ring-mint/20' : 'border-ink/10'" @click="playLimit = @js($key)">
                        <p class="font-semibold">{{ $label }}@if ($key === 'daily') <span class="text-mint-deep">· {{ __('loop.recommended') }}</span> @endif</p>
                    </button>
                @endforeach
                <button type="button" class="loop-btn-mint w-full" @click="next()">{{ __('loop.continue') }}</button>
            </div>

            <div data-step="4" x-show="step === 4" x-cloak class="space-y-4">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.game_what_they_win') }}</h2>
                @foreach ($oldPrizes as $i => $prize)
                    @php
                        $kindOptions = collect($prizeKinds)->mapWithKeys(fn ($kind) => [$kind => __('loop.game_prize_kind_'.$kind)])->all();
                        $kind = $prize['kind'] ?? 'free_item';
                    @endphp
                    <div
                        class="space-y-3 rounded-2xl border border-ink/10 p-4"
                        x-data="{
                            kind: @js($kind),
                            cost: {{ (int) ($prize['unit_cost'] ?? 0) }},
                            costDisplay: @js(number_format((int) ($prize['unit_cost'] ?? 0))),
                        }"
                        @sheet-selected.window="if ($event.detail.name === 'prizes[{{ $i }}][kind]') kind = $event.detail.value"
                    >
                        <x-sheet-select
                            name="prizes[{{ $i }}][kind]"
                            :label="__('loop.game_prize_kind')"
                            :options="$kindOptions"
                            :value="$kind"
                            :required="true"
                        />
                        <template x-if="kind === 'free_item'">
                            <div class="space-y-3">
                                <input name="prizes[{{ $i }}][name]" value="{{ $prize['name'] ?? '' }}" class="loop-input" placeholder="{{ __('loop.game_prize_name') }}">
                                <input type="number" min="1" name="prizes[{{ $i }}][quantity]" value="{{ $prize['quantity'] ?? 10 }}" class="loop-input" placeholder="{{ __('loop.game_prize_qty') }}">
                                <div>
                                    <label class="loop-label">{{ __('loop.game_prize_unit_cost') }}</label>
                                    <input type="hidden" name="prizes[{{ $i }}][unit_cost]" :value="cost">
                                    <input type="text" inputmode="numeric" class="loop-input" x-model="costDisplay" @input="costDisplay = window.loopNumber.formatInput(costDisplay); cost = window.loopNumber.parse(costDisplay)">
                                </div>
                            </div>
                        </template>
                        <template x-if="kind === 'percent'">
                            <div class="space-y-3">
                                <label class="loop-label">{{ __('loop.game_prize_percent') }}</label>
                                <input type="number" min="1" max="100" name="prizes[{{ $i }}][percent_value]" value="{{ $prize['percent_value'] ?? 10 }}" class="loop-input">
                                <input type="number" min="1" name="prizes[{{ $i }}][quantity]" value="{{ $prize['quantity'] ?? 10 }}" class="loop-input" placeholder="{{ __('loop.game_prize_qty') }}">
                                <input name="prizes[{{ $i }}][name]" value="{{ $prize['name'] ?? '' }}" class="loop-input" placeholder="{{ __('loop.game_prize_name') }}">
                            </div>
                        </template>
                        <template x-if="kind === 'points'">
                            <div class="space-y-3">
                                <label class="loop-label">{{ __('loop.pts') }}</label>
                                <input type="number" min="1" name="prizes[{{ $i }}][points_value]" value="{{ $prize['points_value'] ?? 20 }}" class="loop-input">
                                <input type="number" min="1" name="prizes[{{ $i }}][quantity]" value="{{ $prize['quantity'] ?? 50 }}" class="loop-input" placeholder="{{ __('loop.game_prize_qty') }}">
                                <input name="prizes[{{ $i }}][name]" value="{{ $prize['name'] ?? '' }}" class="loop-input" placeholder="{{ __('loop.game_prize_name') }}">
                            </div>
                        </template>
                        <template x-if="kind === 'custom'">
                            <div class="space-y-3">
                                <input name="prizes[{{ $i }}][name]" value="{{ $prize['name'] ?? '' }}" class="loop-input" placeholder="{{ __('loop.game_prize_name') }}">
                                <input type="number" min="1" name="prizes[{{ $i }}][quantity]" value="{{ $prize['quantity'] ?? 10 }}" class="loop-input" placeholder="{{ __('loop.game_prize_qty') }}">
                            </div>
                        </template>
                    </div>
                @endforeach
                <p class="text-sm text-ink-muted">{{ __('loop.game_no_prize_also') }}</p>
                <button type="button" class="loop-btn-mint w-full" @click="next()">{{ __('loop.continue') }}</button>
            </div>

            <div data-step="5" x-show="step === 5" x-cloak class="space-y-4">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.game_how_winning') }}</h2>
                <button type="button" class="w-full rounded-3xl border p-4 text-left" :class="winMode === 'automatic' ? 'border-mint ring-2 ring-mint/20' : 'border-ink/10'" @click="winMode = 'automatic'">
                    <p class="font-semibold">{{ __('loop.game_win_automatic') }} <span class="text-mint-deep">· {{ __('loop.recommended') }}</span></p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.game_win_automatic_body') }}</p>
                </button>
                <button type="button" class="w-full rounded-3xl border p-4 text-left" :class="winMode === 'odds' ? 'border-mint ring-2 ring-mint/20' : 'border-ink/10'" @click="winMode = 'odds'">
                    <p class="font-semibold">{{ __('loop.game_win_odds') }}</p>
                </button>
                <div x-show="winMode === 'odds'" x-cloak>
                    <label class="loop-label">{{ __('loop.game_odds_every') }}</label>
                    <input type="number" min="2" max="100" name="odds_every" x-model.number="oddsEvery" class="loop-input">
                </div>
                <button type="button" class="w-full rounded-3xl border p-4 text-left" :class="winMode === 'spread' ? 'border-mint ring-2 ring-mint/20' : 'border-ink/10'" @click="winMode = 'spread'">
                    <p class="font-semibold">{{ __('loop.game_win_spread') }}</p>
                </button>
                <div x-show="winMode === 'spread'" x-cloak class="grid gap-3 sm:grid-cols-2">
                    <input type="number" min="1" name="spread_count" x-model.number="spreadCount" class="loop-input">
                    <select name="spread_period" class="loop-input">
                        <option value="day">{{ __('loop.game_per_day') }}</option>
                        <option value="week">{{ __('loop.game_per_week') }}</option>
                    </select>
                </div>
                <button type="button" class="loop-btn-mint w-full" @click="next()">{{ __('loop.continue') }}</button>
            </div>

            <div data-step="6" x-show="step === 6" x-cloak class="space-y-4">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.game_when_runs') }}</h2>
                <x-date-field name="starts_at" :label="__('loop.starts')" :value="old('starts_at', now()->format('Y-m-d'))" :min="now()->format('Y-m-d')" required />
                <x-date-field name="ends_at" :label="__('loop.ends')" :value="old('ends_at', now()->addDays(6)->format('Y-m-d'))" :min="now()->format('Y-m-d')" required />
                <button type="button" class="loop-btn-mint w-full" @click="next()">{{ __('loop.continue') }}</button>
            </div>

            <div data-step="7" x-show="step === 7" x-cloak class="space-y-4">
                <h2 class="font-display text-xl font-semibold" x-text="typeLabels[type] || ''"></h2>
                <p class="text-sm text-ink-muted" x-text="summaryLine()"></p>
                <p class="text-sm">{{ __('loop.game_better_luck_included') }}</p>
                <button class="loop-btn-mint w-full">{{ __('loop.launch_game') }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
