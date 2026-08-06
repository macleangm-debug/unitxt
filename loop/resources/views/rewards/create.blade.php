@php
    $t = $selectedTemplate;
    $earn = $earnCampaign;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.add_offer') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.add_offer_guided') }}</p>
    </x-slot>

    @if (! $createOwn && ! $t)
        <p class="mb-4 text-sm text-ink-muted">{{ __('loop.offer_templates_hint') }}</p>
        @if ($earn)
            <p class="mb-6 rounded-2xl bg-mint-soft/60 px-4 py-3 text-sm font-medium text-ink">
                {{ $earn->ruleSummary($business->currency) }}
            </p>
        @endif

        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($offerTemplates as $offer)
                @php
                    $hint = \App\Support\OfferTemplates::spendToUnlock($earn, $offer['points_cost'], $business->currency);
                @endphp
                <a href="{{ route('rewards.create', ['template' => $offer['key']]) }}" class="rounded-3xl border border-ink/10 bg-white/90 p-5 transition hover:-translate-y-0.5 hover:border-mint hover:bg-mint-soft/30">
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-display text-lg font-semibold">{{ $offer['name'] }}</p>
                        <span class="shrink-0 rounded-lg bg-ink px-2 py-1 text-xs font-semibold text-mint">{{ $offer['points_cost'] }} pts</span>
                    </div>
                    <p class="mt-2 text-sm text-ink-muted">{{ $offer['description'] }}</p>
                    @if ($hint)
                        <p class="mt-2 text-xs font-medium text-mint-deep">{{ $hint }}</p>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('rewards.create', ['own' => 1]) }}" class="loop-btn-ghost">{{ __('loop.create_own_offer') }}</a>
            <a href="{{ route('campaigns.index') }}#offers" class="text-sm font-semibold text-ink-muted underline">{{ __('loop.back') }}</a>
        </div>
    @else
        <form method="POST" action="{{ route('rewards.store') }}" class="mx-auto max-w-xl space-y-4 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8">
            @csrf
            @if ($t)
                <input type="hidden" name="template_key" value="{{ $t['key'] }}">
                <div class="rounded-2xl bg-gradient-to-br from-mint/20 to-coral/10 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.start_from_template') }}</p>
                    <p class="mt-1 font-display text-xl font-semibold">{{ $t['name'] }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ $t['description'] }}</p>
                    @php
                        $hint = \App\Support\OfferTemplates::spendToUnlock($earn, $t['points_cost'], $business->currency);
                    @endphp
                    @if ($hint)
                        <p class="mt-2 text-xs font-medium text-mint-deep">{{ $hint }}</p>
                    @endif
                </div>
                @unless ($createOwn || request()->boolean('own'))
                    <button class="loop-btn-mint w-full">{{ __('loop.save_offer') }}</button>
                    <a href="{{ route('rewards.create', ['template' => $t['key'], 'own' => 1]) }}" class="block text-center text-sm font-semibold text-mint-deep">{{ __('loop.customize_offer') }}</a>
                @endunless
            @endif

            @if ($createOwn || request()->boolean('own'))
                @if ($t)
                    <input type="hidden" name="customize" value="1">
                @endif
                <div>
                    <label class="loop-label">{{ __('loop.offer_name') }}</label>
                    <input name="name" class="loop-input" placeholder="{{ __('loop.offer_name_placeholder') }}" value="{{ old('name', $t['name'] ?? '') }}" required>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.description') }}</label>
                    <textarea name="description" class="loop-input" rows="2" placeholder="{{ __('loop.offer_desc_placeholder') }}">{{ old('description', $t['description'] ?? '') }}</textarea>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="loop-label">{{ __('loop.product') }}</label>
                        <input name="product_name" class="loop-input" placeholder="{{ __('loop.product_placeholder') }}" value="{{ old('product_name', $t['product_name'] ?? '') }}">
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.sku') }}</label>
                        <input name="product_sku" class="loop-input" placeholder="SKU-100" value="{{ old('product_sku') }}">
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="loop-label">{{ __('loop.points_cost') }}</label>
                        <input type="number" name="points_cost" value="{{ old('points_cost', $t['points_cost'] ?? 100) }}" class="loop-input" required>
                        <p class="mt-1 text-xs text-ink-muted">{{ __('loop.points_cost_help') }}</p>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.type') }}</label>
                        <select name="reward_type" class="loop-input">
                            @foreach (['free_item', 'percent_off', 'fixed_off', 'custom'] as $type)
                                <option value="{{ $type }}" @selected(old('reward_type', $t['reward_type'] ?? 'free_item') === $type)>{{ __('loop.'.$type) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.value_hint') }}</label>
                    <input type="number" step="0.01" name="reward_value" value="{{ old('reward_value', $t['reward_value'] ?? 0) }}" class="loop-input">
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
            @endif

            <a href="{{ $t && ($createOwn || request()->boolean('own')) ? route('rewards.create', ['template' => $t['key']]) : route('rewards.create') }}" class="block text-center text-sm text-ink-muted underline">{{ __('loop.back') }}</a>
        </form>
    @endif
</x-app-layout>
