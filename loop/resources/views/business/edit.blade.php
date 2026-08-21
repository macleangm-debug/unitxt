<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start gap-3">
            <x-back-icon :href="route('settings')" />
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.settings') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.business_settings') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.business_settings_blurb') }}</p>
            </div>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('business.update') }}" enctype="multipart/form-data" class="loop-panel mx-auto max-w-xl space-y-5 p-6 sm:p-8">
        @csrf
        @method('PATCH')

        <div>
            <p class="loop-label">{{ __('loop.business_logo') }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.shared_logo_hint') }}</p>
            <div class="mt-4">
                <x-logo-placeholder
                    name="logo"
                    :preview="$business->logoUrl()"
                    :hint="__('loop.logo_square_hint')"
                />
            </div>
        </div>

        <div>
            <label class="loop-label">{{ __('loop.business_name') }}</label>
            <input name="name" value="{{ old('name', $business->name) }}" class="loop-input" required>
        </div>
        <div>
            <x-city-sheet-select
                name="city"
                :label="__('loop.city')"
                :value="old('city', $business->city)"
                :country="$business->country ?? 'TZ'"
            />
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="loop-label">{{ __('loop.country') }}</label>
                <input class="loop-input bg-chalk" value="{{ \App\Support\Countries::OPTIONS[$business->country]['name'] ?? $business->country }}" disabled>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.currency') }}</label>
                <input class="loop-input bg-chalk" value="{{ $business->currency }}" disabled>
                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.currency_follows_country') }}</p>
            </div>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.hotline') }}</label>
            <input name="hotline" value="{{ old('hotline', $business->hotline) }}" class="loop-input" placeholder="+255 712 345 678">
            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.hotline_hint') }}</p>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.description') }}</label>
            <textarea name="description" rows="4" class="loop-input">{{ old('description', $business->description) }}</textarea>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $business->is_active))>
            {{ __('loop.business_is_active') }}
        </label>

        <section class="space-y-3 rounded-2xl border border-ink/10 bg-chalk/40 p-4"
                 x-data="{
                    enabled: {{ old('allow_pay_with_points', $business->allow_pay_with_points) ? 'true' : 'false' }},
                    spendDisplay: @js(number_format((int) old('pay_spend_step', $business->pay_spend_step ?: 500))),
                    points: {{ (int) old('pay_points_per_step', $business->pay_points_per_step ?: 2) }},
                    formatSpend() {
                        let raw = String(this.spendDisplay).replace(/[^\d]/g, '');
                        this.spendDisplay = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
                    },
                    spendValue() { return parseInt(String(this.spendDisplay).replace(/,/g, ''), 10) || 0; }
                 }">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ __('loop.pay_with_points_settings') }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.pay_with_points_settings_help') }}</p>
            </div>
            @if (! \App\Support\FeatureFlags::enabled('pay_with_points'))
                <p class="rounded-xl bg-white px-3 py-2 text-sm text-ink-muted">{{ __('loop.feature_disabled_by_platform') }}</p>
            @else
            <label class="flex items-start gap-2 text-sm font-semibold">
                <input type="checkbox" name="allow_pay_with_points" value="1" x-model="enabled" class="mt-0.5 rounded border-ink/20 text-mint-deep focus:ring-mint-deep">
                <span>{{ __('loop.allow_pay_with_points') }}</span>
            </label>
            <div x-show="enabled" x-cloak class="grid gap-3 sm:grid-cols-3">
                <input type="hidden" name="pay_spend_step" :value="spendValue()">
                <div class="sm:col-span-1">
                    <label class="loop-label">{{ __('loop.pay_spend_step') }} ({{ $business->currency }})</label>
                    <input type="text" inputmode="numeric" class="loop-input" x-model="spendDisplay" @input="formatSpend()">
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.pay_points_per_step') }}</label>
                    <input type="number" name="pay_points_per_step" min="1" class="loop-input" x-model="points">
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.pay_points_max_percent') }}</label>
                    <input type="number" name="pay_points_max_percent" min="1" max="100" class="loop-input" value="{{ old('pay_points_max_percent', $business->pay_points_max_percent ?: 50) }}">
                </div>
                <p class="sm:col-span-3 text-sm font-semibold text-ink">
                    <span x-text="points"></span> {{ __('loop.pts') }} ≈
                    <span x-text="spendDisplay || '0'"></span> {{ $business->currency }}
                    · {{ __('loop.pay_max_percent_example', ['percent' => old('pay_points_max_percent', $business->pay_points_max_percent ?: 50)]) }}
                </p>
            </div>
            @endif
        </section>

        <section class="space-y-3 rounded-2xl border border-ink/10 bg-chalk/40 p-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ __('loop.redeem_settings') }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.redeem_settings_help') }}</p>
            </div>
            <label class="flex items-start gap-2 text-sm font-semibold">
                <input type="checkbox" name="allow_same_day_earn_redeem" value="1" class="mt-0.5 rounded border-ink/20 text-mint-deep focus:ring-mint-deep" @checked(old('allow_same_day_earn_redeem', $business->allow_same_day_earn_redeem))>
                <span>{{ __('loop.allow_same_day_earn_redeem') }}</span>
            </label>
            <p class="text-xs text-ink-muted">{{ __('loop.allow_same_day_earn_redeem_help') }}</p>
        </section>

        <button class="loop-btn-mint w-full">{{ __('loop.save_changes') }}</button>
    </form>
</x-app-layout>
