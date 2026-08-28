@php
    $currency = $currency ?? ($business->currency ?? 'TZS');
    $starter = $starter ?? [];
    $spendChoices = $spendChoices ?? [5000, 10000, 20000];
    $defaultSpend = (int) ($starter['spend_step'] ?? 5000);
    $hasLogo = filled($business->logo_path);
    $dial = \App\Support\Countries::dial($business->country ?? 'TZ');
    $hotlineLocal = $business->hotline
        ? preg_replace('/^\+\d+\s*/', '', (string) $business->hotline)
        : '';
    $choseShop = $business->shops->isNotEmpty();
    $defaultPresence = old('presence', $choseShop ? (string) $business->presence : '');
    $defaultLocations = old('locations', $choseShop
        ? (((int) ($business->branch_count ?: 1) > 1) ? 'more' : 'just_one')
        : '');
    $defaultBranchCount = (int) old('branch_count', $choseShop ? max(1, (int) $business->branch_count) : 1);
    $shopPane = 1;
    if ($errors->has('presence')) {
        $shopPane = 2;
    } elseif ($errors->has('city') || $errors->has('address') || $errors->has('hotline')) {
        $shopPane = 3;
    } elseif ($errors->has('locations') || $errors->has('branch_count')) {
        $shopPane = 4;
    }
@endphp
<x-app-layout>
    <div class="loop-onboard mx-auto max-w-lg" data-loop-no-skeleton>
        <div class="text-center">
            @if ($hasLogo)
                <div class="mx-auto mb-4 flex h-16 w-full max-w-[14rem] items-center justify-center overflow-hidden rounded-2xl bg-ink ring-2 ring-violet/25">
                    <img src="{{ $business->logoUrl() }}" alt="" class="max-h-full max-w-full object-contain p-2">
                </div>
            @endif
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">Loop</p>
            <h1 class="mt-2 font-display text-3xl font-semibold">{{ $business->name }}</h1>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.onboarding_blurb') }}</p>
        </div>

        <div class="mt-6 flex gap-2">
            @foreach (range(1, $totalSteps ?? 3) as $n)
                <div class="h-1.5 flex-1 rounded-full {{ $step >= $n ? 'bg-mint-deep' : 'bg-ink/10' }}"></div>
            @endforeach
        </div>
        <p class="mt-2 text-center text-xs font-semibold text-ink-muted">{{ __('loop.step') }} {{ $step }}/{{ $totalSteps ?? 3 }}</p>

        @if ($step === 1)
            <form
                method="POST"
                action="{{ route('onboarding.shop') }}"
                enctype="multipart/form-data"
                class="mt-6 overflow-hidden rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8"
                data-loop-no-skeleton
                x-data="{
                    hadLogo: @js($hasLogo),
                    dirty: false,
                    uploading: false,
                    pane: {{ (int) $shopPane }},
                    logoError: false,
                    presenceError: false,
                    locationsError: false,
                    cityError: false,
                    presence: @js($defaultPresence),
                    locations: @js($defaultLocations),
                    branchCount: {{ max(1, $defaultBranchCount) }},
                    lastPane() {
                        return (this.presence === 'physical' || this.presence === 'both') ? 4 : 3;
                    },
                    extraCount() {
                        if (this.presence === 'online' || this.locations !== 'more') return 0;
                        return Math.min(3, Math.max(1, this.branchCount - 1));
                    },
                    pickPresence(value) {
                        this.presence = value;
                        this.presenceError = false;
                        if (value === 'online') {
                            this.locations = 'just_one';
                            this.branchCount = 1;
                        }
                    },
                    pickLocations(value) {
                        this.locations = value;
                        this.locationsError = false;
                        if (value === 'just_one') this.branchCount = 1;
                        if (value === 'more' && this.branchCount < 2) this.branchCount = 2;
                    },
                    pickCount(n) {
                        this.locations = 'more';
                        this.locationsError = false;
                        this.branchCount = n;
                    },
                    nextPane() {
                        if (this.pane === 1) {
                            if (!this.hadLogo && !this.dirty) { this.logoError = true; return false; }
                            this.logoError = false;
                        }
                        if (this.pane === 2) {
                            if (!this.presence) { this.presenceError = true; return false; }
                            this.presenceError = false;
                        }
                        if (this.pane === 3) {
                            this.cityError = false;
                            const city = this.$el.querySelector('[name=city]');
                            if (city && !String(city.value || '').trim()) {
                                this.cityError = true;
                                return false;
                            }
                            if (this.presence === 'physical' || this.presence === 'both') {
                                const addr = this.$refs.address;
                                if (addr && !String(addr.value || '').trim()) {
                                    addr.reportValidity();
                                    return false;
                                }
                            }
                        }
                        if (this.pane === 4 && !this.locations) {
                            this.locationsError = true;
                            return false;
                        }
                        return true;
                    },
                    submit(e) {
                        if (this.uploading) { e.preventDefault(); return; }
                        if (this.pane < this.lastPane()) {
                            e.preventDefault();
                            if (this.nextPane()) this.pane++;
                            return;
                        }
                        if (!this.nextPane()) { e.preventDefault(); return; }
                        if (!this.hadLogo && !this.dirty) {
                            e.preventDefault();
                            this.pane = 1;
                            this.logoError = true;
                            return;
                        }
                        this.uploading = true;
                    }
                }"
                @logo-picked="dirty = true"
                @submit="submit"
            >
                @csrf
                <div class="text-center">
                    <h2 class="font-display text-2xl font-semibold">{{ __('loop.your_shop') }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.your_shop_body') }}</p>
                    <div class="mt-4 flex justify-center gap-1.5">
                        <template x-for="n in lastPane()" :key="n">
                            <span class="h-1.5 w-8 rounded-full" :class="pane >= n ? 'bg-mint-deep' : 'bg-ink/10'"></span>
                        </template>
                    </div>
                </div>

                <div class="mt-8 flex flex-col items-center" x-show="pane === 1">
                    <x-logo-placeholder
                        name="logo"
                        :preview="$business->logoUrl()"
                        :required="false"
                        :hint="__('loop.logo_square_hint')"
                    >
                        <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                    </x-logo-placeholder>
                    <p class="mt-2 text-sm text-coral" x-show="logoError" x-cloak>{{ __('loop.logo_required_body') }}</p>
                </div>

                <div class="mt-8" x-show="pane === 2" x-cloak>
                    <p class="loop-label">{{ __('loop.how_you_sell') }}</p>
                    <input type="hidden" name="presence" :value="presence">
                    <div class="mt-2 grid gap-2">
                        @foreach (['physical' => __('loop.presence_physical'), 'online' => __('loop.presence_online'), 'both' => __('loop.presence_both')] as $key => $label)
                            <button
                                type="button"
                                class="w-full rounded-2xl border px-4 py-3 text-left transition"
                                :class="presence === @js($key) ? 'border-violet bg-violet-soft/50' : 'border-ink/10 bg-white'"
                                @click="pickPresence(@js($key))"
                            >
                                <span class="block font-semibold">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('presence')" class="mt-1" />
                    <p class="mt-2 text-sm text-coral" x-show="presenceError" x-cloak>{{ __('loop.how_you_sell') }}</p>
                </div>

                <div class="mt-6" x-show="pane === 3" x-cloak>
                    <x-city-sheet-select
                        name="city"
                        :label="__('loop.city')"
                        :value="old('city', $business->city ?: ($cities[0] ?? ''))"
                        :cities="$cities"
                        :country="$business->country ?? 'TZ'"
                        :required="false"
                    />
                    <p class="mt-2 text-sm text-coral" x-show="cityError" x-cloak>{{ __('loop.pick_city') }}</p>

                    <div class="mt-6" x-show="presence === 'physical' || presence === 'both'">
                        <label class="loop-label">{{ __('loop.address') }}</label>
                        <input
                            x-ref="address"
                            name="address"
                            value="{{ old('address', $business->shops->first()?->address) }}"
                            class="loop-input"
                            placeholder="{{ __('loop.address_directive_placeholder') }}"
                            :required="pane === 3 && (presence === 'physical' || presence === 'both')"
                        >
                        <p class="mt-1 text-xs text-ink-muted">{{ __('loop.address_directive_hint') }}</p>
                        <x-input-error :messages="$errors->get('address')" class="mt-1" />
                    </div>
                    <p class="mt-4 rounded-2xl bg-violet-soft/50 px-4 py-3 text-sm text-ink-muted" x-show="presence === 'online'">{{ __('loop.online_no_address_note') }}</p>

                    <div class="mt-6">
                        <label class="loop-label">{{ __('loop.hotline') }}</label>
                        <x-phone-field
                            name="hotline"
                            :dial="$dial"
                            hidden-dial-name="hotline_country_code"
                            :value="old('hotline', $hotlineLocal)"
                        />
                        @if (filled($business->hotline))
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.hotline_already_on_file') }}</p>
                        @else
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.onboarding_hotline_hint') }}</p>
                        @endif
                        <x-input-error :messages="$errors->get('hotline')" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6" x-show="pane === 4 && (presence === 'physical' || presence === 'both')" x-cloak>
                    <p class="loop-label">{{ __('loop.how_many_locations') }}</p>
                    <input type="hidden" name="locations" :value="locations">
                    <input type="hidden" name="branch_count" :value="branchCount">
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <button type="button" class="rounded-2xl border px-4 py-3 text-left font-semibold" :class="locations === 'just_one' ? 'border-violet bg-violet-soft/50' : 'border-ink/10 bg-white'" @click="pickLocations('just_one')">{{ __('loop.just_this_location') }}</button>
                        <button type="button" class="rounded-2xl border px-4 py-3 text-left font-semibold" :class="locations === 'more' ? 'border-violet bg-violet-soft/50' : 'border-ink/10 bg-white'" @click="pickLocations('more')">{{ __('loop.more_than_one_location') }}</button>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2" x-show="locations === 'more'" x-cloak>
                        @foreach ([2, 3, 4] as $n)
                            <button type="button" class="rounded-full px-4 py-2 text-sm font-semibold" :class="branchCount === {{ $n }} ? 'bg-ink text-white' : 'bg-chalk text-ink'" @click="pickCount({{ $n }})">{{ $n }}</button>
                        @endforeach
                        <button type="button" class="rounded-full px-4 py-2 text-sm font-semibold" :class="branchCount >= 5 ? 'bg-ink text-white' : 'bg-chalk text-ink'" @click="pickCount(Math.max(5, branchCount))">{{ __('loop.five_plus_locations') }}</button>
                    </div>
                    <div class="mt-3" x-show="locations === 'more' && branchCount >= 5" x-cloak>
                        <label class="loop-label">{{ __('loop.how_many_branches') }}</label>
                        <input type="number" min="5" max="50" class="loop-input" x-model.number="branchCount">
                    </div>
                    <p class="mt-3 text-xs text-ink-muted" x-show="locations === 'more'" x-cloak>{{ __('loop.setup_other_locations_later') }}</p>
                    <p class="mt-2 text-sm text-coral" x-show="locationsError" x-cloak>{{ __('loop.how_many_locations') }}</p>
                </div>

                <div class="mt-8 flex flex-col gap-3">
                    <button type="submit" class="loop-btn w-full" :disabled="uploading" :class="{ 'opacity-60': uploading }">
                        <span x-show="pane < lastPane()">{{ __('loop.continue') }}</span>
                        <span x-show="pane >= lastPane()" x-cloak>{{ __('loop.next') }}</span>
                    </button>
                    <button type="button" class="text-sm font-semibold text-ink-muted" x-show="pane > 1" x-cloak @click="pane--">{{ __('loop.back') }}</button>
                </div>
            </form>
        @elseif ($step === 2)
            <form
                method="POST"
                action="{{ route('onboarding.campaign') }}"
                class="mt-6 overflow-hidden rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8"
                data-loop-no-skeleton
                x-data="{
                    spend: {{ $defaultSpend }},
                    other: {{ in_array($defaultSpend, $spendChoices, true) ? 'false' : 'true' }},
                    otherValue: '{{ in_array($defaultSpend, $spendChoices, true) ? '' : number_format($defaultSpend) }}',
                    visits: {{ (int) ($starter['visits'] ?? 10) }},
                    reward: @js($starter['reward_name'] ?? __('loop.offer_type_free_name')),
                    currency: @js($currency),
                    pick(amount) {
                        this.other = false;
                        this.spend = amount;
                    },
                    formatOther() {
                        let raw = String(this.otherValue).replace(/[^\d]/g, '');
                        this.otherValue = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
                        this.spend = parseInt(raw, 10) || 0;
                    },
                    useOther() {
                        this.other = true;
                        this.$nextTick(() => this.$refs.otherAmount?.focus());
                    },
                    spendLabel() {
                        return Number(this.spend || 0).toLocaleString();
                    }
                }"
            >
                @csrf
                <input type="hidden" name="typical_spend" value="{{ $defaultSpend }}" x-bind:value="spend">
                <div class="text-center">
                    <h2 class="font-display text-2xl font-semibold">{{ __('loop.typical_spend_title') }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.typical_spend_body') }}</p>
                </div>
                <div class="mt-6 grid gap-3">
                    @foreach ($spendChoices as $amount)
                        <button
                            type="button"
                            class="w-full rounded-2xl border px-4 py-4 text-left transition"
                            :class="!other && spend === {{ $amount }} ? 'border-violet bg-violet-soft/50' : 'border-ink/10 bg-white'"
                            @click="pick({{ $amount }})"
                        >
                            <span class="block font-display text-lg font-semibold">{{ $currency }} {{ number_format($amount) }}</span>
                        </button>
                    @endforeach
                    <button
                        type="button"
                        class="w-full rounded-2xl border px-4 py-4 text-left transition"
                        :class="other ? 'border-violet bg-violet-soft/50' : 'border-ink/10 bg-white'"
                        @click="useOther()"
                    >
                        <span class="block font-display text-lg font-semibold">{{ __('loop.typical_spend_other') }}</span>
                    </button>
                    <div x-show="other" x-cloak class="mt-1">
                        <label class="loop-label">{{ $currency }}</label>
                        <input type="text" inputmode="numeric" class="loop-input font-display text-xl font-semibold" x-ref="otherAmount" x-model="otherValue" @input="formatOther()" placeholder="0">
                    </div>
                </div>

                <div class="mt-8 rounded-[1.5rem] bg-ink px-5 py-5 text-white">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-lime">{{ __('loop.starter_recommend_title') }}</p>
                    <p class="mt-3 font-display text-xl font-semibold leading-snug">
                        {{ __('loop.starter_every') }} {{ $currency }}
                        <span x-text="spendLabel()">{{ number_format($defaultSpend) }}</span>
                        = 1 {{ __('loop.pts') }}
                    </p>
                    <p class="mt-2 font-display text-xl font-semibold leading-snug">
                        <span x-text="visits">{{ (int) ($starter['visits'] ?? 10) }}</span>
                        {{ __('loop.pts') }} = {{ $starter['reward_name'] ?? __('loop.offer_type_free_name') }}
                    </p>
                </div>

                <button class="loop-btn mt-6 w-full" :disabled="spend < 1" :class="{ 'opacity-60': spend < 1 }">{{ __('loop.use_this_campaign') }}</button>
                <p class="mt-3 text-center text-xs text-ink-muted">{{ __('loop.starter_change_hint') }}</p>
                <a href="{{ route('onboarding.show', ['step' => 1]) }}" class="mt-3 block text-center text-sm font-semibold text-ink-muted">{{ __('loop.back') }}</a>
            </form>
        @else
            <form
                method="POST"
                action="{{ route('onboarding.offers') }}"
                class="mt-6 overflow-hidden rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8"
                data-loop-no-skeleton
            >
                @csrf
                <div class="text-center">
                    <h2 class="font-display text-2xl font-semibold">{{ __('loop.what_they_get_title') }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.what_they_get_body') }}</p>
                </div>
                <div class="mt-6 rounded-[1.5rem] bg-violet px-5 py-5 text-white">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-lime">{{ __('loop.starter_recommend_title') }}</p>
                    <p class="mt-2 font-display text-2xl font-semibold">{{ $starter['reward_name'] }}</p>
                    <p class="mt-1 text-sm text-white/75">{{ number_format((int) $starter['points_cost']) }} {{ __('loop.pts') }} · {{ __('loop.starter_visits_line', ['count' => $starter['visits']]) }}</p>
                </div>
                <div class="mt-5">
                    <label class="loop-label">{{ __('loop.offer_name') }}</label>
                    <input name="name" value="{{ old('name', $starter['reward_name']) }}" class="loop-input font-display text-lg font-semibold" maxlength="120">
                    <p class="mt-1 text-xs text-ink-muted">{{ __('loop.starter_rename_hint') }}</p>
                </div>
                <button class="loop-btn mt-8 w-full">{{ __('loop.youre_live') }}</button>
                <a href="{{ route('onboarding.show', ['step' => 2]) }}" class="mt-3 block text-center text-sm font-semibold text-ink-muted">{{ __('loop.back') }}</a>
            </form>
        @endif
    </div>
</x-app-layout>
