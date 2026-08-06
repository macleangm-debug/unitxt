<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.offers') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.add_offer') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.add_offer_owner_hint') }}</p>
        </div>
    </x-slot>

    @if (! $showForm)
        <div class="mb-6 max-w-2xl">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.choose_offer_type') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.choose_offer_type_body_short') }}</p>
        </div>

        @if ($earnCampaign)
            <p class="mb-6 rounded-2xl bg-mint-soft/60 px-4 py-3 text-sm font-medium text-ink">
                {{ $earnCampaign->ruleSummary($business->currency) }}
            </p>
        @endif

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($typeStarters as $starter)
                <a href="{{ route('rewards.create', ['type' => $starter['key']]) }}"
                   class="flex min-h-[11rem] flex-col rounded-3xl border border-ink/10 bg-white/90 p-5 transition hover:-translate-y-0.5 hover:border-mint hover:bg-mint-soft/30">
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-display text-lg font-semibold">{{ $starter['name'] }}</p>
                        <span class="shrink-0 rounded-lg bg-ink px-2 py-1 text-xs font-semibold text-mint">{{ $starter['points_cost'] }} {{ __('loop.pts') }}</span>
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
            $spendPerPoint = $earnCampaign && $earnCampaign->points_per_step
                ? ($earnCampaign->spend_step / $earnCampaign->points_per_step)
                : 0;
        @endphp

        <form method="POST" action="{{ route('rewards.store') }}"
              x-data="{
                type: @js($defaultType),
                step: 1,
                name: @js($defaultName),
                points: {{ (int) $defaultPoints }},
                value: {{ (float) $defaultValue }},
                product: @js($defaultProduct),
                spendPerPoint: {{ (float) $spendPerPoint }},
                currency: @js($business->currency),
                unlockSpend() {
                    if (!this.spendPerPoint) return 0;
                    return Math.round(this.points * this.spendPerPoint);
                }
              }"
              class="mx-auto max-w-xl space-y-6 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8">
            @csrf

            <div x-show="step === 1" class="space-y-6">
                <section class="space-y-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">1 · {{ __('loop.section_offer_type') }}</p>
                        <h2 class="mt-1 font-display text-xl font-semibold">{{ __('loop.what_to_give_back') }}</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach (['free_item', 'percent_off', 'fixed_off', 'custom'] as $option)
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-ink/10 bg-chalk/70 px-3 py-3 text-sm has-[:checked]:border-mint has-[:checked]:bg-mint-soft/50">
                                <input type="radio" name="reward_type" value="{{ $option }}" class="text-mint-deep focus:ring-mint"
                                       x-model="type" @checked($defaultType === $option)>
                                <span class="font-semibold">{{ __('loop.'.$option) }}</span>
                            </label>
                        @endforeach
                    </div>
                </section>

                <section class="space-y-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">2 · {{ __('loop.section_offer_details') }}</p>
                        <h2 class="mt-1 font-display text-xl font-semibold">{{ __('loop.name_your_offer') }}</h2>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.offer_boost_hint') }}</p>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.offer_name') }}</label>
                        <input name="name" class="loop-input" x-model="name" placeholder="{{ __('loop.offer_name_placeholder') }}" required>
                    </div>
                    @if ($ideas->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($ideas as $idea)
                                <button type="button"
                                        class="rounded-full border border-ink/10 bg-white px-3 py-1.5 text-xs font-semibold text-ink-muted hover:border-mint hover:text-ink"
                                        @click="name = @js($idea['name']); if (@js($idea['product_name'])) { product = @js($idea['product_name']); }">
                                    {{ $idea['name'] }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                    <div>
                        <label class="loop-label">{{ __('loop.description') }}</label>
                        <textarea name="description" class="loop-input" rows="2" placeholder="{{ __('loop.offer_desc_placeholder_short') }}">{{ $defaultDesc }}</textarea>
                    </div>
                    <div x-show="type === 'free_item' || type === 'custom'" x-cloak>
                        <label class="loop-label">{{ __('loop.product') }}</label>
                        <input name="product_name" class="loop-input" x-model="product" placeholder="{{ __('loop.product_placeholder') }}">
                        <p class="mt-1 text-xs text-ink-muted">{{ __('loop.product_boost_hint') }}</p>
                    </div>
                </section>

                <section class="space-y-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">3 · {{ __('loop.section_offer_cost') }}</p>
                        <h2 class="mt-1 font-display text-xl font-semibold">{{ __('loop.points_and_value') }}</h2>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="loop-label">{{ __('loop.points_cost') }}</label>
                            <input type="number" name="points_cost" x-model.number="points" class="loop-input" required>
                        </div>
                        <div x-show="type === 'percent_off' || type === 'fixed_off'" x-cloak>
                            <label class="loop-label">{{ __('loop.value_hint') }}</label>
                            <input type="number" step="0.01" name="reward_value" x-model.number="value" class="loop-input">
                        </div>
                    </div>
                </section>

                <section class="space-y-4 rounded-2xl bg-chalk/70 p-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">4 · {{ __('loop.section_limits') }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.limits_optional_hint') }}</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="loop-label">{{ __('loop.stock_optional') }}</label>
                            <input type="number" name="stock" class="loop-input" value="{{ old('stock') }}" placeholder="∞">
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.stock_help') }}</p>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.max_per_member') }}</label>
                            <input type="number" name="max_redemptions_per_member" class="loop-input" min="1" value="{{ old('max_redemptions_per_member') }}">
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.max_per_member_help') }}</p>
                        </div>
                    </div>
                </section>

                <button type="button" class="loop-btn-mint w-full" @click="step = 2">{{ __('loop.review_offer') }}</button>
                <a href="{{ route('rewards.create') }}" class="block text-center text-sm text-ink-muted underline">{{ __('loop.back') }}</a>
            </div>

            <div x-show="step === 2" x-cloak class="space-y-5">
                <div class="rounded-[1.5rem] bg-gradient-to-br from-mint/20 to-white p-5 ring-1 ring-mint/20">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.review_before_launch') }}</p>
                    <p class="mt-2 font-display text-2xl font-semibold" x-text="name"></p>
                    <p class="mt-3 text-sm text-ink-muted">
                        <span x-text="points"></span> {{ __('loop.pts') }}
                        <span x-show="product"> · <span x-text="product"></span></span>
                    </p>
                    <template x-if="unlockSpend() > 0">
                        <p class="mt-4 rounded-2xl bg-ink px-4 py-3 text-sm text-white">
                            {{ __('loop.customer_spend_to_unlock_prefix') }}
                            <span class="font-semibold" x-text="currency + ' ' + unlockSpend().toLocaleString()"></span>
                            {{ __('loop.customer_spend_to_unlock_suffix') }}
                        </p>
                    </template>
                    <template x-if="unlockSpend() === 0">
                        <p class="mt-4 text-sm text-ink-muted">{{ __('loop.no_earn_campaign_for_unlock') }}</p>
                    </template>
                </div>
                <button class="loop-btn-mint w-full">{{ __('loop.confirm_launch_offer') }}</button>
                <button type="button" class="w-full text-sm font-semibold text-ink-muted" @click="step = 1">{{ __('loop.back') }}</button>
            </div>
        </form>
    @endif
</x-app-layout>
