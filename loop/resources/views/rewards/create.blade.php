<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.offers') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.add_offer') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.add_offer_owner_hint') }}</p>
            </div>
            <x-settings-back :href="route('campaigns.index').'#offers'" :label="__('loop.back')" />
        </div>
    </x-slot>

    @if (! $showForm)
        <div class="mb-6 max-w-2xl">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.choose_offer_type') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.choose_offer_type_body_short') }}</p>
        </div>

        @if ($earnCampaign)
            <p class="mb-6 rounded-2xl border border-ink/10 bg-white px-4 py-3 text-sm font-medium text-ink">
                {{ __('loop.customer_gets') }}:
                {{ $earnCampaign->points_per_step }} {{ __('loop.pts') }} /
                {{ $business->currency }} {{ number_format($earnCampaign->spend_step) }}
            </p>
        @endif

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($typeStarters as $starter)
                <a href="{{ route('rewards.create', ['type' => $starter['key']]) }}"
                   class="flex min-h-[11rem] flex-col rounded-3xl border border-ink/10 bg-white p-5 transition hover:border-mint">
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-display text-lg font-semibold">{{ $starter['name'] }}</p>
                        <span class="shrink-0 rounded-lg bg-lime px-2.5 py-1 text-xs font-semibold text-ink">{{ $starter['points_cost'] }} {{ __('loop.pts') }}</span>
                    </div>
                    <p class="mt-2 flex-1 text-sm text-ink-muted">{{ $starter['description'] }}</p>
                    <p class="mt-4 text-sm font-semibold text-mint-deep">{{ __('loop.continue') }} →</p>
                </a>
            @endforeach
        </div>

        <a href="{{ route('campaigns.index') }}#offers" class="mt-8 inline-block text-sm font-semibold text-ink-muted underline">{{ __('loop.back') }}</a>
    @else
        @php
            $t = $selectedTemplate;
            $typeMeta = $selectedType;
            $defaultName = old('name', $t['name'] ?? $typeMeta['default_name'] ?? $typeMeta['name'] ?? '');
            $defaultType = old('reward_type', $t['reward_type'] ?? $typeMeta['reward_type'] ?? 'percent_off');
            $defaultPoints = old('points_cost', $t['points_cost'] ?? $typeMeta['points_cost'] ?? 100);
            $defaultValue = old('reward_value', $t['reward_value'] ?? $typeMeta['reward_value'] ?? 0);
            $defaultProduct = old('product_name', $t['product_name'] ?? $typeMeta['product_name'] ?? '');
            $defaultDesc = old('description', $t['description'] ?? $typeMeta['description'] ?? '');
            $spendPerPoint = $earnCampaign && $earnCampaign->points_per_step
                ? ($earnCampaign->spend_step / $earnCampaign->points_per_step)
                : 0;
            $biz = $business->name;
            $valueSeed = $defaultType === 'fixed_off'
                ? number_format((float) $defaultValue)
                : (string) ($defaultValue ?: ($defaultType === 'percent_off' ? '5' : '0'));
            $starterLabel = $typeMeta['name'] ?? __('loop.'.$defaultType);
            $offerSteps = [
                1 => __('loop.section_basics'),
                2 => __('loop.section_offer_reward'),
                3 => __('loop.section_offer_cost'),
                4 => __('loop.section_limits'),
            ];
        @endphp

        <div
            class="mx-auto max-w-2xl"
            x-data="{
                step: {{ (int) old('_step', 1) }},
                total: 4,
                type: @js($defaultType),
                name: @js($defaultName),
                points: {{ (int) $defaultPoints }},
                valueDisplay: @js($valueSeed),
                product: @js($defaultProduct),
                spendPerPoint: {{ (float) $spendPerPoint }},
                currency: @js($business->currency),
                businessName: @js($biz),
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
                formatValue() {
                    if (this.type !== 'fixed_off') return;
                    let raw = String(this.valueDisplay).replace(/[^\d]/g, '');
                    this.valueDisplay = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
                },
                valueNumber() {
                    return parseFloat(String(this.valueDisplay).replace(/,/g, '')) || 0;
                },
                unlockSpend() {
                    if (!this.spendPerPoint) return 0;
                    return Math.round(this.points * this.spendPerPoint);
                },
                applyIdea(label) {
                    this.name = this.businessName ? (this.businessName + ' ' + label) : label;
                }
            }"
        >
            <x-form-stepper :steps="$offerSteps" />

            <form
                x-ref="form"
                method="POST"
                action="{{ route('rewards.store') }}"
                class="space-y-6 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8"
            >
                @csrf
                <input type="hidden" name="_step" :value="step">
                <input type="hidden" name="reward_type" value="{{ $defaultType }}">
                <input type="hidden" name="reward_value" :value="type === 'free_item' || type === 'custom' ? 0 : valueNumber()">

                <div class="rounded-2xl border border-ink/10 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.start_from_template') }}</p>
                    <p class="mt-1 font-display text-xl font-semibold">{{ $starterLabel }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.edit_template_step_hint') }}</p>
                </div>

                {{-- 1 · Basics --}}
                <div data-step="1" :class="step === 1 ? '' : 'hidden'" class="space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">1 · {{ __('loop.section_basics') }}</p>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.name_your_offer') }}</h2>
                    <p class="text-sm text-ink-muted">{{ __('loop.offer_name_hint', ['business' => $biz]) }}</p>
                    <div>
                        <label class="loop-label">{{ __('loop.offer_name') }}</label>
                        <input name="name" class="loop-input font-display text-lg font-semibold" x-model="name" :required="step === 1">
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="rounded-full border border-ink/10 px-3 py-1.5 text-xs font-semibold" @click="applyIdea('5% off')">{{ $biz }} 5% off</button>
                        <button type="button" class="rounded-full border border-ink/10 px-3 py-1.5 text-xs font-semibold" @click="applyIdea('10% off')">{{ $biz }} 10% off</button>
                        <button type="button" class="rounded-full border border-ink/10 px-3 py-1.5 text-xs font-semibold" @click="applyIdea(@js(__('loop.free_item')))">{{ $biz }} {{ __('loop.free_item') }}</button>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.description') }}</label>
                        <textarea name="description" class="loop-input" rows="2">{{ $defaultDesc }}</textarea>
                    </div>
                    <button type="button" class="loop-btn-mint w-full" @click="next()">{{ __('loop.continue') }}</button>
                    <a href="{{ route('rewards.create') }}" class="block text-center text-sm text-ink-muted underline">{{ __('loop.back') }}</a>
                </div>

                {{-- 2 · Reward --}}
                <div data-step="2" :class="step === 2 ? '' : 'hidden'" class="space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">2 · {{ __('loop.section_offer_reward') }}</p>

                    @if ($defaultType === 'percent_off')
                        <h2 class="font-display text-xl font-semibold">{{ __('loop.percent_off_value') }}</h2>
                        <p class="text-sm text-ink-muted">{{ __('loop.percent_off_form_help') }}</p>
                        <input type="number" min="1" max="100" class="loop-input" x-model="valueDisplay" :required="step === 2" placeholder="5">
                        <p class="text-sm font-semibold text-ink">
                            <span x-text="valueDisplay || 0"></span>% {{ __('loop.off_every_eligible_sale') }}
                        </p>
                    @elseif ($defaultType === 'fixed_off')
                        <h2 class="font-display text-xl font-semibold">{{ __('loop.fixed_off_value') }}</h2>
                        <p class="text-sm text-ink-muted">{{ __('loop.fixed_off_form_help') }}</p>
                        <input type="text" inputmode="numeric" class="loop-input" x-model="valueDisplay" @input="formatValue()" :required="step === 2">
                        <p class="text-sm font-semibold text-ink">
                            {{ $business->currency }} <span x-text="valueDisplay || 0"></span> {{ __('loop.off_every_eligible_sale') }}
                        </p>
                    @else
                        <h2 class="font-display text-xl font-semibold">{{ __('loop.tie_to_product_optional') }}</h2>
                        <p class="text-sm text-ink-muted">{{ __('loop.tie_to_product_hint') }}</p>
                        <input name="product_name" class="loop-input" x-model="product" placeholder="{{ __('loop.tie_to_product_placeholder') }}">
                    @endif

                    <div class="flex gap-3">
                        <button type="button" class="loop-btn-ghost flex-1" @click="go(1)">{{ __('loop.back') }}</button>
                        <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                    </div>
                </div>

                {{-- 3 · Points --}}
                <div data-step="3" :class="step === 3 ? '' : 'hidden'" class="space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">3 · {{ __('loop.section_offer_cost') }}</p>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.points_to_unlock') }}</h2>
                    <p class="text-sm text-ink-muted">{{ __('loop.points_to_unlock_help') }}</p>
                    <input type="number" name="points_cost" x-model.number="points" class="loop-input" :required="step === 3" min="1">
                    <p class="text-center font-display text-xl font-bold" x-show="unlockSpend() > 0" style="{{ $spendPerPoint > 0 ? '' : 'display:none' }}">
                        {{ __('loop.customer_spend_to_unlock_prefix') }}
                        <span x-text="currency + ' ' + unlockSpend().toLocaleString()"></span>
                        {{ __('loop.customer_spend_to_unlock_suffix') }}
                    </p>
                    <div class="flex gap-3">
                        <button type="button" class="loop-btn-ghost flex-1" @click="go(2)">{{ __('loop.back') }}</button>
                        <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                    </div>
                </div>

                {{-- 4 · Limits (optional) --}}
                <div data-step="4" :class="step === 4 ? '' : 'hidden'" class="space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">4 · {{ __('loop.section_limits') }}</p>
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
                        <button type="button" class="loop-btn-ghost flex-1" @click="go(3)">{{ __('loop.back') }}</button>
                        <button class="loop-btn-mint flex-1">{{ __('loop.confirm_launch_offer') }}</button>
                    </div>
                    <button type="submit" class="w-full text-sm font-semibold text-ink-muted underline">{{ __('loop.skip_for_now') }} — {{ __('loop.confirm_launch_offer') }}</button>
                </div>
            </form>
        </div>
    @endif
</x-app-layout>
