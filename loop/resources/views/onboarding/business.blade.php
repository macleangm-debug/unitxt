<x-app-layout>
    <div class="mx-auto max-w-lg">
        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">Loop</p>
            <h1 class="mt-2 font-display text-3xl font-semibold">{{ $business->name }}</h1>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.onboarding_blurb') }}</p>
        </div>

        <div class="mt-6 flex gap-2">
            @foreach ([1, 2, 3, 4, 5] as $n)
                <div class="h-1.5 flex-1 rounded-full {{ $step >= $n ? 'bg-gradient-to-r from-mint-deep to-coral' : 'bg-ink/10' }}"></div>
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
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-mint text-2xl text-ink">✓</div>
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
                </div>
                <div class="mt-8 flex flex-col items-center">
                    <div class="relative h-36 w-36 overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-ink via-ink-soft to-mint/30 ring-4 ring-mint/20">
                        <template x-if="preview"><img :src="preview" alt="" class="h-full w-full object-cover"></template>
                        <div x-show="!preview" class="flex h-full w-full items-center justify-center font-display text-4xl text-mint">{{ mb_substr($business->name, 0, 1) }}</div>
                        <div x-show="uploading" x-cloak class="absolute inset-0 flex flex-col items-center justify-center bg-ink/70 backdrop-blur-sm">
                            <div class="h-10 w-10 animate-spin rounded-full border-2 border-white/30 border-t-mint"></div>
                            <p class="mt-3 text-xs font-semibold text-white">{{ __('loop.uploading') }}</p>
                        </div>
                    </div>
                    <label class="mt-6 inline-flex cursor-pointer items-center justify-center rounded-xl bg-mint px-6 py-3 text-sm font-semibold text-ink transition hover:bg-mint-deep">
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
                    @if ($business->logo_path)
                        <img src="{{ asset('storage/'.$business->logo_path) }}" alt="" class="mx-auto h-20 w-20 rounded-2xl object-cover ring-2 ring-mint/30">
                    @endif
                    <h2 class="mt-4 font-display text-2xl font-semibold">{{ __('loop.how_many_branches') }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.how_many_branches_body') }}</p>
                </div>
                <div class="mt-8">
                    <label class="loop-label text-center">{{ __('loop.branches') }}</label>
                    <input type="number" min="1" max="50" name="branch_count" value="{{ old('branch_count', $business->branch_count ?: 1) }}" class="loop-input text-center text-2xl font-display font-semibold" required>
                </div>
                <button class="loop-btn-mint mt-8 w-full">{{ __('loop.next') }}</button>
            </form>
        @elseif ($step === 3)
            <form method="POST" action="{{ route('onboarding.shop') }}" class="mt-6 overflow-hidden rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8">
                @csrf
                <div class="text-center">
                    <h2 class="font-display text-2xl font-semibold">{{ __('loop.first_shop') }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.first_shop_body') }}</p>
                </div>
                <div class="mt-6 space-y-4">
                    <div>
                        <label class="loop-label">{{ __('loop.shop_name') }}</label>
                        <input name="shop_name" value="{{ old('shop_name', $business->name) }}" class="loop-input" required>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.city') }}</label>
                        <input name="city" list="cities" value="{{ old('city', $business->city) }}" class="loop-input" required>
                        <datalist id="cities">
                            @foreach ($cities as $city)
                                <option value="{{ $city }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.address') }}</label>
                        <input name="address" value="{{ old('address') }}" class="loop-input">
                    </div>
                </div>
                <button class="loop-btn-mint mt-8 w-full">{{ __('loop.next') }}</button>
            </form>
        @elseif ($step === 4)
            <div class="mt-6" x-data="{ selected: null, name: '', description: '' }">
                <div class="text-center">
                    <h2 class="font-display text-2xl font-semibold">{{ __('loop.pick_campaign') }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.pick_campaign_earn_only') }}</p>
                </div>

                @foreach ($groupedTemplates as $intention => $group)
                    <section class="mt-6">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ $group['label'] }}</h3>
                        <div class="mt-3 grid gap-3">
                            @foreach ($group['templates'] as $key => $template)
                                <button
                                    type="button"
                                    class="w-full rounded-3xl border border-ink/10 bg-white/90 p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-mint hover:bg-mint-soft/40"
                                    @click="selected='{{ $key }}'; name=@js($template['name']); description=@js($template['description'])"
                                >
                                    <p class="font-display text-lg font-semibold">{{ $template['name'] }}</p>
                                    <p class="mt-2 text-sm text-ink-muted">{{ $template['description'] }}</p>
                                </button>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <div x-show="selected" x-cloak class="fixed inset-0 z-50 flex items-end justify-center sm:items-center" @keydown.escape.window="selected=null">
                    <div class="absolute inset-0 bg-ink/45" @click="selected=null"></div>
                    <div class="relative w-full max-w-md rounded-t-3xl bg-white p-6 shadow-2xl sm:rounded-3xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.confirm_campaign') }}</p>
                        <p class="mt-2 font-display text-2xl font-semibold" x-text="name"></p>
                        <p class="mt-2 text-sm text-ink-muted" x-text="description"></p>
                        <form method="POST" action="{{ route('onboarding.campaign') }}" class="mt-6 space-y-3">
                            @csrf
                            <input type="hidden" name="template" :value="selected">
                            <button class="loop-btn-mint w-full">{{ __('loop.next_to_offers') }}</button>
                            <button type="button" class="w-full text-sm font-semibold text-ink-muted" @click="selected=null">{{ __('loop.back') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            @php
                $earn = $earnCampaign;
            @endphp
            <form method="POST" action="{{ route('onboarding.offers') }}" class="mt-6" x-data="{ selected: {} }">
                @csrf
                <div class="text-center">
                    <h2 class="font-display text-2xl font-semibold">{{ __('loop.pick_offers') }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.pick_offers_body') }}</p>
                    @if ($earn)
                        <p class="mt-2 rounded-2xl bg-mint-soft/60 px-3 py-2 text-xs font-medium text-ink">
                            {{ $earn->ruleSummary($business->currency) }}
                        </p>
                    @endif
                </div>

                <div class="mt-6 space-y-3">
                    @foreach ($offerTemplates as $offer)
                        @php
                            $hint = \App\Support\OfferTemplates::spendToUnlock($earn, $offer['points_cost'], $business->currency);
                        @endphp
                        <label class="flex cursor-pointer items-start gap-3 rounded-3xl border border-ink/10 bg-white/90 p-4 transition has-[:checked]:border-mint has-[:checked]:bg-mint-soft/40">
                            <input type="checkbox" name="offers[]" value="{{ $offer['key'] }}" class="mt-1 rounded border-ink/20 text-mint focus:ring-mint"
                                   @checked(in_array($offer['key'], ['percent_5_100', 'free_coffee_100', 'free_meal_500', 'percent_10_200'], true))>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-start justify-between gap-2">
                                    <span class="font-display text-base font-semibold">{{ $offer['name'] }}</span>
                                    <span class="shrink-0 rounded-lg bg-ink px-2 py-1 text-xs font-semibold text-mint">{{ $offer['points_cost'] }} pts</span>
                                </span>
                                <span class="mt-1 block text-sm text-ink-muted">{{ $offer['description'] }}</span>
                                @if ($hint)
                                    <span class="mt-2 block text-xs font-medium text-mint-deep">{{ $hint }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>

                <p class="mt-4 text-center text-xs text-ink-muted">{{ __('loop.more_offers_later') }}</p>
                <button class="loop-btn-mint mt-6 w-full">{{ __('loop.finish_onboarding') }}</button>
            </form>
            <form method="POST" action="{{ route('onboarding.offers') }}" class="mt-3">
                @csrf
                <input type="hidden" name="skip" value="1">
                <button class="w-full text-sm font-semibold text-ink-muted">{{ __('loop.skip_offers') }}</button>
            </form>
        @endif
    </div>
</x-app-layout>
