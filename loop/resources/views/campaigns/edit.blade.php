@php
    $isEarn = in_array(old('type', $campaign->type), ['earn', 'product_push'], true);
@endphp
<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.edit') }} · {{ $campaign->displayName() }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.edit_campaign_simple_blurb') }}</p>
    </x-slot>

    <form
        method="POST"
        action="{{ route('campaigns.update', $campaign) }}"
        class="mx-auto max-w-lg space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8"
        x-data="{
            spendDisplay: @js(number_format((int) old('spend_step', $campaign->spend_step ?: 1000))),
            pointsPerStep: {{ (int) old('points_per_step', $campaign->points_per_step ?: 2) }},
            type: @js(old('type', $campaign->type)),
            currency: @js($business->currency),
            saving: false,
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
        @submit="return startSave()"
    >
        @csrf
        @method('PUT')

        <input type="hidden" name="type" value="{{ old('type', $campaign->type) }}">
        <input type="hidden" name="starts_at" value="{{ old('starts_at', $campaign->starts_at?->format('Y-m-d') ?? now()->toDateString()) }}">
        <input type="hidden" name="description" value="{{ old('description', $campaign->description) }}">

        <div>
            <label class="loop-label">{{ __('loop.campaign_name') }}</label>
            <input name="name" class="loop-input" value="{{ old('name', $campaign->name) }}" required>
        </div>

        @if ($isEarn)
            <input type="hidden" name="spend_step" :value="spendValue()">
            <div class="rounded-2xl border border-ink/10 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ __('loop.customer_gets') }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.min_spend_section_help') }}</p>
                <div class="mt-3 grid grid-cols-2 gap-3">
                    <div>
                        <label class="loop-label">{{ __('loop.min_spend_to_earn') }} ({{ $business->currency }})</label>
                        <input type="text" inputmode="numeric" class="loop-input" x-model="spendDisplay" @input="formatSpend()" required>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.points_earned') }}</label>
                        <input type="number" name="points_per_step" min="1" class="loop-input" x-model="pointsPerStep" required>
                    </div>
                </div>
                <p class="mt-3 text-center font-display text-xl font-bold">
                    <span x-text="pointsPerStep"></span> {{ __('loop.pts') }} /
                    <span x-text="spendDisplay || '0'"></span> <span x-text="currency"></span>
                </p>
            </div>

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
            <div>
                <label class="loop-label">{{ __('loop.bonus_points') }}</label>
                <input type="number" name="bonus_points" class="loop-input" value="{{ old('bonus_points', $campaign->bonus_points) }}">
            </div>
        @endif

        <p class="rounded-2xl border border-ink/10 bg-chalk/50 px-4 py-3 text-sm text-ink-muted">
            {{ __('loop.campaign_offers_untied_hint') }}
            <a href="{{ route('rewards.index') }}" class="font-semibold text-mint-deep">{{ __('loop.offers') }} →</a>
        </p>

        <label class="flex items-center gap-2 text-sm font-semibold">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $campaign->is_active)) class="rounded border-ink/20 text-mint-deep focus:ring-mint-deep">
            {{ __('loop.live') }} — {{ __('loop.pause_or_resume_hint') }}
        </label>

        <button class="loop-btn-mint w-full" :disabled="saving" :class="{ 'opacity-70': saving }">
            <span x-show="!saving">{{ __('loop.save') }}</span>
            <span x-show="saving" x-cloak>{{ __('loop.saving') }}</span>
        </button>
    </form>
</x-app-layout>
