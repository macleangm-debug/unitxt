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
            $defaultType = old('reward_type', $t['reward_type'] ?? $type['reward_type'] ?? 'percent_off');
            $defaultPoints = old('points_cost', $t['points_cost'] ?? $type['points_cost'] ?? 100);
            $defaultValue = old('reward_value', $t['reward_value'] ?? $type['reward_value'] ?? 0);
            $defaultProduct = old('product_name', $t['product_name'] ?? $type['product_name'] ?? '');
            $defaultDesc = old('description', $t['description'] ?? $type['description'] ?? '');
            $spendPerPoint = $earnCampaign && $earnCampaign->points_per_step
                ? ($earnCampaign->spend_step / $earnCampaign->points_per_step)
                : 0;
            $biz = $business->name;
        @endphp

        <form method="POST" action="{{ route('rewards.store') }}"
              x-data="{
                type: @js($defaultType),
                step: 1,
                name: @js($defaultName),
                points: {{ (int) $defaultPoints }},
                valueDisplay: @js($defaultType === 'fixed_off' ? number_format((float) $defaultValue) : (string) $defaultValue),
                product: @js($defaultProduct),
                spendPerPoint: {{ (float) $spendPerPoint }},
                currency: @js($business->currency),
                businessName: @js($biz),
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
              class="mx-auto max-w-xl space-y-6 rounded-[2rem] border border-ink/10 bg-white p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8">
            @csrf
            <input type="hidden" name="reward_value" :value="type === 'free_item' || type === 'custom' ? 0 : valueNumber()">

            <div x-show="step === 1" class="space-y-8">
                {{-- 1. Type --}}
                <section class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">1 · {{ __('loop.section_offer_type') }}</p>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.what_to_give_back') }}</h2>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach (['percent_off', 'fixed_off', 'free_item', 'custom'] as $option)
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-ink/10 px-3 py-3 text-sm has-[:checked]:border-mint has-[:checked]:bg-mint-soft/40">
                                <input type="radio" name="reward_type" value="{{ $option }}" class="text-mint-deep focus:ring-mint"
                                       x-model="type" @checked($defaultType === $option)
                                       @change="valueDisplay = ($event.target.value === 'percent_off' ? '5' : ($event.target.value === 'fixed_off' ? '2,000' : '0'))">
                                <span class="font-semibold">{{ __('loop.'.$option) }}</span>
                            </label>
                        @endforeach
                    </div>
                </section>

                {{-- 2. Name --}}
                <section class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">2 · {{ __('loop.section_offer_details') }}</p>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.name_your_offer') }}</h2>
                    <p class="text-sm text-ink-muted">{{ __('loop.offer_name_hint', ['business' => $biz]) }}</p>
                    <div>
                        <label class="loop-label">{{ __('loop.offer_name') }}</label>
                        <input name="name" class="loop-input font-display text-lg font-semibold" x-model="name" required>
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
                </section>

                {{-- 3. Type-specific reward --}}
                <section class="space-y-3 rounded-2xl border border-ink/10 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">3 · {{ __('loop.section_offer_reward') }}</p>

                    <div x-show="type === 'percent_off'" x-cloak class="space-y-3">
                        <h2 class="font-display text-lg font-semibold">{{ __('loop.percent_off_value') }}</h2>
                        <p class="text-sm text-ink-muted">{{ __('loop.percent_off_form_help') }}</p>
                        <input type="number" min="1" max="100" class="loop-input" x-model="valueDisplay" placeholder="5">
                        <p class="text-sm font-semibold text-ink">
                            <span x-text="valueDisplay || 0"></span>% {{ __('loop.off_every_eligible_sale') }}
                        </p>
                    </div>

                    <div x-show="type === 'fixed_off'" x-cloak class="space-y-3">
                        <h2 class="font-display text-lg font-semibold">{{ __('loop.fixed_off_value') }}</h2>
                        <p class="text-sm text-ink-muted">{{ __('loop.fixed_off_form_help') }}</p>
                        <input type="text" inputmode="numeric" class="loop-input" x-model="valueDisplay" @input="formatValue()">
                        <p class="text-sm font-semibold text-ink">
                            {{ $business->currency }} <span x-text="valueDisplay || 0"></span> {{ __('loop.off_every_eligible_sale') }}
                        </p>
                    </div>

                    <div x-show="type === 'free_item' || type === 'custom'" x-cloak class="space-y-3">
                        <h2 class="font-display text-lg font-semibold">{{ __('loop.tie_to_product_optional') }}</h2>
                        <p class="text-sm text-ink-muted">{{ __('loop.tie_to_product_hint') }}</p>
                        <input name="product_name" class="loop-input" x-model="product" placeholder="{{ __('loop.tie_to_product_placeholder') }}">
                    </div>
                </section>

                {{-- 4. Points gate --}}
                <section class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">4 · {{ __('loop.section_offer_cost') }}</p>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.points_to_unlock') }}</h2>
                    <p class="text-sm text-ink-muted">{{ __('loop.points_to_unlock_help') }}</p>
                    <input type="number" name="points_cost" x-model.number="points" class="loop-input" required min="1">
                    <template x-if="unlockSpend() > 0">
                        <p class="rounded-2xl border border-ink/10 bg-chalk/50 px-4 py-3 text-sm text-ink">
                            {{ __('loop.customer_spend_to_unlock_prefix') }}
                            <span class="font-semibold" x-text="currency + ' ' + unlockSpend().toLocaleString()"></span>
                            {{ __('loop.customer_spend_to_unlock_suffix') }}
                        </p>
                    </template>
                </section>

                {{-- 5. Optional limits --}}
                <section class="space-y-3 rounded-2xl border border-ink/10 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">5 · {{ __('loop.section_limits') }}</p>
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
                </section>

                <button type="button" class="loop-btn-mint w-full" @click="step = 2">{{ __('loop.review_offer') }}</button>
                <a href="{{ route('rewards.create') }}" class="block text-center text-sm text-ink-muted underline">{{ __('loop.back') }}</a>
            </div>

            <div x-show="step === 2" x-cloak class="space-y-5">
                <div class="rounded-[1.5rem] border border-ink/10 bg-white p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.review_before_launch') }}</p>
                    <p class="mt-2 font-display text-2xl font-bold" x-text="name"></p>
                    <p class="mt-3 text-sm text-ink-muted">
                        <span x-text="points"></span> {{ __('loop.pts') }}
                        <span x-show="type === 'percent_off'"> · <span x-text="valueDisplay"></span>%</span>
                        <span x-show="type === 'fixed_off'"> · {{ $business->currency }} <span x-text="valueDisplay"></span></span>
                        <span x-show="product"> · <span x-text="product"></span></span>
                    </p>
                    <template x-if="unlockSpend() > 0">
                        <p class="mt-4 rounded-2xl bg-ink px-4 py-3 text-sm text-white">
                            {{ __('loop.customer_spend_to_unlock_prefix') }}
                            <span class="font-semibold" x-text="currency + ' ' + unlockSpend().toLocaleString()"></span>
                            {{ __('loop.customer_spend_to_unlock_suffix') }}
                        </p>
                    </template>
                </div>
                <button class="loop-btn-mint w-full">{{ __('loop.confirm_launch_offer') }}</button>
                <button type="button" class="w-full text-sm font-semibold text-ink-muted" @click="step = 1">{{ __('loop.back') }}</button>
            </div>
        </form>
    @endif
</x-app-layout>
