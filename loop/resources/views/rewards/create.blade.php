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
            $defaultDesc = old('description', '');
            $descPlaceholder = match ($defaultType) {
                'percent_off' => __('loop.offer_type_percent_off_body'),
                'fixed_off' => __('loop.offer_type_fixed_off_body'),
                'free_item' => __('loop.offer_desc_free_item_placeholder'),
                default => __('loop.offer_desc_placeholder_short'),
            };
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
                3 => __('loop.save'),
            ];
        @endphp

        <div
            class="mx-auto max-w-2xl"
            x-data="loopWizard({
                step: {{ (int) request('_step', old('_step', 1)) }},
                total: 3,
                type: @js($defaultType),
                name: @js($defaultName),
                points: {{ (int) $defaultPoints }},
                valueDisplay: @js($valueSeed),
                product: @js($defaultProduct),
                spendPerPoint: {{ (float) $spendPerPoint }},
                currency: @js($business->currency),
                businessName: @js($biz),
                stock: @js(old('stock')),
                maxPerMember: @js(old('max_redemptions_per_member')),
                limitsCopy: {
                    none: @js(__('loop.limits_summary_none')),
                    stock: @js(__('loop.limits_summary_stock')),
                    maxOnce: @js(__('loop.limits_summary_max_once')),
                    maxMany: @js(__('loop.limits_summary_max_many')),
                    bothOnce: @js(__('loop.limits_summary_both_once')),
                    bothMany: @js(__('loop.limits_summary_both_many')),
                },
                formatValue() {
                    if (this.type !== 'fixed_off') return;
                    let raw = String(this.valueDisplay).replace(/[^\d]/g, '');
                    this.valueDisplay = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
                },
                valueNumber() { return parseFloat(String(this.valueDisplay).replace(/,/g, '')) || 0; },
                unlockSpend() {
                    if (! this.spendPerPoint) return 0;
                    return Math.round(this.points * this.spendPerPoint);
                },
                applyIdea(label) {
                    this.name = this.businessName ? (this.businessName + ' ' + label) : label;
                },
                limitsSummary() {
                    const stockRaw = String(this.stock ?? '').trim();
                    const maxRaw = String(this.maxPerMember ?? '').trim();
                    const stock = parseInt(stockRaw, 10);
                    const max = parseInt(maxRaw, 10);
                    const hasStock = stockRaw !== '' && Number.isFinite(stock) && stock > 0;
                    const hasMax = maxRaw !== '' && Number.isFinite(max) && max > 0;
                    const n = (v) => Number(v).toLocaleString();
                    if (! hasStock && ! hasMax) return this.limitsCopy.none;
                    if (hasStock && hasMax) {
                        return (max === 1 ? this.limitsCopy.bothOnce : this.limitsCopy.bothMany)
                            .replaceAll(':stock', n(stock))
                            .replaceAll(':max', n(max));
                    }
                    if (hasStock) return this.limitsCopy.stock.replaceAll(':stock', n(stock));
                    return (max === 1 ? this.limitsCopy.maxOnce : this.limitsCopy.maxMany)
                        .replaceAll(':max', n(max));
                },
            })"
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
                <div data-step="1" x-show="step === 1" class="space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">1 · {{ __('loop.section_basics') }}</p>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.name_your_offer') }}</h2>
                    <p class="text-sm text-ink-muted">
                        @if ($defaultType === 'free_item')
                            {{ __('loop.offer_name_hint_free', ['business' => $biz]) }}
                        @elseif ($defaultType === 'fixed_off')
                            {{ __('loop.offer_name_hint_fixed', ['business' => $biz]) }}
                        @else
                            {{ __('loop.offer_name_hint', ['business' => $biz]) }}
                        @endif
                    </p>
                    <div>
                        <label class="loop-label">{{ __('loop.offer_name') }}</label>
                        <input name="name" class="loop-input font-display text-lg font-semibold" x-model="name" :required="step === 1">
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($defaultType === 'percent_off')
                            <button type="button" class="rounded-full border border-ink/10 px-3 py-1.5 text-xs font-semibold" @click="applyIdea('5% off')">{{ $biz }} 5% off</button>
                            <button type="button" class="rounded-full border border-ink/10 px-3 py-1.5 text-xs font-semibold" @click="applyIdea('10% off')">{{ $biz }} 10% off</button>
                        @elseif ($defaultType === 'fixed_off')
                            <button type="button" class="rounded-full border border-ink/10 px-3 py-1.5 text-xs font-semibold" @click="applyIdea(@js(__('loop.offer_chip_fixed_small')))">{{ $biz }} {{ __('loop.offer_chip_fixed_small') }}</button>
                            <button type="button" class="rounded-full border border-ink/10 px-3 py-1.5 text-xs font-semibold" @click="applyIdea(@js(__('loop.offer_chip_fixed_large')))">{{ $biz }} {{ __('loop.offer_chip_fixed_large') }}</button>
                        @else
                            <button type="button" class="rounded-full border border-ink/10 px-3 py-1.5 text-xs font-semibold" @click="applyIdea(@js(__('loop.offer_chip_free_drink')))">{{ $biz }} {{ __('loop.offer_chip_free_drink') }}</button>
                            <button type="button" class="rounded-full border border-ink/10 px-3 py-1.5 text-xs font-semibold" @click="applyIdea(@js(__('loop.offer_chip_free_item')))">{{ $biz }} {{ __('loop.offer_chip_free_item') }}</button>
                            <button type="button" class="rounded-full border border-ink/10 px-3 py-1.5 text-xs font-semibold" @click="applyIdea(@js(__('loop.offer_chip_free_dessert')))">{{ $biz }} {{ __('loop.offer_chip_free_dessert') }}</button>
                        @endif
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.description') }}</label>
                        <textarea name="description" class="loop-input" rows="2" placeholder="{{ $descPlaceholder }}">{{ $defaultDesc }}</textarea>
                    </div>
                    <button type="button" class="loop-btn-mint w-full" @click.prevent="next()">{{ __('loop.continue') }}</button>
                    <a href="{{ route('rewards.create') }}" class="block text-center text-sm text-ink-muted underline">{{ __('loop.back') }}</a>
                </div>

                {{-- 2 · Reward + points cost --}}
                <div data-step="2" x-show="step === 2" x-cloak class="space-y-4">
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
                        <h2 class="font-display text-xl font-semibold">{{ __('loop.free_item_product_title') }}</h2>
                        <p class="text-sm text-ink-muted">{{ __('loop.free_item_product_hint') }}</p>
                        <input name="product_name" class="loop-input" x-model="product" placeholder="{{ __('loop.free_item_product_placeholder') }}">
                    @endif

                    <div class="rounded-2xl border border-ink/8 bg-chalk/40 p-4">
                        <label class="loop-label">{{ __('loop.points_to_unlock') }}</label>
                        <p class="mb-2 text-sm text-ink-muted">{{ __('loop.points_to_unlock_help') }}</p>
                        <input type="number" name="points_cost" x-model.number="points" class="loop-input" :required="step === 2" min="1">
                        <p class="mt-3 text-center font-display text-lg font-bold" x-show="unlockSpend() > 0" style="{{ $spendPerPoint > 0 ? '' : 'display:none' }}">
                            {{ __('loop.customer_spend_to_unlock_prefix') }}
                            <span x-text="currency + ' ' + unlockSpend().toLocaleString()"></span>
                            {{ __('loop.customer_spend_to_unlock_suffix') }}
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" class="loop-btn-ghost flex-1" @click.prevent="go(1)">{{ __('loop.back') }}</button>
                        <button type="button" class="loop-btn-mint flex-1" @click.prevent="next()">{{ __('loop.continue') }}</button>
                    </div>
                </div>

                {{-- 3 · Limits + launch --}}
                <div data-step="3" x-show="step === 3" x-cloak class="space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">3 · {{ __('loop.save') }}</p>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.section_limits') }}</h2>
                    <p class="text-sm text-ink-muted">{{ __('loop.limits_optional_hint') }}</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="loop-label">{{ __('loop.stock_optional') }}</label>
                            <input type="number" name="stock" class="loop-input" x-model="stock" min="1" placeholder="∞">
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.stock_help') }}</p>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.max_per_member') }}</label>
                            <input type="number" name="max_redemptions_per_member" class="loop-input" x-model="maxPerMember" min="1" placeholder="∞">
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.max_per_member_help') }}</p>
                        </div>
                    </div>
                    <p class="rounded-2xl border border-mint/25 bg-mint-soft/40 px-4 py-3 text-center font-display text-base font-semibold text-ink" x-text="limitsSummary()"></p>

                    <div class="rounded-2xl border border-ink/8 bg-chalk/40 p-4" x-data="{ scheduleMode: 'evergreen' }">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.offer_schedule') }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.offer_schedule_help') }}</p>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <label class="rounded-xl border border-ink/10 bg-white px-3 py-3 text-sm has-[:checked]:border-mint-deep has-[:checked]:bg-mint-soft/40">
                                <input type="radio" name="schedule_mode" value="evergreen" class="sr-only" x-model="scheduleMode" checked>
                                <span class="font-semibold">{{ __('loop.offer_evergreen') }}</span>
                            </label>
                            <label class="rounded-xl border border-ink/10 bg-white px-3 py-3 text-sm has-[:checked]:border-mint-deep has-[:checked]:bg-mint-soft/40">
                                <input type="radio" name="schedule_mode" value="scheduled" class="sr-only" x-model="scheduleMode">
                                <span class="font-semibold">{{ __('loop.offer_scheduled') }}</span>
                            </label>
                        </div>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2" x-show="scheduleMode === 'scheduled'" x-cloak>
                            <div>
                                <label class="loop-label">{{ __('loop.starts_at') }}</label>
                                <input type="date" name="starts_at" class="loop-input" value="{{ old('starts_at') }}">
                            </div>
                            <div>
                                <label class="loop-label">{{ __('loop.ends_at') }}</label>
                                <input type="date" name="ends_at" class="loop-input" value="{{ old('ends_at') }}">
                            </div>
                        </div>
                    </div>

                    <p class="rounded-2xl border border-ink/10 bg-chalk/50 px-4 py-3 text-sm text-ink-muted">
                        {{ __('loop.default_offer_owner_hint') }}
                    </p>
                    <div class="flex gap-3">
                        <button type="button" class="loop-btn-ghost flex-1" @click.prevent="go(2)">{{ __('loop.back') }}</button>
                        <button class="loop-btn-mint flex-1">{{ __('loop.confirm_launch_offer') }}</button>
                    </div>
                </div>
            </form>
        </div>
    @endif
</x-app-layout>
