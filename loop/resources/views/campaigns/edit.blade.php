@php
    $isEarn = in_array(old('type', $campaign->type), ['earn', 'product_push'], true);
    $editSteps = $isEarn
        ? [
            1 => __('loop.section_basics'),
            2 => __('loop.customer_gets'),
            3 => __('loop.save'),
        ]
        : [
            1 => __('loop.section_basics'),
            2 => __('loop.section_bonus'),
            3 => __('loop.save'),
        ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ __('loop.edit') }} · {{ $campaign->displayName() }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.edit_campaign_simple_blurb') }}</p>
            </div>
            <x-settings-back :href="route('campaigns.index')" :label="__('loop.back')" />
        </div>
    </x-slot>

    <div
        class="mx-auto max-w-lg"
        x-data="{
            step: {{ (int) old('_step', 1) }},
            total: 3,
            spendDisplay: @js(number_format((int) old('spend_step', $campaign->spend_step ?: 1000))),
            pointsPerStep: {{ (int) old('points_per_step', $campaign->points_per_step ?: 2) }},
            currency: @js($business->currency),
            saving: false,
            go(n) { this.step = n; window.scrollTo({ top: 0, behavior: 'smooth' }); },
            next() {
                const form = this.$refs.form;
                const fields = form.querySelectorAll('[data-step=\"'+this.step+'\'] [name]');
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
            },
            formatSpend() {
                let raw = String(this.spendDisplay).replace(/[^\d]/g, '');
                this.spendDisplay = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
            },
            spendValue() { return parseInt(String(this.spendDisplay).replace(/,/g, ''), 10) || 0; },
            startSave() {
                if (this.saving) return false;
                this.saving = true;
                return true;
            }
        }"
    >
        <x-form-stepper :steps="$editSteps" />

        <form
            x-ref="form"
            method="POST"
            action="{{ route('campaigns.update', $campaign) }}"
            class="space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8"
            @submit="return startSave()"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="_step" :value="step">
            <input type="hidden" name="type" value="{{ old('type', $campaign->type) }}">
            <input type="hidden" name="starts_at" value="{{ old('starts_at', $campaign->starts_at?->format('Y-m-d') ?? now()->toDateString()) }}">
            <input type="hidden" name="description" value="{{ old('description', $campaign->description) }}">

            <div data-step="1" class="space-y-4" :class="step === 1 ? '' : 'hidden'">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">1 · {{ __('loop.section_basics') }}</p>
                <div>
                    <label class="loop-label">{{ __('loop.campaign_name') }}</label>
                    <input name="name" class="loop-input" value="{{ old('name', $campaign->name) }}" :required="step === 1">
                </div>
                <p class="rounded-xl bg-chalk/60 px-3 py-2 text-sm text-ink-muted">
                    {{ __('loop.type') }}:
                    <span class="font-semibold text-ink">{{ __('loop.type_'.$campaign->type) }}</span>
                </p>
                <button type="button" class="loop-btn-mint w-full" @click="next()">{{ __('loop.continue') }}</button>
            </div>

            <div data-step="2" class="space-y-4" :class="step === 2 ? '' : 'hidden'">
                @if ($isEarn)
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">2 · {{ __('loop.customer_gets') }}</p>
                    <input type="hidden" name="spend_step" :value="spendValue()">
                    <p class="text-sm text-ink-muted">{{ __('loop.min_spend_section_help') }}</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="loop-label">{{ __('loop.min_spend_to_earn') }} ({{ $business->currency }})</label>
                            <input type="text" inputmode="numeric" class="loop-input" x-model="spendDisplay" @input="formatSpend()" :required="step === 2">
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.points_earned') }}</label>
                            <input type="number" name="points_per_step" min="1" class="loop-input" x-model="pointsPerStep" :required="step === 2">
                        </div>
                    </div>
                    <p class="text-center font-display text-xl font-bold">
                        <span x-text="pointsPerStep"></span> {{ __('loop.pts') }} /
                        <span x-text="spendDisplay || '0'"></span> <span x-text="currency"></span>
                    </p>

                    @if ($campaign->type === 'product_push')
                        <div class="space-y-3 rounded-2xl border border-violet/20 bg-violet-soft/40 p-4">
                            <div>
                                <label class="loop-label">{{ __('loop.featured_product_name') }}</label>
                                <input type="text" name="featured_product_name" class="loop-input" value="{{ old('featured_product_name', $campaign->featured_product_name) }}">
                            </div>
                            <div>
                                <label class="loop-label">{{ __('loop.featured_bonus_points') }}</label>
                                <input type="number" name="bonus_points" min="0" class="loop-input" value="{{ old('bonus_points', $campaign->bonus_points) }}">
                            </div>
                        </div>
                    @else
                        <input type="hidden" name="bonus_points" value="{{ old('bonus_points', $campaign->bonus_points ?: 0) }}">
                    @endif
                @else
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">2 · {{ __('loop.section_bonus') }}</p>
                    <div>
                        <label class="loop-label">{{ __('loop.bonus_points') }}</label>
                        <input type="number" name="bonus_points" class="loop-input" value="{{ old('bonus_points', $campaign->bonus_points) }}" :required="step === 2">
                    </div>
                @endif

                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(1)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                </div>
            </div>

            <div data-step="3" class="space-y-4" :class="step === 3 ? '' : 'hidden'">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">3 · {{ __('loop.save') }}</p>
                <p class="rounded-2xl border border-ink/10 bg-chalk/50 px-4 py-3 text-sm text-ink-muted">
                    {{ __('loop.campaign_offers_untied_hint') }}
                    <a href="{{ route('rewards.index') }}" class="font-semibold text-mint-deep">{{ __('loop.offers') }} →</a>
                </p>
                <label class="flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $campaign->is_active)) class="rounded border-ink/20 text-mint-deep focus:ring-mint-deep">
                    {{ __('loop.live') }} — {{ __('loop.pause_or_resume_hint') }}
                </label>
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(2)">{{ __('loop.back') }}</button>
                    <button class="loop-btn-mint flex-1" :disabled="saving" :class="{ 'opacity-70': saving }">
                        <span x-show="!saving">{{ __('loop.save') }}</span>
                        <span x-show="saving" :class="saving ? '' : 'hidden'">{{ __('loop.saving') }}</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
