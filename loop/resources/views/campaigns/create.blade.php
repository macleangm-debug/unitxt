@php
    $selectedShops = old('shop_ids', []);
    $t = $template ?? null;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.new_campaign') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.campaigns_vs_offers') }}</p>
    </x-slot>

    @if ($picking)
        <div class="mb-6 flex flex-wrap gap-3">
            <a href="{{ route('campaigns.create', ['own' => 1]) }}" class="loop-btn-ghost">{{ __('loop.create_own') }}</a>
        </div>

        @forelse ($groupedTemplates as $intention => $group)
            <section class="mb-8">
                <h2 class="font-display text-lg font-semibold">{{ $group['label'] }}</h2>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    @foreach ($group['templates'] as $key => $item)
                        <a href="{{ route('campaigns.create', ['template' => $key]) }}" class="rounded-3xl border border-ink/10 bg-white/90 p-5 transition hover:-translate-y-0.5 hover:border-mint hover:bg-mint-soft/30">
                            <p class="font-display text-lg font-semibold">{{ $item['name'] }}</p>
                            <p class="mt-2 text-sm text-ink-muted">{{ $item['description'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="loop-panel p-6 text-sm text-ink-muted">
                {{ __('loop.all_templates_used') }}
                <a href="{{ route('campaigns.create', ['own' => 1]) }}" class="mt-3 inline-flex font-semibold text-mint-deep">{{ __('loop.create_own') }} →</a>
            </div>
        @endforelse
    @else
        <form method="POST" action="{{ route('campaigns.store') }}" class="mx-auto max-w-2xl space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8">
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

            <div>
                <label class="loop-label">{{ __('loop.campaign_name') }}</label>
                <input name="name" class="loop-input" value="{{ old('name', $t['name'] ?? '') }}" required>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.type') }}</label>
                <select name="type" class="loop-input" x-data="{ type: '{{ old('type', $t['type'] ?? 'earn') }}' }" x-model="type">
                    @foreach ([
                        'earn' => __('loop.type_earn'),
                        'product_push' => __('loop.type_product_push'),
                        'streak' => __('loop.type_streak'),
                        'birthday' => __('loop.type_birthday'),
                        'welcome' => __('loop.type_welcome'),
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $t['type'] ?? 'earn') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.description') }}</label>
                <textarea name="description" rows="2" class="loop-input">{{ old('description', $t['description'] ?? '') }}</textarea>
            </div>

            <div class="rounded-2xl bg-chalk/80 p-4">
                <p class="text-sm font-semibold">{{ __('loop.earn_rules') }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.spend_step_help') }}</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-3">
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
                        <p class="mt-1 text-[11px] text-ink-muted">{{ __('loop.bonus_points_help') }}</p>
                    </div>
                </div>
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

            <p class="rounded-2xl bg-mint-soft/50 px-4 py-3 text-sm text-ink-muted">{{ __('loop.campaign_then_offers_hint') }}</p>

            <button class="loop-btn-mint w-full">{{ __('loop.launch_campaign') }}</button>
            <a href="{{ route('campaigns.create') }}" class="block text-center text-sm text-ink-muted underline">{{ __('loop.back') }}</a>
        </form>
    @endif
</x-app-layout>
