<x-app-layout>
    <div class="mx-auto max-w-lg">
        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">Loop</p>
            <h1 class="mt-2 font-display text-3xl font-semibold">{{ $business->name }}</h1>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.onboarding_blurb') }}</p>
        </div>

        <div class="mt-6 flex gap-2">
            @foreach ([1, 2, 3, 4] as $n)
                <div class="h-1.5 flex-1 rounded-full {{ $step >= $n ? 'bg-gradient-to-r from-mint-deep to-coral' : 'bg-ink/10' }}"></div>
            @endforeach
        </div>
        <p class="mt-2 text-center text-xs font-semibold text-ink-muted">{{ __('loop.step') }} {{ $step }}/4</p>

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
                        if (!this.fileName || this.uploading) {
                            e.preventDefault();
                            return;
                        }
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
                        <template x-if="preview">
                            <img :src="preview" alt="" class="h-full w-full object-cover">
                        </template>
                        <div x-show="!preview" class="flex h-full w-full items-center justify-center font-display text-4xl text-mint">
                            {{ mb_substr($business->name, 0, 1) }}
                        </div>
                        <div
                            x-show="uploading"
                            x-cloak
                            class="absolute inset-0 flex flex-col items-center justify-center bg-ink/70 backdrop-blur-sm"
                        >
                            <div class="h-10 w-10 animate-spin rounded-full border-2 border-white/30 border-t-mint"></div>
                            <p class="mt-3 text-xs font-semibold text-white">{{ __('loop.uploading') }}</p>
                        </div>
                    </div>

                    <p x-show="fileName" x-text="fileName" class="mt-3 max-w-xs truncate text-xs text-ink-muted"></p>

                    <label class="mt-6 inline-flex cursor-pointer items-center justify-center rounded-xl bg-mint px-6 py-3 text-sm font-semibold text-ink transition hover:bg-mint-deep">
                        <span x-text="fileName ? '{{ __('loop.change_image') }}' : '{{ __('loop.upload') }}'"></span>
                        <input type="file" name="logo" accept="image/*" class="sr-only" required @change="pick">
                    </label>
                    <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                </div>

                <button
                    type="submit"
                    class="loop-btn mt-8 w-full"
                    :disabled="!fileName || uploading"
                    :class="{ 'opacity-60': !fileName || uploading }"
                >
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
        @else
            <div class="mt-6">
                <div class="text-center">
                    <h2 class="font-display text-2xl font-semibold">{{ __('loop.pick_campaign') }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.pick_campaign_body') }}</p>
                </div>
                <div class="mt-6 grid gap-3">
                    @foreach ($templates as $key => $template)
                        <form method="POST" action="{{ route('onboarding.campaign') }}">
                            @csrf
                            <input type="hidden" name="template" value="{{ $key }}">
                            <button class="w-full rounded-3xl border border-ink/10 bg-white/90 p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-mint hover:bg-mint-soft/40">
                                <p class="font-display text-lg font-semibold">{{ $template['name'] }}</p>
                                <p class="mt-2 text-sm text-ink-muted">{{ $template['description'] }}</p>
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
