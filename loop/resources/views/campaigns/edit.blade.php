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
    $oldSpend = old('spend_step', $campaign->spend_step);
    $spendDisplayInit = ($oldSpend !== null && $oldSpend !== '' && (int) $oldSpend > 0)
        ? number_format((int) $oldSpend)
        : '';
    $oldPoints = old('points_per_step', $campaign->points_per_step);
    $pointsInit = ($oldPoints !== null && $oldPoints !== '') ? (int) $oldPoints : 'null';
    $errorStep = 1;
    if ($errors->hasAny(['spend_step', 'points_per_step', 'featured_product_name', 'bonus_points'])) {
        $errorStep = 2;
    }
    $initialStep = $errors->any() ? $errorStep : (int) old('_step', 1);
@endphp
<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.edit') }} · {{ $campaign->displayName() }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.edit_campaign_simple_blurb') }}</p>
    </x-slot>

    <div
        class="mx-auto max-w-lg"
        x-data="{
            step: {{ (int) $initialStep }},
            total: 3,
            type: @js(old('type', $campaign->type)),
            spendDisplay: @js($spendDisplayInit),
            pointsPerStep: {{ $pointsInit }},
            bonusPoints: {{ (int) old('bonus_points', $campaign->bonus_points ?: 0) }},
            currency: @js($business->currency),
            saving: false,
            spendRequired: @js(__('loop.campaign_spend_required')),
            pointsRequired: @js(__('loop.campaign_points_required')),
            go(n) { this.step = n; window.scrollTo({ top: 0, behavior: 'smooth' }); },
            fail(stepNum, el, message) {
                if (this.step !== stepNum) this.go(stepNum);
                this.$nextTick(() => {
                    if (!el) return;
                    if (message) {
                        el.setCustomValidity(message);
                        el.reportValidity();
                        el.setCustomValidity('');
                    } else {
                        el.reportValidity();
                    }
                    el.focus();
                });
                return false;
            },
            validateStep(stepNum) {
                const form = this.$refs.form;
                if (!form) return false;
                const root = form.querySelector('[data-step=\"'+stepNum+'\"]');
                if (!root) return true;
                if (stepNum === 1) {
                    const name = root.querySelector('[name=\"name\"]');
                    if (!name || !String(name.value || '').trim()) return this.fail(1, name);
                }
                if (stepNum === 2) {
                    if (['earn', 'product_push'].includes(this.type)) {
                        const spendEl = root.querySelector('[data-spend-input]');
                        if (this.spendValue() < 1) return this.fail(2, spendEl, this.spendRequired);
                        const ptsEl = root.querySelector('[name=\"points_per_step\"]');
                        const pts = parseInt(this.pointsPerStep, 10);
                        if (!pts || pts < 1) return this.fail(2, ptsEl, this.pointsRequired);
                        if (this.type === 'product_push') {
                            const product = root.querySelector('[name=\"featured_product_name\"]');
                            if (!product || !String(product.value || '').trim()) return this.fail(2, product);
                            const bonusEl = root.querySelector('[data-bonus-input]');
                            const bonus = parseInt(this.bonusPoints, 10);
                            if (!bonus || bonus < 1) return this.fail(2, bonusEl);
                        }
                    } else {
                        const bonus = root.querySelector('[name=\"bonus_points\"]');
                        if (!bonus || parseInt(bonus.value, 10) < 1) return this.fail(2, bonus);
                    }
                }
                return true;
            },
            next() {
                if (!this.validateStep(this.step)) return;
                this.go(Math.min(this.total, this.step + 1));
            },
            goTo(n) {
                n = parseInt(n, 10);
                if (n <= this.step) { this.go(n); return; }
                while (this.step < n) {
                    const before = this.step;
                    this.next();
                    if (this.step === before) return;
                }
            },
            formatSpend() {
                let raw = String(this.spendDisplay).replace(/[^\d]/g, '');
                this.spendDisplay = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
            },
            spendValue() { return parseInt(String(this.spendDisplay).replace(/,/g, ''), 10) || 0; },
            startSave(event) {
                if (this.saving) { event.preventDefault(); return; }
                if (this.step !== this.total) {
                    event.preventDefault();
                    this.next();
                    return;
                }
                for (let s = 1; s <= this.total; s++) {
                    if (!this.validateStep(s)) {
                        event.preventDefault();
                        return;
                    }
                }
                this.saving = true;
            }
        }"
    >
        <x-form-stepper :steps="$editSteps" />

        <form
            x-ref="form"
            method="POST"
            action="{{ route('campaigns.update', $campaign) }}"
            class="space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8"
            @submit="startSave($event)"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="_step" :value="step">
            @if ($errors->any())
                <div class="rounded-xl border border-coral/30 bg-coral/10 px-4 py-3 text-sm text-ink" role="alert">
                    {{ $errors->first() ?: __('loop.campaign_form_errors') }}
                </div>
            @endif
            <input type="hidden" name="type" value="{{ old('type', $campaign->type) }}">
            <input type="hidden" name="starts_at" value="{{ old('starts_at', $campaign->starts_at?->format('Y-m-d') ?? now()->toDateString()) }}">
            <input type="hidden" name="description" value="{{ old('description', $campaign->description) }}">

            <div data-step="1" x-show="step === 1" class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">1 · {{ __('loop.section_basics') }}</p>
                <div>
                    <label class="loop-label">{{ __('loop.campaign_name') }}</label>
                    <input name="name" class="loop-input" value="{{ old('name', $campaign->name) }}" :required="step === 1">
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <p class="rounded-xl bg-chalk/60 px-3 py-2 text-sm text-ink-muted">
                    {{ __('loop.type') }}:
                    <span class="font-semibold text-ink">{{ __('loop.type_'.$campaign->type) }}</span>
                </p>
                <button type="button" class="loop-btn-mint w-full" @click="next()">{{ __('loop.continue') }}</button>
            </div>

            <div data-step="2" x-show="step === 2" x-cloak class="space-y-4">
                @if ($isEarn)
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">2 · {{ __('loop.customer_gets') }}</p>
                    <input type="hidden" name="spend_step" :value="spendValue()">
                    <p class="text-sm text-ink-muted">{{ __('loop.min_spend_section_help') }}</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="loop-label">{{ __('loop.min_spend_to_earn') }} ({{ $business->currency }})</label>
                            <input type="text" inputmode="numeric" class="loop-input" x-model="spendDisplay" @input="formatSpend()" data-spend-input :required="step === 2">
                            <x-input-error :messages="$errors->get('spend_step')" class="mt-1" />
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.points_earned') }}</label>
                            <input type="number" name="points_per_step" min="1" class="loop-input" x-model="pointsPerStep" :required="step === 2">
                            <x-input-error :messages="$errors->get('points_per_step')" class="mt-1" />
                        </div>
                    </div>
                    <p class="text-center font-display text-xl font-bold">
                        <span x-text="pointsPerStep || '—'"></span> {{ __('loop.pts') }} /
                        <span x-text="spendDisplay || '0'"></span> <span x-text="currency"></span>
                    </p>

                    @if ($campaign->type === 'product_push')
                        <div class="space-y-3 rounded-2xl border border-violet/20 bg-violet-soft/40 p-4">
                            <div>
                                <label class="loop-label">{{ __('loop.featured_product_name') }}</label>
                                <input type="text" name="featured_product_name" class="loop-input" value="{{ old('featured_product_name', $campaign->featured_product_name) }}" :required="step === 2">
                                <x-input-error :messages="$errors->get('featured_product_name')" class="mt-1" />
                            </div>
                            <div>
                                <label class="loop-label">{{ __('loop.featured_bonus_points') }}</label>
                                <input type="number" name="bonus_points" min="1" class="loop-input" x-model="bonusPoints" data-bonus-input :required="step === 2">
                                <x-input-error :messages="$errors->get('bonus_points')" class="mt-1" />
                            </div>
                        </div>
                    @else
                        <input type="hidden" name="bonus_points" value="{{ old('bonus_points', $campaign->bonus_points ?: 0) }}">
                    @endif
                @else
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">2 · {{ __('loop.section_bonus') }}</p>
                    <div>
                        <label class="loop-label">{{ __('loop.bonus_points') }}</label>
                        <input type="number" name="bonus_points" min="1" class="loop-input" value="{{ old('bonus_points', $campaign->bonus_points) }}" :required="step === 2">
                        <x-input-error :messages="$errors->get('bonus_points')" class="mt-1" />
                    </div>
                @endif

                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(1)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                </div>
            </div>

            <div data-step="3" x-show="step === 3" x-cloak class="space-y-4">
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
                        <span x-show="saving" x-cloak>{{ __('loop.saving') }}</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
