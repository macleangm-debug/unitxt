<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.offers') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.add_offer') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.add_offer_guided') }}</p>
        </div>
    </x-slot>

    @if (! $showForm)
        <div class="mb-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.choose_offer_type') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.choose_offer_type_body') }}</p>
        </div>

        @if ($earnCampaign)
            <p class="mb-6 rounded-2xl bg-mint-soft/60 px-4 py-3 text-sm font-medium text-ink">
                {{ $earnCampaign->ruleSummary($business->currency) }}
            </p>
        @endif

        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($typeStarters as $starter)
                <a href="{{ route('rewards.create', ['type' => $starter['key']]) }}"
                   class="flex min-h-[10.5rem] flex-col rounded-3xl border border-ink/10 bg-white/90 p-5 transition hover:-translate-y-0.5 hover:border-mint hover:bg-mint-soft/30">
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-display text-lg font-semibold">{{ $starter['name'] }}</p>
                        <span class="shrink-0 rounded-lg bg-ink px-2 py-1 text-xs font-semibold text-mint">{{ $starter['points_cost'] }} pts</span>
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
            $type = $selectedType;
            $defaultName = old('name', $t['name'] ?? $type['default_name'] ?? '');
            $defaultType = old('reward_type', $t['reward_type'] ?? $type['reward_type'] ?? 'free_item');
            $defaultPoints = old('points_cost', $t['points_cost'] ?? $type['points_cost'] ?? 100);
            $defaultValue = old('reward_value', $t['reward_value'] ?? $type['reward_value'] ?? 0);
            $defaultProduct = old('product_name', $t['product_name'] ?? $type['product_name'] ?? '');
            $defaultDesc = old('description', $t['description'] ?? $type['description'] ?? '');
            $ideas = collect($offerTemplates)->where('reward_type', $defaultType)->take(4);
        @endphp

        <form method="POST" action="{{ route('rewards.store') }}"
              x-data="{ type: @js($defaultType) }"
              class="mx-auto max-w-xl space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8">
            @csrf

            <div>
                <label class="loop-label">{{ __('loop.type') }}</label>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    @foreach (['free_item', 'percent_off', 'fixed_off', 'custom'] as $option)
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-ink/10 bg-chalk/70 px-3 py-3 text-sm has-[:checked]:border-mint has-[:checked]:bg-mint-soft/50">
                            <input type="radio" name="reward_type" value="{{ $option }}" class="text-mint-deep focus:ring-mint"
                                   x-model="type" @checked($defaultType === $option)>
                            <span class="font-semibold">{{ __('loop.'.$option) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="loop-label">{{ __('loop.offer_name') }}</label>
                <input name="name" class="loop-input" placeholder="{{ __('loop.offer_name_placeholder') }}" value="{{ $defaultName }}" required>
                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.offer_name_override_hint') }}</p>
            </div>

            @if ($ideas->isNotEmpty())
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.name_ideas') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($ideas as $idea)
                            <button type="button"
                                    class="rounded-full border border-ink/10 bg-white px-3 py-1.5 text-xs font-semibold text-ink-muted hover:border-mint hover:text-ink"
                                    @click="$el.closest('form').querySelector('[name=name]').value = @js($idea['name']); if (@js($idea['product_name'])) { $el.closest('form').querySelector('[name=product_name]').value = @js($idea['product_name']); }">
                                {{ $idea['name'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <label class="loop-label">{{ __('loop.description') }}</label>
                <textarea name="description" class="loop-input" rows="2" placeholder="{{ __('loop.offer_desc_placeholder') }}">{{ $defaultDesc }}</textarea>
            </div>

            <div class="grid gap-3 sm:grid-cols-2" x-show="type === 'free_item' || type === 'custom'" x-cloak>
                <div>
                    <label class="loop-label">{{ __('loop.product') }}</label>
                    <input name="product_name" class="loop-input" placeholder="{{ __('loop.product_placeholder') }}" value="{{ $defaultProduct }}">
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.sku') }}</label>
                    <input name="product_sku" class="loop-input" placeholder="SKU-100" value="{{ old('product_sku') }}">
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="loop-label">{{ __('loop.points_cost') }}</label>
                    <input type="number" name="points_cost" value="{{ $defaultPoints }}" class="loop-input" required>
                    <p class="mt-1 text-xs text-ink-muted">{{ __('loop.points_cost_help') }}</p>
                </div>
                <div x-show="type === 'percent_off' || type === 'fixed_off'" x-cloak>
                    <label class="loop-label">{{ __('loop.value_hint') }}</label>
                    <input type="number" step="0.01" name="reward_value" value="{{ $defaultValue }}" class="loop-input">
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="loop-label">{{ __('loop.stock_optional') }}</label>
                    <input type="number" name="stock" class="loop-input" value="{{ old('stock') }}">
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.max_per_member') }}</label>
                    <input type="number" name="max_redemptions_per_member" class="loop-input" min="1" value="{{ old('max_redemptions_per_member') }}">
                </div>
            </div>

            <button class="loop-btn-mint w-full">{{ __('loop.save_offer') }}</button>
            <a href="{{ route('rewards.create') }}" class="block text-center text-sm text-ink-muted underline">{{ __('loop.back') }}</a>
        </form>
    @endif
</x-app-layout>
