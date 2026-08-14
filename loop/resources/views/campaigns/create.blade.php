@php
    $selectedShops = old('shop_ids', []);
    $t = $template ?? null;
    $defaultType = old('type', $t['type'] ?? 'earn');
    if (! in_array($defaultType, ['earn', 'product_push'], true)) {
        $defaultType = 'earn';
    }
    $fromTemplate = (bool) $t;
    $createSteps = [
        1 => __('loop.section_basics'),
        2 => __('loop.customer_gets'),
        3 => __('loop.save'),
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ __('loop.new_campaign') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.campaign_create_blurb') }}</p>
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
            @if ($intention === 'retention')
                @continue
            @endif
            <section class="mb-8">
                <h2 class="font-display text-lg font-semibold">{{ $group['label'] }}</h2>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    @foreach ($group['templates'] as $key => $item)
                        <a href="{{ route('campaigns.create', ['template' => $key]) }}" class="flex min-h-[9.5rem] flex-col rounded-3xl border border-ink/10 bg-white/90 p-5 transition hover:-translate-y-0.5 hover:border-mint hover:bg-mint-soft/30">
                            <p class="font-display text-lg font-semibold">{{ $item['name'] }}</p>
                            <p class="mt-2 flex-1 text-sm text-ink-muted">{{ $item['description'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="loop-panel mb-6 p-6 text-sm text-ink-muted">{{ __('loop.all_templates_used') }}</div>
        @endforelse

        <a href="{{ route('campaigns.create', ['own' => 1]) }}" class="flex min-h-[9.5rem] max-w-md flex-col rounded-3xl border border-dashed border-ink/20 bg-chalk/50 p-5 transition hover:border-mint">
            <p class="font-display text-lg font-semibold">{{ __('loop.create_own') }}</p>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.create_own_campaign_body') }}</p>
        </a>
    @else
        <div
            class="mx-auto max-w-2xl"
            x-data="{
                step: {{ (int) old('_step', 1) }},
                total: 3,
                enableWelcome: {{ old('enable_welcome') ? 'true' : 'false' }},
                enableBirthday: {{ old('enable_birthday') ? 'true' : 'false' }},
                enableStreak: {{ old('enable_streak') ? 'true' : 'false' }},
                spendDisplay: @js(number_format((int) old('spend_step', $t['spend_step'] ?? 1000))),
                pointsPerStep: {{ (int) old('points_per_step', $t['points_per_step'] ?? 2) }},
                currency: @js($business->currency),
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
                },
                formatSpend() {
                    let raw = String(this.spendDisplay).replace(/[^\d]/g, '');
                    this.spendDisplay = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
                },
                spendValue() { return parseInt(String(this.spendDisplay).replace(/,/g, ''), 10) || 0; }
            }"
        >
            <x-form-stepper :steps="$createSteps" />

            <form
                x-ref="form"
                method="POST"
                action="{{ route('campaigns.store') }}"
                class="space-y-6 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8"
            >
                @csrf
                <input type="hidden" name="_step" :value="step">
                @if ($templateKey)
                    <input type="hidden" name="template_key" value="{{ $templateKey }}">
                @endif

                @if ($t)
                    <div class="rounded-2xl border border-ink/10 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.start_from_template') }}</p>
                        <p class="mt-1 font-display text-xl font-semibold">{{ $t['name'] }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.edit_template_step_hint') }}</p>
                    </div>
                @endif

                {{-- 1 · Basics --}}
                <div data-step="1" :class="step === 1 ? '' : 'hidden'" class="space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">1 · {{ __('loop.section_basics') }}</p>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.campaign_name') }}</h2>
                    <div>
                        <label class="loop-label">{{ __('loop.campaign_name') }}</label>
                        <input name="name" class="loop-input" value="{{ old('name', $t['name'] ?? '') }}" :required="step === 1">
                        <p class="mt-1 text-xs text-ink-muted">{{ __('loop.campaign_name_hint', ['business' => $business->name]) }}</p>
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
                            <select name="type" class="loop-input" :required="step === 1">
                                <option value="earn" @selected($defaultType === 'earn')>{{ __('loop.type_earn') }}</option>
                                <option value="product_push" @selected($defaultType === 'product_push')>{{ __('loop.type_product_push') }}</option>
                            </select>
                        </div>
                    @endif
                    <div>
                        <label class="loop-label">{{ __('loop.description') }}</label>
                        <textarea name="description" rows="2" class="loop-input">{{ old('description', $t['description'] ?? '') }}</textarea>
                    </div>
                    <button type="button" class="loop-btn-mint w-full" @click="next()">{{ __('loop.continue') }}</button>
                    <a href="{{ route('campaigns.create') }}" class="block text-center text-sm text-ink-muted underline">{{ __('loop.back') }}</a>
                </div>

                {{-- 2 · Member gets (min spend + points + optional bonuses) --}}
                <div data-step="2" :class="step === 2 ? '' : 'hidden'" class="space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">2 · {{ __('loop.customer_gets') }}</p>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.customer_gets') }}</h2>
                    <p class="text-sm text-ink-muted">{{ __('loop.min_spend_section_help') }}</p>
                    <input type="hidden" name="spend_step" :value="spendValue()">
                    <input type="hidden" name="bonus_points" value="{{ old('bonus_points', $t['bonus_points'] ?? 0) }}">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="loop-label">{{ __('loop.min_spend_to_earn') }} ({{ $business->currency }})</label>
                            <input type="text" inputmode="numeric" class="loop-input" x-model="spendDisplay" @input="formatSpend()" :required="step === 2">
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.points_earned') }}</label>
                            <input type="number" name="points_per_step" class="loop-input" x-model="pointsPerStep" :required="step === 2" min="1">
                        </div>
                    </div>
                    <p class="text-center font-display text-xl font-bold">
                        <span x-text="pointsPerStep"></span> {{ __('loop.pts') }} /
                        <span x-text="spendDisplay || '0'"></span> <span x-text="currency"></span>
                    </p>

                    <div class="rounded-2xl border border-dashed border-ink/10 bg-chalk/40 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.section_bonuses') }} · {{ __('loop.optional') }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.bonuses_body') }}</p>

                        <label class="mt-3 flex items-start gap-3 rounded-2xl bg-white px-4 py-3 ring-1 ring-ink/5">
                            <input type="checkbox" name="enable_welcome" value="1" class="mt-1" x-model="enableWelcome">
                            <span class="flex-1">
                                <span class="block text-sm font-semibold">{{ __('loop.type_welcome') }}</span>
                                <span class="text-xs text-ink-muted">{{ __('loop.welcome_bonus_hint') }}</span>
                                <input type="number" name="welcome_points" class="loop-input mt-2" value="{{ old('welcome_points', 20) }}" x-show="enableWelcome" x-cloak>
                            </span>
                        </label>

                        <label class="mt-2 flex items-start gap-3 rounded-2xl bg-white px-4 py-3 ring-1 ring-ink/5">
                            <input type="checkbox" name="enable_birthday" value="1" class="mt-1" x-model="enableBirthday">
                            <span class="flex-1">
                                <span class="block text-sm font-semibold">{{ __('loop.type_birthday') }}</span>
                                <span class="text-xs text-ink-muted">{{ __('loop.birthday_bonus_hint') }}</span>
                                <input type="number" name="birthday_points" class="loop-input mt-2" value="{{ old('birthday_points', 50) }}" x-show="enableBirthday" x-cloak>
                            </span>
                        </label>

                        <label class="mt-2 flex items-start gap-3 rounded-2xl bg-white px-4 py-3 ring-1 ring-ink/5">
                            <input type="checkbox" name="enable_streak" value="1" class="mt-1" x-model="enableStreak">
                            <span class="flex-1">
                                <span class="block text-sm font-semibold">{{ __('loop.type_streak') }}</span>
                                <span class="text-xs text-ink-muted">{{ __('loop.streak_advice') }}</span>
                                <div class="mt-2 grid gap-2 sm:grid-cols-3" x-show="enableStreak" x-cloak>
                                    <input type="number" name="streak_target" class="loop-input" placeholder="{{ __('loop.streak_target') }}" value="{{ old('streak_target', 3) }}">
                                    <select name="streak_period" class="loop-input">
                                        <option value="week">{{ __('loop.streak_period_week') }}</option>
                                        <option value="month">{{ __('loop.streak_period_month') }}</option>
                                    </select>
                                    <input type="number" name="streak_points" class="loop-input" placeholder="{{ __('loop.bonus_points') }}" value="{{ old('streak_points', 30) }}">
                                </div>
                            </span>
                        </label>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" class="loop-btn-ghost flex-1" @click="go(1)">{{ __('loop.back') }}</button>
                        <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                    </div>
                </div>

                {{-- 3 · Save / launch --}}
                <div data-step="3" :class="step === 3 ? '' : 'hidden'" class="space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">3 · {{ __('loop.save') }}</p>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.section_schedule') }}</h2>
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
                    <p class="rounded-2xl border border-ink/10 bg-chalk/50 px-4 py-3 text-sm text-ink-muted">
                        {{ __('loop.campaign_offers_untied_hint') }}
                        <a href="{{ route('rewards.create') }}" class="font-semibold text-mint-deep" @click="$store.loopNav.go(@js(route('rewards.create')), $event, { kind: 'push' })">{{ __('loop.offers') }} →</a>
                    </p>
                    <div class="flex gap-3">
                        <button type="button" class="loop-btn-ghost flex-1" @click="go(2)">{{ __('loop.back') }}</button>
                        <button class="loop-btn-mint flex-1">{{ __('loop.launch_campaign') }}</button>
                    </div>
                </div>
            </form>
        </div>
    @endif
</x-app-layout>
