@php
    $selectedShops = old('shop_ids', []);
    $t = $template ?? null;
    $defaultType = old('type', $t['type'] ?? 'earn');
    $allowedTypes = ['earn', 'product_push', 'birthday', 'welcome', 'streak'];
    if (! in_array($defaultType, $allowedTypes, true)) {
        $defaultType = 'earn';
    }
    $isEarnLike = in_array($defaultType, ['earn', 'product_push'], true);
    $isBonus = in_array($defaultType, ['birthday', 'welcome', 'streak'], true);
    $fromTemplate = (bool) $t;
    $createSteps = [
        1 => __('loop.section_basics'),
        2 => $isBonus ? __('loop.section_bonus') : __('loop.customer_gets'),
        3 => __('loop.save'),
    ];
    $spendPlaceholder = number_format((int) ($t['spend_step'] ?? 1000));
    $pointsPlaceholder = (string) ((int) ($t['points_per_step'] ?? 2));
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="font-display text-2xl font-semibold sm:text-3xl">{{ __('loop.new_campaign') }}</h1>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.campaign_create_blurb') }}</p>
            </div>
            <x-settings-back :href="route('campaigns.index')" :label="__('loop.back')" />
        </div>
    </x-slot>

    @if ($picking)
        <div class="mb-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.pick_campaign_template') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.campaign_pick_hint') }}</p>
        </div>

        @forelse ($groupedTemplates as $intention => $group)
            <section class="mb-8">
                <h2 class="font-display text-lg font-semibold">{{ $group['label'] }}</h2>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    @foreach ($group['templates'] as $key => $item)
                        <a href="{{ route('campaigns.create', ['template' => $key]) }}" class="flex min-h-[7.5rem] flex-col rounded-3xl border border-ink/10 bg-white p-5 transition hover:-translate-y-0.5 hover:border-mint hover:bg-mint-soft/30">
                            <p class="font-display text-xl font-semibold leading-snug">{{ $item['name'] }}</p>
                            <p class="mt-2 flex-1 text-base leading-snug text-ink-muted">{{ $item['description'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="loop-panel mb-6 p-6 text-sm text-ink-muted">{{ __('loop.all_templates_used') }}</div>
        @endforelse

        <a href="{{ route('campaigns.create', ['own' => 1]) }}" class="flex min-h-[7.5rem] max-w-md flex-col rounded-3xl border border-dashed border-ink/20 bg-chalk/50 p-5 transition hover:border-mint">
            <p class="font-display text-xl font-semibold">{{ __('loop.create_own') }}</p>
            <p class="mt-2 text-base text-ink-muted">{{ __('loop.create_own_campaign_body') }}</p>
        </a>
    @else
        <div
            class="mx-auto max-w-2xl"
            x-data="loopWizard({
                step: {{ (int) request('_step', old('_step', $errors->has('spend_step') || $errors->has('points_per_step') ? 2 : 1)) }},
                total: 3,
                campaignType: @js($defaultType),
                spendDisplay: @js(old('spend_step') ? number_format((int) old('spend_step')) : ''),
                pointsPerStep: @js(old('points_per_step') !== null && old('points_per_step') !== '' ? (string) old('points_per_step') : ''),
                currency: @js($business->currency),
                earnRulesMessage: @js(__('loop.campaign_spend_points_required')),
                formatSpend() {
                    let raw = String(this.spendDisplay).replace(/[^\d]/g, '');
                    this.spendDisplay = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
                },
                spendValue() { return parseInt(String(this.spendDisplay).replace(/,/g, ''), 10) || 0; },
            })"
        >
            <x-form-stepper :steps="$createSteps" />

            <form
                x-ref="form"
                method="POST"
                action="{{ route('campaigns.store') }}"
                class="space-y-6 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8"
                @submit="return startSave()"
            >
                @csrf
                <input type="hidden" name="_step" :value="step">
                @if ($isEarnLike)
                    <input type="hidden" name="spend_step" :value="spendValue() >= 1 ? spendValue() : ''">
                    <input type="hidden" name="points_per_step" :value="(parseInt(String(pointsPerStep || '').replace(/[^\d]/g, ''), 10) || 0) >= 1 ? pointsPerStep : ''">
                @endif
                @if ($errors->has('spend_step') || $errors->has('points_per_step'))
                    <div class="rounded-2xl border border-coral/30 bg-coral/10 px-4 py-3 text-sm text-coral">
                        {{ $errors->first('spend_step') ?: $errors->first('points_per_step') }}
                    </div>
                @endif
                @if ($templateKey)
                    <input type="hidden" name="template_key" value="{{ $templateKey }}">
                @endif

                @if ($t)
                    <div class="rounded-2xl border border-ink/10 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.start_from_template') }}</p>
                        <p class="mt-1 font-display text-xl font-semibold">{{ $t['name'] }}</p>
                    </div>
                @endif

                {{-- 1 · Basics --}}
                <div data-step="1" x-show="step === 1" class="space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">1 · {{ __('loop.section_basics') }}</p>
                    <div>
                        <label class="loop-label">{{ __('loop.campaign_name') }}</label>
                        <input name="name" class="loop-input" value="{{ old('name') }}" placeholder="{{ $t['name'] ?? __('loop.campaign_name_placeholder') }}" data-required="1">
                    </div>
                    @if ($fromTemplate)
                        <input type="hidden" name="type" value="{{ $defaultType }}">
                        <p class="rounded-xl bg-chalk/60 px-3 py-2 text-sm text-ink-muted">
                            {{ __('loop.type') }}:
                            <span class="font-semibold text-ink">{{ __('loop.type_'.$defaultType) }}</span>
                        </p>
                    @else
                        <div>
                            <label class="loop-label">{{ __('loop.type') }}</label>
                            <select name="type" class="loop-input" x-model="campaignType" data-required="1">
                                <option value="earn" @selected($defaultType === 'earn')>{{ __('loop.type_earn') }}</option>
                                <option value="product_push" @selected($defaultType === 'product_push')>{{ __('loop.type_product_push') }}</option>
                            </select>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.bonus_campaigns_use_templates') }}</p>
                        </div>
                    @endif
                    <div>
                        <label class="loop-label">{{ __('loop.description') }}</label>
                        <textarea name="description" rows="2" class="loop-input" placeholder="{{ __('loop.campaign_desc_placeholder') }}">{{ old('description') }}</textarea>
                    </div>
                    <button type="button" class="loop-btn-mint w-full" @click.prevent="next()">{{ __('loop.continue') }}</button>
                    <a href="{{ route('campaigns.create') }}" class="block text-center text-sm text-ink-muted underline">{{ __('loop.back') }}</a>
                </div>

                {{-- 2 · Rules --}}
                <div data-step="2" x-show="step === 2" x-cloak class="space-y-4">
                    @if ($isEarnLike)
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">2 · {{ __('loop.customer_gets') }}</p>
                        <p class="text-sm text-ink-muted">{{ __('loop.min_spend_section_help') }}</p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="loop-label">{{ __('loop.min_spend_to_earn') }} ({{ $business->currency }})</label>
                                <input type="text" inputmode="numeric" class="loop-input" x-model="spendDisplay" @input="formatSpend()" data-required="1" placeholder="{{ $spendPlaceholder }}">
                            </div>
                            <div>
                                <label class="loop-label">{{ __('loop.points_earned') }}</label>
                                <input type="number" class="loop-input" x-model="pointsPerStep" data-required="1" min="1" placeholder="{{ $pointsPlaceholder }}">
                            </div>
                        </div>
                        <p class="text-center font-display text-xl font-bold" x-show="earnRulesOk()" x-cloak>
                            <span x-text="pointsPerStep"></span> {{ __('loop.pts') }} /
                            <span x-text="spendDisplay"></span> <span x-text="currency"></span>
                        </p>
                        <p class="text-center text-sm font-semibold text-ink-muted" x-show="!earnRulesOk()">
                            {{ __('loop.campaign_spend_points_required') }}
                        </p>

                        @if ($defaultType === 'product_push' || ! $fromTemplate)
                            <div
                                class="space-y-3 rounded-2xl border border-violet/20 bg-violet-soft/40 p-4"
                                @if (! $fromTemplate)
                                    x-show="campaignType === 'product_push'"
                                    x-cloak
                                @endif
                            >
                                <p class="text-sm font-semibold text-ink">{{ __('loop.featured_product_section') }}</p>
                                <p class="text-sm text-ink-muted">{{ __('loop.featured_product_how_it_works') }}</p>
                                <div>
                                    <label class="loop-label">{{ __('loop.featured_product_name') }}</label>
                                    <input type="text" name="featured_product_name" class="loop-input" value="{{ old('featured_product_name') }}" placeholder="{{ __('loop.featured_product_placeholder') }}" @if($defaultType === 'product_push') data-required="1" @endif x-bind:data-required="campaignType === 'product_push' ? '1' : null">
                                </div>
                                <div>
                                    <label class="loop-label">{{ __('loop.featured_bonus_points') }}</label>
                                    <input type="number" name="bonus_points" min="0" class="loop-input" value="{{ old('bonus_points') }}" placeholder="{{ (string) ((int) ($t['bonus_points'] ?? 10)) }}">
                                </div>
                            </div>
                        @else
                            <input type="hidden" name="bonus_points" value="{{ old('bonus_points', 0) }}">
                        @endif
                    @else
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">2 · {{ __('loop.section_bonus') }}</p>
                        <p class="text-sm text-ink-muted">{{ __('loop.bonus_campaign_step_help') }}</p>
                        <div>
                            <label class="loop-label">{{ __('loop.bonus_points') }}</label>
                            <input type="number" name="bonus_points" class="loop-input" value="{{ old('bonus_points') }}" placeholder="{{ (string) ((int) ($t['bonus_points'] ?? 20)) }}" data-required="1" min="1">
                        </div>
                        @if ($defaultType === 'streak')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="loop-label">{{ __('loop.streak_target') }}</label>
                                    <input type="number" name="streak_target" class="loop-input" value="{{ old('streak_target') }}" placeholder="{{ (string) ((int) ($t['streak_target'] ?? 3)) }}" data-required="1" min="2">
                                </div>
                                <div>
                                    <label class="loop-label">{{ __('loop.streak_period') }}</label>
                                    <select name="streak_period" class="loop-input">
                                        <option value="week" @selected(old('streak_period', $t['streak_period'] ?? 'week') === 'week')>{{ __('loop.streak_period_week') }}</option>
                                        <option value="month" @selected(old('streak_period', $t['streak_period'] ?? 'week') === 'month')>{{ __('loop.streak_period_month') }}</option>
                                    </select>
                                </div>
                            </div>
                        @endif
                    @endif

                    <div class="flex gap-3">
                        <button type="button" class="loop-btn-ghost flex-1" @click.prevent="prev()">{{ __('loop.back') }}</button>
                        <button type="button" class="loop-btn-mint flex-1" @click.prevent="next()">{{ __('loop.continue') }}</button>
                    </div>
                </div>

                {{-- 3 · Save --}}
                <div data-step="3" x-show="step === 3" x-cloak class="space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">3 · {{ __('loop.save') }}</p>
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
                        <button type="button" class="loop-btn-ghost flex-1" @click.prevent="prev()">{{ __('loop.back') }}</button>
                        <button type="submit" class="loop-btn-mint flex-1">{{ __('loop.launch_campaign') }}</button>
                    </div>
                </div>
            </form>
        </div>
    @endif
</x-app-layout>
