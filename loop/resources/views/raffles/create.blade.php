<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="font-display text-2xl font-semibold sm:text-3xl">{{ __('loop.create_raffle') }}</h1>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.create_raffle_blurb') }}</p>
            </div>
            <x-settings-back :href="route('raffles.index')" :label="__('loop.back')" />
        </div>
    </x-slot>

    @php
        $raffleSteps = [
            1 => __('loop.section_basics'),
            2 => __('loop.section_prize'),
            3 => __('loop.section_draw_rules'),
        ];
    @endphp

    <div
        class="mx-auto max-w-xl"
        x-data="loopWizard({ step: {{ (int) old('_step', 1) }}, total: 3 })"
    >
        <x-form-stepper :steps="$raffleSteps" />

        <form x-ref="form" method="POST" action="{{ route('raffles.store') }}" class="space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 sm:p-8">
            @csrf
            <input type="hidden" name="_step" :value="step">

            <div data-step="1" x-show="step === 1" class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">1 · {{ __('loop.section_basics') }}</p>
                <div>
                    <label class="loop-label">{{ __('loop.raffle_name') }}</label>
                    <input name="name" class="loop-input" value="{{ old('name') }}" required placeholder="{{ __('loop.raffle_name_placeholder') }}">
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.description') }}</label>
                    <textarea name="description" rows="2" class="loop-input">{{ old('description') }}</textarea>
                </div>
                <button type="button" class="loop-btn-mint w-full" @click.prevent="next()">{{ __('loop.continue') }}</button>
            </div>

            <div data-step="2" x-show="step === 2" x-cloak class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">2 · {{ __('loop.section_prize') }}</p>
                <p class="text-sm text-ink-muted">{{ __('loop.prize_like_offer') }}</p>
                <div>
                    <label class="loop-label">{{ __('loop.prize_name') }}</label>
                    <input name="prize_name" class="loop-input" value="{{ old('prize_name') }}" required placeholder="{{ __('loop.prize_name_placeholder') }}">
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <x-sheet-select
                            name="prize_type"
                            :label="__('loop.type')"
                            :value="old('prize_type', 'custom')"
                            :options="collect(['free_item','percent_off','fixed_off','custom'])->mapWithKeys(fn ($t) => [$t => __('loop.'.$t)])->all()"
                        />
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.value_hint') }}</label>
                        <input type="number" step="0.01" name="prize_value" class="loop-input" value="{{ old('prize_value') }}">
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click.prevent="prev()">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click.prevent="next()">{{ __('loop.continue') }}</button>
                </div>
            </div>

            <div data-step="3" x-show="step === 3" x-cloak class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">3 · {{ __('loop.section_draw_rules') }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="loop-label">{{ __('loop.how_many_winners') }}</label>
                        <input type="number" min="1" max="{{ $maxWinners }}" name="winners_count" class="loop-input" value="{{ old('winners_count', 1) }}" required>
                        <p class="mt-1 text-xs text-ink-muted">{{ __('loop.raffle_winners_cap_help', ['max' => $maxWinners, 'pct' => $maxWinnersPercent, 'members' => $memberCount]) }}</p>
                    </div>
                    <div>
                        <x-sheet-select
                            name="frequency"
                            :label="__('loop.frequency')"
                            :value="old('frequency', 'once')"
                            :options="collect(['once','weekly','monthly','yearly'])->mapWithKeys(fn ($f) => [$f => __('loop.freq_'.$f)])->all()"
                        />
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.draw_date') }}</label>
                        <input type="date" name="draw_at" class="loop-input" value="{{ old('draw_at', now()->addWeek()->format('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.claim_days') }}</label>
                        <input type="number" min="1" max="30" name="claim_days" class="loop-input" value="{{ old('claim_days', $defaultClaimDays) }}" required>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click.prevent="prev()">{{ __('loop.back') }}</button>
                    <button class="loop-btn-mint flex-1">{{ __('loop.save_raffle') }}</button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
