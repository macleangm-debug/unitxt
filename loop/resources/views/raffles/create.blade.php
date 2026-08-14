<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ __('loop.create_raffle') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.create_raffle_blurb') }}</p>
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
        x-data="{
            step: {{ (int) old('_step', 1) }},
            total: 3,
            go(n) { this.step = n; window.scrollTo({ top: 0, behavior: 'smooth' }); },
            next() {
                const form = this.$refs.form;
                const fields = form.querySelectorAll('[data-step='+this.step+'] [name]');
                for (const el of fields) {
                    if (el.disabled) continue;
                    if (el.hasAttribute('required') && !String(el.value || '').trim()) {
                        el.reportValidity();
                        return;
                    }
                    if (typeof el.checkValidity === 'function' && !el.checkValidity()) {
                        el.reportValidity();
                        return;
                    }
                }
                this.go(Math.min(this.total, this.step + 1));
            }
        }"
    >
        <x-form-stepper :steps="$raffleSteps" />

        <form x-ref="form" method="POST" action="{{ route('raffles.store') }}" class="space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 sm:p-8">
            @csrf
            <input type="hidden" name="_step" :value="step">

            <div data-step="1" :class="step === 1 ? '' : 'hidden'" class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">1 · {{ __('loop.section_basics') }}</p>
                <div>
                    <label class="loop-label">{{ __('loop.raffle_name') }}</label>
                    <input name="name" class="loop-input" value="{{ old('name') }}" :required="step === 1" placeholder="{{ __('loop.raffle_name_placeholder') }}">
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.description') }}</label>
                    <textarea name="description" rows="2" class="loop-input">{{ old('description') }}</textarea>
                </div>
                <button type="button" class="loop-btn-mint w-full" @click="next()">{{ __('loop.continue') }}</button>
            </div>

            <div data-step="2" :class="step === 2 ? '' : 'hidden'" class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">2 · {{ __('loop.section_prize') }}</p>
                <p class="text-sm text-ink-muted">{{ __('loop.prize_like_offer') }}</p>
                <div>
                    <label class="loop-label">{{ __('loop.prize_name') }}</label>
                    <input name="prize_name" class="loop-input" value="{{ old('prize_name') }}" :required="step === 2" placeholder="{{ __('loop.prize_name_placeholder') }}">
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="loop-label">{{ __('loop.type') }}</label>
                        <select name="prize_type" class="loop-input">
                            @foreach (['free_item','percent_off','fixed_off','custom'] as $t)
                                <option value="{{ $t }}" @selected(old('prize_type','custom')===$t)>{{ __('loop.'.$t) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.value_hint') }}</label>
                        <input type="number" step="0.01" name="prize_value" class="loop-input" value="{{ old('prize_value') }}">
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(1)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                </div>
            </div>

            <div data-step="3" :class="step === 3 ? '' : 'hidden'" class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">3 · {{ __('loop.section_draw_rules') }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="loop-label">{{ __('loop.how_many_winners') }}</label>
                        <input type="number" min="1" max="{{ $maxWinners }}" name="winners_count" class="loop-input" value="{{ old('winners_count', 1) }}" :required="step === 3">
                        <p class="mt-1 text-xs text-ink-muted">{{ __('loop.raffle_winners_cap_help', ['max' => $maxWinners, 'pct' => $maxWinnersPercent, 'members' => $memberCount]) }}</p>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.frequency') }}</label>
                        <select name="frequency" class="loop-input">
                            @foreach (['once','weekly','monthly','yearly'] as $f)
                                <option value="{{ $f }}" @selected(old('frequency','once')===$f)>{{ __('loop.freq_'.$f) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.draw_date') }}</label>
                        <input type="date" name="draw_at" class="loop-input" value="{{ old('draw_at', now()->addWeek()->format('Y-m-d')) }}" :required="step === 3">
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.claim_days') }}</label>
                        <input type="number" min="1" max="30" name="claim_days" class="loop-input" value="{{ old('claim_days', $defaultClaimDays) }}" :required="step === 3">
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(2)">{{ __('loop.back') }}</button>
                    <button class="loop-btn-mint flex-1">{{ __('loop.save_raffle') }}</button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
