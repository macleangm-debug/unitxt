@php
    $dial = $dial ?? \App\Support\Countries::dial($business->country ?? 'TZ');
    $branchIndex = $branchIndex ?? 1;
    $branchTotal = $branchTotal ?? 1;
    $isOnline = $isOnline ?? $business->isOnline();
    $currency = $currency ?? ($business->currency ?? 'TZS');
@endphp
<x-app-layout>
    <div class="mx-auto max-w-lg">
        <div class="text-center">
            @if ($business->logoUrl())
                <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center overflow-hidden rounded-[1.35rem] bg-ink ring-2 ring-violet/30 sm:h-24 sm:w-24">
                    <img src="{{ $business->logoUrl() }}" alt="" class="h-full w-full object-cover">
                </div>
            @endif
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">Loop</p>
            <h1 class="mt-2 font-display text-3xl font-semibold">{{ $business->name }}</h1>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.onboarding_blurb') }}</p>
        </div>

        <div class="mt-6 flex gap-2">
            @foreach ([1, 2, 3, 4, 5] as $n)
                <div class="h-1.5 flex-1 rounded-full {{ $step >= $n ? 'bg-mint-deep' : 'bg-ink/10' }}"></div>
            @endforeach
        </div>
        <p class="mt-2 text-center text-xs font-semibold text-ink-muted">{{ __('loop.step') }} {{ $step }}/5</p>

        @if ($logoJustSaved)
            <div
                x-data="{ show: true }"
                x-init="setTimeout(() => show = false, 2800)"
                x-show="show"
                x-transition.opacity
                class="mt-6 overflow-hidden rounded-3xl bg-gradient-to-br from-ink to-ink-soft p-6 text-center text-white shadow-[0_20px_60px_rgba(11,31,42,0.15)]"
            >
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-lime text-2xl text-ink">✓</div>
                <p class="mt-4 font-display text-xl font-semibold">{{ __('loop.logo_live') }}</p>
                <p class="mt-1 text-sm text-white/70">{{ __('loop.logo_live_body') }}</p>
            </div>
        @endif

        @if ($step === 1)
            <form
                method="POST"
                action="{{ route('onboarding.logo') }}"
                enctype="multipart/form-data"
                class="mt-6 overflow-hidden rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8"
                x-data="{
                    preview: @js($business->logoUrl()),
                    hadLogo: @js((bool) $business->logo_path),
                    dirty: false,
                    uploading: false,
                    openPicker() {
                        this.$refs.logoInput.value = '';
                        this.$refs.logoInput.click();
                    },
                    pick(e) {
                        const file = e.target.files?.[0];
                        if (!file) return;
                        this.dirty = true;
                        const reader = new FileReader();
                        reader.onload = (ev) => { this.preview = ev.target.result; };
                        reader.readAsDataURL(file);
                    },
                    submit(e) {
                        if (!this.dirty || this.uploading) { e.preventDefault(); return; }
                        this.uploading = true;
                    }
                }"
                @submit="submit"
            >
                @csrf
                <div class="text-center">
                    <h2 class="font-display text-2xl font-semibold">{{ __('loop.add_logo') }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.add_logo_body') }}</p>
                    <p class="mt-2 text-xs text-ink-muted">{{ __('loop.logo_square_hint') }}</p>
                </div>
                <div class="mt-8 flex flex-col items-center">
                    <button
                        type="button"
                        class="relative flex h-36 w-36 items-center justify-center overflow-hidden rounded-[1.75rem] bg-ink ring-4 ring-violet/25 transition hover:ring-lime/40 focus:outline-none focus:ring-lime/50 sm:h-40 sm:w-40"
                        @click="openPicker()"
                        aria-label="{{ __('loop.add_logo') }}"
                    >
                        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(91,46,255,0.35),transparent_55%),radial-gradient(circle_at_80%_80%,rgba(200,255,61,0.18),transparent_45%)]"></div>
                        <template x-if="preview">
                            <img :src="preview" alt="" class="relative z-[1] h-full w-full object-cover">
                        </template>
                        <div x-show="!preview" class="relative z-[1] flex flex-col items-center justify-center gap-3 text-white">
                            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20">
                                <svg class="h-7 w-7 text-lime" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true">
                                    <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                                </svg>
                            </span>
                            <span class="px-3 text-center text-xs font-semibold text-white/80">{{ __('loop.tap_to_add_logo') }}</span>
                        </div>
                        <div x-show="uploading" x-cloak class="absolute inset-0 z-[2] flex flex-col items-center justify-center bg-ink/80 backdrop-blur-sm">
                            <div class="h-10 w-10 animate-spin rounded-full border-2 border-white/30 border-t-lime"></div>
                            <p class="mt-3 text-xs font-semibold text-white">{{ __('loop.uploading') }}</p>
                        </div>
                    </button>
                    <input type="file" name="logo" accept="image/*" class="sr-only" x-ref="logoInput" @change="pick" :required="!hadLogo">
                    <p x-show="hadLogo && !dirty" x-cloak class="mt-4 text-xs text-ink-muted">{{ __('loop.tap_logo_to_replace') }}</p>
                </div>
                <button type="submit" class="loop-btn mt-8 w-full" :disabled="!dirty || uploading" :class="{ 'opacity-60': !dirty || uploading }">
                    <span x-show="!uploading">{{ __('loop.save_logo') }}</span>
                    <span x-show="uploading" x-cloak>{{ __('loop.uploading') }}</span>
                </button>
                @if ($business->logo_path)
                    <a href="{{ route('onboarding.show', ['step' => 2]) }}" class="mt-3 block text-center text-sm font-semibold text-ink-muted">{{ __('loop.keep_this_logo') }} →</a>
                @endif
            </form>
        @elseif ($step === 2)
            <form
                method="POST"
                action="{{ route('onboarding.branches') }}"
                class="mt-6 overflow-hidden rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8"
                x-data="{ presence: @js(old('presence', $business->presence ?? 'physical')) }"
            >
                @csrf
                <div class="text-center">
                    <h2 class="mt-2 font-display text-2xl font-semibold">{{ __('loop.how_you_sell') }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.how_you_sell_body') }}</p>
                </div>
                <div class="mt-6 grid gap-3">
                    <label class="cursor-pointer rounded-2xl border px-4 py-4 transition has-[:checked]:border-violet has-[:checked]:bg-violet-soft/50"
                           :class="presence === 'physical' ? 'border-violet bg-violet-soft/50' : 'border-ink/10 bg-white'">
                        <input type="radio" name="presence" value="physical" class="sr-only" x-model="presence">
                        <span class="block font-display text-lg font-semibold">{{ __('loop.presence_physical') }}</span>
                        <span class="mt-1 block text-sm text-ink-muted">{{ __('loop.presence_physical_body') }}</span>
                    </label>
                    <label class="cursor-pointer rounded-2xl border px-4 py-4 transition has-[:checked]:border-violet has-[:checked]:bg-violet-soft/50"
                           :class="presence === 'online' ? 'border-violet bg-violet-soft/50' : 'border-ink/10 bg-white'">
                        <input type="radio" name="presence" value="online" class="sr-only" x-model="presence">
                        <span class="block font-display text-lg font-semibold">{{ __('loop.presence_online') }}</span>
                        <span class="mt-1 block text-sm text-ink-muted">{{ __('loop.presence_online_body') }}</span>
                    </label>
                </div>
                <div class="mt-6" x-show="presence === 'physical'" x-cloak>
                    <label class="loop-label text-center">{{ __('loop.branches') }}</label>
                    <input
                        type="number"
                        min="1"
                        max="50"
                        name="branch_count"
                        value="{{ old('branch_count', $business->branch_count ?: 1) }}"
                        class="loop-input text-center text-2xl font-display font-semibold"
                        x-bind:disabled="presence !== 'physical'"
                        :required="presence === 'physical'"
                    >
                </div>
                <input type="hidden" name="branch_count" value="1" x-bind:disabled="presence !== 'online'">
                <button class="loop-btn mt-8 w-full">{{ __('loop.next') }}</button>
                <a href="{{ route('onboarding.show', ['step' => 1]) }}" class="mt-3 block text-center text-sm font-semibold text-ink-muted">{{ __('loop.back_to_logo') }}</a>
            </form>
        @elseif ($step === 3)
            <form
                method="POST"
                action="{{ route('onboarding.shop') }}"
                class="mt-6 overflow-hidden rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8"
            >
                @csrf
                <div class="text-center">
                    <h2 class="font-display text-2xl font-semibold">
                        @if ($isOnline)
                            {{ __('loop.online_presence_title') }}
                        @else
                            {{ $branchTotal > 1 ? __('loop.branch_n_of', ['n' => $branchIndex, 'total' => $branchTotal]) : __('loop.first_shop') }}
                        @endif
                    </h2>
                    <p class="mt-2 text-sm text-ink-muted">
                        {{ $isOnline ? __('loop.online_presence_body') : __('loop.branch_location_body') }}
                    </p>
                    <p class="mt-2 text-sm font-semibold text-ink">{{ $business->name }}</p>
                </div>
                <div class="mt-6 space-y-4">
                    @unless ($isOnline)
                        <x-city-sheet-select
                            name="city"
                            :label="__('loop.city')"
                            :value="old('city', $business->city ?: ($cities[0] ?? ''))"
                            :cities="$cities"
                            :country="$business->country ?? 'TZ'"
                            :required="true"
                        />

                        <div>
                            <label class="loop-label">{{ __('loop.address') }}</label>
                            <input
                                type="text"
                                name="address"
                                value="{{ old('address') }}"
                                class="loop-input"
                                required
                                placeholder="{{ __('loop.address_directive_placeholder') }}"
                            >
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.address_directive_hint') }}</p>
                            <x-input-error :messages="$errors->get('address')" class="mt-1" />
                        </div>
                    @else
                        <input type="hidden" name="city" value="{{ old('city', $business->city ?: 'Online') }}">
                        <input type="hidden" name="address" value="">
                        <p class="rounded-2xl bg-violet-soft/50 px-4 py-3 text-sm text-ink-muted">{{ __('loop.online_no_address_note') }}</p>
                    @endunless

                    @if ($branchIndex === 1)
                        <div>
                            <label class="loop-label">{{ __('loop.hotline') }}</label>
                            <x-phone-field
                                name="hotline"
                                :dial="$dial"
                                :value="old('hotline', $business->hotline ? preg_replace('/^\+\d+\s*/', '', (string) $business->hotline) : '')"
                            />
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.onboarding_hotline_hint') }}</p>
                        </div>
                    @endif
                </div>
                <button class="loop-btn mt-8 w-full">
                    {{ (! $isOnline && $branchIndex < $branchTotal) ? __('loop.next_branch') : __('loop.next') }}
                </button>
            </form>
        @elseif ($step === 4)
            <div
                class="mt-6"
                x-data="{
                    selected: null,
                    name: '',
                    description: '',
                    type: 'earn',
                    spendStep: 1000,
                    pointsPerStep: 2,
                    bonusPoints: 10,
                    productName: '',
                    defaults: @js(collect($groupedTemplates)->flatMap(fn ($g) => $g['templates'])->mapWithKeys(fn ($t, $k) => [$k => [
                        'name' => $t['name'],
                        'description' => $t['description'],
                        'type' => $t['type'],
                        'spend_step' => $t['spend_step'] ?? 1000,
                        'points_per_step' => $t['points_per_step'] ?? 2,
                        'bonus_points' => $t['bonus_points'] ?? 0,
                    ]])->all()),
                    pick(key) {
                        const t = this.defaults[key];
                        if (!t) return;
                        this.selected = key;
                        this.name = t.name;
                        this.description = t.description;
                        this.type = t.type;
                        this.spendStep = t.spend_step || 1000;
                        this.pointsPerStep = t.points_per_step || 2;
                        this.bonusPoints = t.bonus_points || 10;
                        this.productName = '';
                    }
                }"
            >
                <div class="text-center">
                    <h2 class="font-display text-2xl font-semibold">{{ __('loop.create_first_campaign') }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.create_first_campaign_body') }}</p>
                </div>

                @foreach ($groupedTemplates as $intention => $group)
                    <section class="mt-6">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ $group['label'] }}</h3>
                        <div class="mt-3 grid gap-3">
                            @foreach ($group['templates'] as $key => $template)
                                <button
                                    type="button"
                                    class="w-full rounded-3xl border border-ink/10 bg-white/90 p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-violet hover:bg-violet-soft/40"
                                    :class="selected === '{{ $key }}' ? 'border-violet ring-2 ring-violet/30' : ''"
                                    @click="pick(@js($key))"
                                >
                                    <p class="font-display text-lg font-semibold">{{ $template['name'] }}</p>
                                    <p class="mt-2 text-sm text-ink-muted">{{ $template['description'] }}</p>
                                </button>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <p class="mt-6 text-center text-xs text-ink-muted">{{ __('loop.bonus_campaigns_later_note') }}</p>

                <div x-show="selected" x-cloak class="fixed inset-0 z-50 flex items-end justify-center sm:items-center px-0 sm:px-4" @keydown.escape.window="selected=null">
                    <div class="absolute inset-0 bg-ink/50" @click="selected=null"></div>
                    <div class="relative w-full max-w-md rounded-t-[1.75rem] bg-white p-6 shadow-2xl sm:rounded-3xl sm:p-8">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.confirm_campaign') }}</p>
                        <p class="mt-2 font-display text-2xl font-bold" x-text="name"></p>
                        <p class="mt-2 text-sm text-ink-muted" x-text="description"></p>

                        <form method="POST" action="{{ route('onboarding.campaign') }}" class="mt-5 space-y-4">
                            @csrf
                            <input type="hidden" name="template" :value="selected">

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="loop-label">{{ __('loop.spend_amount') }} ({{ $currency }})</label>
                                    <input type="number" name="spend_step" min="100" step="100" class="loop-input" x-model="spendStep" required>
                                </div>
                                <div>
                                    <label class="loop-label">{{ __('loop.points_earned') }}</label>
                                    <input type="number" name="points_per_step" min="1" class="loop-input" x-model="pointsPerStep" required>
                                </div>
                            </div>
                            <p class="text-xs text-ink-muted">
                                {{ __('loop.earn_rate_example_prefix') }}
                                <span class="font-semibold text-ink" x-text="pointsPerStep"></span>
                                {{ __('loop.pts') }} /
                                <span class="font-semibold text-ink" x-text="Number(spendStep).toLocaleString()"></span>
                                {{ $currency }}
                            </p>

                            <div x-show="type === 'product_push'" x-cloak class="space-y-3 rounded-2xl border border-violet/20 bg-violet-soft/40 p-4">
                                <div>
                                    <label class="loop-label">{{ __('loop.featured_product_name') }}</label>
                                    <input type="text" name="featured_product_name" class="loop-input" x-model="productName" :required="type === 'product_push'" placeholder="{{ __('loop.featured_product_placeholder') }}">
                                    <p class="mt-1 text-xs text-ink-muted">{{ __('loop.featured_product_till_hint') }}</p>
                                </div>
                                <div>
                                    <label class="loop-label">{{ __('loop.featured_bonus_points') }}</label>
                                    <input type="number" name="bonus_points" min="1" class="loop-input" x-model="bonusPoints" :required="type === 'product_push'">
                                </div>
                            </div>

                            <button class="loop-btn w-full text-base">{{ __('loop.save_campaign') }}</button>
                            <button type="button" class="w-full text-sm font-semibold text-ink-muted" @click="selected=null">{{ __('loop.back') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('onboarding.offers') }}" class="mt-6" x-data="{ count: 1 }">
                @csrf
                <div class="text-center">
                    <h2 class="font-display text-2xl font-semibold">{{ __('loop.create_first_offer') }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.create_first_offer_body') }}</p>
                    @if ($earnCampaign)
                        <p class="mt-3 rounded-full bg-mint-soft px-3 py-1 text-xs font-semibold text-mint-deep">{{ $earnCampaign->name }}</p>
                    @endif
                </div>

                <div class="mt-6 space-y-3">
                    @foreach ($offerTemplates as $offer)
                        <label class="flex cursor-pointer items-start gap-3 rounded-3xl border border-ink/10 bg-white/90 p-4 transition has-[:checked]:border-mint-deep has-[:checked]:bg-mint-soft/40"
                               @change="count = [...$el.closest('form').querySelectorAll('input[name=\'offers[]\']:checked')].length">
                            <input type="checkbox" name="offers[]" value="{{ $offer['key'] }}" class="mt-1 rounded border-ink/20 text-mint-deep focus:ring-mint-deep"
                                   @checked($loop->first)>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-start justify-between gap-2">
                                    <span class="font-display text-base font-semibold">{{ $offer['name'] }}</span>
                                    <span class="shrink-0 rounded-lg bg-ink px-2 py-1 text-xs font-semibold text-lime">{{ $offer['points_cost'] }} pts</span>
                                </span>
                                <span class="mt-1 block text-sm text-ink-muted">{{ $offer['description'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('offers')" class="mt-3" />
                <button class="loop-btn mt-6 w-full" :disabled="count < 1" :class="{ 'opacity-60': count < 1 }">{{ __('loop.finish_onboarding') }}</button>
            </form>
        @endif
    </div>
</x-app-layout>
