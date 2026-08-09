@php
    $dial = $dial ?? \App\Support\Countries::dial($business->country ?? 'TZ');
    $branchIndex = $branchIndex ?? 1;
    $branchTotal = $branchTotal ?? 1;
@endphp
<x-app-layout>
    <div class="mx-auto max-w-lg">
        <div class="text-center">
            @if ($business->logo_path)
                <div class="mx-auto mb-4 flex h-16 w-full max-w-[14rem] items-center justify-center overflow-hidden rounded-2xl bg-chalk ring-2 ring-mint-deep/20">
                    <img src="{{ $business->logoUrl() }}" alt="" class="max-h-full max-w-full object-contain p-2">
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
                    fileName: '',
                    preview: null,
                    uploading: false,
                    pick(e) {
                        const file = e.target.files?.[0];
                        if (!file) return;
                        this.fileName = file.name;
                        const reader = new FileReader();
                        reader.onload = (ev) => { this.preview = ev.target.result; };
                        reader.readAsDataURL(file);
                    },
                    submit(e) {
                        if (!this.fileName || this.uploading) { e.preventDefault(); return; }
                        this.uploading = true;
                    }
                }"
                @submit="submit"
            >
                @csrf
                <div class="text-center">
                    <h2 class="font-display text-2xl font-semibold">{{ __('loop.add_logo') }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.add_logo_body') }}</p>
                    <p class="mt-2 text-xs font-semibold text-violet">{{ __('loop.logo_required_body') }}</p>
                    <p class="mt-1 text-xs text-ink-muted">{{ __('loop.add_logo_wide_hint') }}</p>
                    <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                </div>
                <div class="mt-8 flex flex-col items-center">
                    {{-- Wide frame so horizontal wordmarks fit without cropping --}}
                    <div class="relative flex h-32 w-full max-w-sm items-center justify-center overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-chalk via-white to-mint-soft ring-4 ring-mint-deep/15">
                        <template x-if="preview"><img :src="preview" alt="" class="max-h-full max-w-full object-contain p-4"></template>
                        <div x-show="!preview" class="flex h-full w-full items-center justify-center font-display text-4xl text-mint-deep">{{ mb_substr($business->name, 0, 1) }}</div>
                        <div x-show="uploading" x-cloak class="absolute inset-0 flex flex-col items-center justify-center bg-ink/70 backdrop-blur-sm">
                            <div class="h-10 w-10 animate-spin rounded-full border-2 border-white/30 border-t-lime"></div>
                            <p class="mt-3 text-xs font-semibold text-white">{{ __('loop.uploading') }}</p>
                        </div>
                    </div>
                    <label class="mt-6 inline-flex cursor-pointer items-center justify-center rounded-xl bg-mint-deep px-6 py-3 text-sm font-semibold text-white transition hover:bg-ink">
                        <span x-text="fileName ? '{{ __('loop.change_image') }}' : '{{ __('loop.upload') }}'"></span>
                        <input type="file" name="logo" accept="image/*" class="sr-only" required @change="pick">
                    </label>
                </div>
                <button type="submit" class="loop-btn mt-8 w-full" :disabled="!fileName || uploading" :class="{ 'opacity-60': !fileName || uploading }">
                    <span x-show="!uploading">{{ __('loop.save_logo') }}</span>
                    <span x-show="uploading" x-cloak>{{ __('loop.uploading') }}</span>
                </button>
            </form>
        @elseif ($step === 2)
            <form method="POST" action="{{ route('onboarding.branches') }}" class="mt-6 overflow-hidden rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8">
                @csrf
                <div class="text-center">
                    <h2 class="mt-2 font-display text-2xl font-semibold">{{ __('loop.how_many_branches') }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.how_many_branches_body') }}</p>
                </div>
                <div class="mt-8">
                    <label class="loop-label text-center">{{ __('loop.branches') }}</label>
                    <input type="number" min="1" max="50" name="branch_count" value="{{ old('branch_count', $business->branch_count ?: 1) }}" class="loop-input text-center text-2xl font-display font-semibold" required>
                </div>
                <button class="loop-btn mt-8 w-full">{{ __('loop.next') }}</button>
            </form>
        @elseif ($step === 3)
            <form
                method="POST"
                action="{{ route('onboarding.shop') }}"
                class="mt-6 overflow-hidden rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8"
                x-data="{
                    city: @js(old('city', $business->city ?: ($cities[0] ?? ''))),
                    address: @js(old('address', '')),
                    areasByCity: @js($areasByCity ?? []),
                    get areas() { return this.areasByCity[this.city] || []; }
                }"
            >
                @csrf
                <div class="text-center">
                    <h2 class="font-display text-2xl font-semibold">
                        {{ $branchTotal > 1 ? __('loop.branch_n_of', ['n' => $branchIndex, 'total' => $branchTotal]) : __('loop.first_shop') }}
                    </h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.branch_location_body') }}</p>
                    <p class="mt-2 text-sm font-semibold text-ink">{{ $business->name }}</p>
                </div>
                <div class="mt-6 space-y-4">
                    <x-city-sheet-select
                        name="city"
                        :label="__('loop.city')"
                        :value="old('city', $business->city ?: ($cities[0] ?? ''))"
                        :cities="$cities"
                        :country="$business->country ?? 'TZ'"
                        :required="true"
                    />

                    <div
                        x-data="{
                            open: false,
                            value: address,
                            q: '',
                            syncCity() {
                                const el = $root.closest('form')?.querySelector('input[name=city]');
                                if (el?.value) city = el.value;
                            },
                            get filtered() {
                                const list = areas;
                                const q = this.q.trim().toLowerCase();
                                if (!q) return list;
                                return list.filter(a => a.toLowerCase().includes(q));
                            },
                            pick(a) { this.value = a; address = a; this.open = false; this.q = ''; }
                        }"
                        x-effect="value = address"
                        class="relative"
                    >
                        <label class="loop-label">{{ __('loop.address') }}</label>
                        <input type="hidden" name="address" :value="value">
                        <button type="button" class="loop-input flex w-full items-center justify-between text-left" @click="syncCity(); open = true">
                            <span x-text="value || '{{ __('loop.pick_area_or_type') }}'" :class="value ? 'text-ink' : 'text-ink-muted'"></span>
                            <span class="text-mint-deep">▾</span>
                        </button>
                        <div x-show="open" x-cloak class="fixed inset-0 z-50" @keydown.escape.window="open=false">
                            <div class="absolute inset-0 bg-ink/45" @click="open=false"></div>
                            <div class="absolute inset-x-0 bottom-0 max-h-[80vh] overflow-hidden rounded-t-3xl bg-white shadow-2xl" @click.stop>
                                <div class="mx-auto mt-3 h-1 w-10 rounded-full bg-ink/15"></div>
                                <div class="border-b border-ink/5 p-4">
                                    <p class="font-display text-lg font-semibold">{{ __('loop.address') }}</p>
                                    <input type="text" x-model="value" @input="address = value" placeholder="{{ __('loop.address_custom_hint') }}" class="loop-input mt-3 !py-2 text-sm">
                                    <input type="search" x-model="q" placeholder="{{ __('loop.search') }}" class="loop-input mt-2 !py-2 text-sm" x-show="filtered.length || areas.length">
                                </div>
                                <div class="max-h-[45vh] overflow-y-auto p-2 pb-8">
                                    <template x-for="area in filtered" :key="area">
                                        <button type="button" class="flex w-full rounded-xl px-3 py-3 text-left text-sm font-medium hover:bg-mint-soft" @click="pick(area)" x-text="area"></button>
                                    </template>
                                    <button type="button" class="mt-2 w-full rounded-xl bg-violet px-3 py-3 text-sm font-semibold text-white" @click="address = value; open=false">{{ __('loop.use_this_address') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($branchIndex === 1)
                        <div>
                            <label class="loop-label">{{ __('loop.hotline') }}</label>
                            <div class="mt-1 flex overflow-hidden rounded-2xl border border-ink/10 bg-white shadow-sm focus-within:border-violet focus-within:ring-1 focus-within:ring-violet">
                                <span class="flex items-center border-r border-ink/10 bg-chalk px-3 text-sm font-semibold text-ink">{{ $dial }}</span>
                                <input name="hotline" value="{{ old('hotline', $business->hotline ? preg_replace('/^\+\d+\s*/', '', (string) $business->hotline) : '') }}" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-sm focus:ring-0" placeholder="712 345 678">
                            </div>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.onboarding_hotline_hint') }}</p>
                        </div>
                    @endif
                </div>
                <button class="loop-btn mt-8 w-full">
                    {{ $branchIndex < $branchTotal ? __('loop.next_branch') : __('loop.next') }}
                </button>
            </form>
        @elseif ($step === 4)
            <div class="mt-6" x-data="{ selected: null, name: '', description: '' }">
                <div class="text-center">
                    <h2 class="font-display text-2xl font-semibold">{{ __('loop.pick_campaign') }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.pick_campaign_first_body') }}</p>
                </div>

                @foreach ($groupedTemplates as $intention => $group)
                    <section class="mt-6">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ $group['label'] }}</h3>
                        <div class="mt-3 grid gap-3">
                            @foreach ($group['templates'] as $key => $template)
                                <button
                                    type="button"
                                    class="w-full rounded-3xl border border-ink/10 bg-white/90 p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-mint-deep hover:bg-mint-soft/40"
                                    @click="selected='{{ $key }}'; name=@js($template['name']); description=@js($template['description'])"
                                >
                                    <p class="font-display text-lg font-semibold">{{ $template['name'] }}</p>
                                    <p class="mt-2 text-sm text-ink-muted">{{ $template['description'] }}</p>
                                </button>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <div x-show="selected" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4" @keydown.escape.window="selected=null">
                    <div class="absolute inset-0 bg-ink/50" @click="selected=null"></div>
                    <div class="relative w-full max-w-md rounded-3xl bg-white p-7 shadow-2xl sm:p-8">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.confirm_campaign') }}</p>
                        <p class="mt-3 font-display text-3xl font-bold" x-text="name"></p>
                        <p class="mt-3 text-base font-medium text-ink-muted" x-text="description"></p>
                        <p class="mt-3 text-sm text-ink-muted">{{ __('loop.campaign_then_offers_note') }}</p>
                        <form method="POST" action="{{ route('onboarding.campaign') }}" class="mt-6 space-y-3">
                            @csrf
                            <input type="hidden" name="template" :value="selected">
                            <button class="loop-btn w-full text-base">{{ __('loop.next_to_offers') }}</button>
                            <button type="button" class="w-full text-sm font-semibold text-ink-muted" @click="selected=null">{{ __('loop.back') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('onboarding.offers') }}" class="mt-6" x-data="{ count: 2 }">
                @csrf
                <div class="text-center">
                    <h2 class="font-display text-2xl font-semibold">{{ __('loop.pick_offers') }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.pick_offers_after_campaign') }}</p>
                    @if ($earnCampaign)
                        <p class="mt-3 rounded-full bg-mint-soft px-3 py-1 text-xs font-semibold text-mint-deep">{{ $earnCampaign->name }}</p>
                    @endif
                </div>

                <div class="mt-6 space-y-3">
                    @foreach ($offerTemplates as $offer)
                        <label class="flex cursor-pointer items-start gap-3 rounded-3xl border border-ink/10 bg-white/90 p-4 transition has-[:checked]:border-mint-deep has-[:checked]:bg-mint-soft/40"
                               @change="count = [...$el.closest('form').querySelectorAll('input[name=\'offers[]\']:checked')].length">
                            <input type="checkbox" name="offers[]" value="{{ $offer['key'] }}" class="mt-1 rounded border-ink/20 text-mint-deep focus:ring-mint-deep"
                                   @checked(in_array($offer['key'], ['percent_5_100', 'free_item_100', 'free_coffee_100', 'free_meal_500', 'percent_10_200'], true))>
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
