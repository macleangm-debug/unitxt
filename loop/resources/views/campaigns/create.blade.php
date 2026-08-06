@php
    $selectedShops = old('shop_ids', []);
    $t = $template ?? null;
    $defaultType = old('type', $t['type'] ?? 'earn');
@endphp
<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.new_campaign') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.campaign_create_blurb') }}</p>
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

        <div class="grid gap-3 sm:grid-cols-2">
            <a href="{{ route('campaigns.create', ['own' => 1]) }}" class="flex min-h-[9.5rem] flex-col rounded-3xl border border-dashed border-ink/20 bg-chalk/50 p-5 transition hover:-translate-y-0.5 hover:border-mint hover:bg-mint-soft/20">
                <p class="font-display text-lg font-semibold">{{ __('loop.create_own') }}</p>
                <p class="mt-2 flex-1 text-sm text-ink-muted">{{ __('loop.create_own_campaign_body') }}</p>
                <p class="mt-4 text-sm font-semibold text-mint-deep">{{ __('loop.continue') }} →</p>
            </a>
        </div>
    @else
        <form method="POST" action="{{ route('campaigns.store') }}"
              x-data="{ type: @js($defaultType) }"
              class="mx-auto max-w-2xl space-y-6 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8">
            @csrf
            @if ($templateKey)
                <input type="hidden" name="template_key" value="{{ $templateKey }}">
            @endif

            @if ($t)
                <div class="rounded-2xl bg-gradient-to-br from-mint/20 to-coral/10 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.start_from_template') }}</p>
                    <p class="mt-1 font-display text-xl font-semibold">{{ $t['name'] }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ $t['description'] }}</p>
                </div>
            @endif

            <section class="space-y-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.section_basics') }}</p>
                    <h2 class="mt-1 font-display text-xl font-semibold">{{ __('loop.campaign_basics_title') }}</h2>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.campaign_name') }}</label>
                    <input name="name" class="loop-input" value="{{ old('name', $t['name'] ?? '') }}" required>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.type') }}</label>
                    <select name="type" class="loop-input" x-model="type">
                        @foreach ([
                            'earn' => __('loop.type_earn'),
                            'product_push' => __('loop.type_product_push'),
                            'streak' => __('loop.type_streak'),
                            'birthday' => __('loop.type_birthday'),
                            'welcome' => __('loop.type_welcome'),
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected($defaultType === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-ink-muted" x-show="type === 'streak'">{{ __('loop.streak_advice') }}</p>
                    <p class="mt-1 text-xs text-ink-muted" x-show="type === 'birthday'">{{ __('loop.birthday_advice') }}</p>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.description') }}</label>
                    <textarea name="description" rows="2" class="loop-input" placeholder="{{ __('loop.campaign_desc_placeholder') }}">{{ old('description', $t['description'] ?? '') }}</textarea>
                </div>
            </section>

            <section class="space-y-4 rounded-2xl bg-chalk/70 p-4" x-show="type === 'earn' || type === 'product_push'" x-cloak>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.section_earn') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.earn_rules_short') }}</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label class="loop-label">{{ __('loop.spend_step') }} ({{ $business->currency }})</label>
                        <input type="number" name="spend_step" class="loop-input" value="{{ old('spend_step', $t['spend_step'] ?? 1000) }}">
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.points_per_step') }}</label>
                        <input type="number" name="points_per_step" class="loop-input" value="{{ old('points_per_step', $t['points_per_step'] ?? 2) }}">
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.bonus_points') }}</label>
                        <input type="number" name="bonus_points" class="loop-input" value="{{ old('bonus_points', $t['bonus_points'] ?? 0) }}">
                    </div>
                </div>
            </section>

            <section class="space-y-4 rounded-2xl bg-chalk/70 p-4" x-show="type === 'streak'" x-cloak>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.section_streak') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.streak_section_hint') }}</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label class="loop-label">{{ __('loop.streak_target') }}</label>
                        <input type="number" name="streak_target" min="2" class="loop-input" value="{{ old('streak_target', $t['streak_target'] ?? 3) }}">
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.streak_period') }}</label>
                        <select name="streak_period" class="loop-input">
                            <option value="week" @selected(old('streak_period', $t['streak_period'] ?? 'week') === 'week')>{{ __('loop.streak_period_week') }}</option>
                            <option value="month" @selected(old('streak_period', $t['streak_period'] ?? 'week') === 'month')>{{ __('loop.streak_period_month') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.bonus_points') }}</label>
                        <input type="number" name="bonus_points" class="loop-input" value="{{ old('bonus_points', $t['bonus_points'] ?? 30) }}">
                    </div>
                </div>
            </section>

            <section class="space-y-4 rounded-2xl bg-chalk/70 p-4" x-show="type === 'birthday' || type === 'welcome'" x-cloak>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.section_bonus') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.bonus_section_hint') }}</p>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.bonus_points') }}</label>
                    <input type="number" name="bonus_points" class="loop-input" value="{{ old('bonus_points', $t['bonus_points'] ?? 20) }}">
                </div>
            </section>

            <section class="space-y-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.section_schedule') }}</p>
                    <h2 class="mt-1 font-display text-xl font-semibold">{{ __('loop.when_and_where') }}</h2>
                </div>
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
            </section>

            <p class="rounded-2xl bg-mint-soft/50 px-4 py-3 text-sm text-ink-muted">{{ __('loop.campaign_then_offers_hint') }}</p>
            <button class="loop-btn-mint w-full">{{ __('loop.launch_campaign') }}</button>
            <a href="{{ route('campaigns.create') }}" class="block text-center text-sm text-ink-muted underline">{{ __('loop.back') }}</a>
        </form>
    @endif
</x-app-layout>
