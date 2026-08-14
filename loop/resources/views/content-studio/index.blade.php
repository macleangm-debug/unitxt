<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.settings') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.content_studio') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.content_studio_blurb') }}</p>
            </div>
            <x-settings-back />
        </div>
    </x-slot>

    <div
        class="mx-auto max-w-lg"
        x-data="{
            step: 1,
            total: 4,
            mode: 'clean_type',
            sizeKey: 'ig_post',
            sizes: @js(collect($sizes)->keyBy('key')),
            design: 'mint_card',
            designs: @js(collect($designs)->keyBy('key')),
            category: 'general',
            copyKey: @js(collect($copies)->firstWhere('category', 'general')['key'] ?? 'now_on_loop'),
            copies: @js(collect($copies)->keyBy('key')),
            lang: @js($locale === 'sw' ? 'sw' : 'en'),
            textColor: 'white',
            textColors: @js(collect($textColors)->keyBy('key')),
            fontScale: 1,
            imageUrl: '',
            busy: false,
            sheet: null,
            previewScale: 1,
            go(n) { this.step = n; window.scrollTo({ top: 0, behavior: 'smooth' }); },
            text() { return this.copies[this.copyKey]?.[this.lang] || ''; },
            size() { return this.sizes[this.sizeKey] || this.sizes.ig_post; },
            colorHex() { return this.textColors[this.textColor]?.hex || '#FFFFFF'; },
            filteredCopies() {
                return Object.values(this.copies).filter(c => c.category === this.category);
            },
            onImage(e) {
                const file = e.target.files?.[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = () => { this.imageUrl = reader.result; };
                reader.readAsDataURL(file);
            },
            measurePreview() {
                this.$nextTick(() => {
                    const wrap = this.$refs.previewWrap;
                    if (!wrap) return;
                    const s = this.size();
                    this.previewScale = Math.min(1, wrap.clientWidth / s.w);
                });
            },
            async downloadPng() {
                if (this.busy) return;
                const node = document.getElementById('studio-card');
                if (!node || !window.html2canvas) {
                    alert(@js(__('loop.studio_download_unavailable')));
                    return;
                }
                this.busy = true;
                const s = this.size();
                const prevTransform = node.style.transform;
                const prevOrigin = node.style.transformOrigin;
                try {
                    node.style.transform = 'none';
                    node.style.transformOrigin = 'top left';
                    await this.$nextTick();
                    const canvas = await window.html2canvas(node, {
                        backgroundColor: null,
                        scale: 1,
                        width: s.w,
                        height: s.h,
                        windowWidth: s.w,
                        windowHeight: s.h,
                        useCORS: true,
                        logging: false,
                    });
                    const link = document.createElement('a');
                    link.download = 'loop-' + this.sizeKey + '.png';
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                } catch (err) {
                    console.error(err);
                    alert(@js(__('loop.studio_download_unavailable')));
                } finally {
                    node.style.transform = prevTransform;
                    node.style.transformOrigin = prevOrigin;
                    this.busy = false;
                    this.measurePreview();
                }
            },
            async shareCard() {
                const message = this.text() + ' — ' + @js($business->name) + ' on Loop';
                if (navigator.share) {
                    try { await navigator.share({ title: @js($business->name), text: message }); return; } catch (_) {}
                }
                await navigator.clipboard.writeText(message);
                alert(@js(__('loop.copied')));
            }
        }"
        x-init="measurePreview(); window.addEventListener('resize', () => measurePreview())"
        x-effect="sizeKey; measurePreview()"
    >
        {{-- Fixed-pixel canvas, scaled for preview (WYSIWYG for PNG) --}}
        <div class="mb-4 flex items-center justify-center">
            <div class="w-full max-w-sm overflow-hidden rounded-[1.5rem] border border-ink/10 bg-chalk/50 p-3 shadow-inner">
                <div
                    x-ref="previewWrap"
                    class="relative mx-auto w-full overflow-hidden rounded-[1.15rem] bg-ink/5 shadow-lg"
                    :style="'height:' + (size().h * previewScale) + 'px'"
                >
                    <div
                        id="studio-card"
                        class="absolute left-0 top-0 flex flex-col justify-between overflow-hidden"
                        :style="'width:' + size().w + 'px;height:' + size().h + 'px;transform:scale(' + previewScale + ');transform-origin:top left;padding:' + (48 * fontScale) + 'px;color:' + (mode === 'photo_story' ? colorHex() : '')"
                        :class="{
                            'text-white': mode === 'photo_story' || design === 'ink_bold',
                            'bg-gradient-to-br from-lime to-[#8fd63a] text-ink': mode === 'clean_type' && design === 'mint_card',
                            'bg-ink text-white': mode === 'clean_type' && design === 'ink_bold',
                            'bg-gradient-to-br from-coral to-[#ff8f75] text-ink': mode === 'clean_type' && design === 'coral_pop',
                            'bg-[#F7F3EA] text-ink': mode === 'clean_type' && design === 'cream_soft',
                            'bg-ink': mode === 'photo_story' && !imageUrl
                        }"
                    >
                        <template x-if="mode === 'photo_story' && imageUrl">
                            <div class="absolute inset-0">
                                <img :src="imageUrl" alt="" class="h-full w-full object-cover" crossorigin="anonymous" style="filter: brightness(0.55) contrast(1.05);">
                                <div class="absolute inset-0 bg-gradient-to-t from-ink/70 via-ink/25 to-ink/10"></div>
                            </div>
                        </template>
                        <div class="relative flex items-center justify-between gap-6">
                            <div class="flex min-w-0 items-center gap-4">
                                @if ($business->logoUrl())
                                    <img src="{{ $business->logoUrl() }}" alt="" class="rounded-2xl object-cover ring-2 ring-white/40" style="width:88px;height:88px" crossorigin="anonymous">
                                @else
                                    <div class="flex items-center justify-center rounded-2xl bg-white/20 font-display font-semibold ring-2 ring-white/30" style="width:88px;height:88px;font-size:40px">{{ mb_substr($business->name, 0, 1) }}</div>
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate font-display font-semibold leading-tight" style="font-size:36px">{{ $business->name }}</p>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-3 rounded-2xl bg-white/15 px-4 py-2 backdrop-blur-sm">
                                <x-loop-logo class="h-10 w-10" />
                                <span class="font-semibold uppercase tracking-[0.12em]" style="font-size:18px">Loop</span>
                            </div>
                        </div>
                        <p
                            class="relative mt-10 font-display font-semibold leading-snug"
                            :style="'font-size:' + (Math.round(52 * fontScale)) + 'px;color:' + (mode === 'photo_story' ? colorHex() : 'inherit')"
                            x-text="text()"
                        ></p>
                        <p class="relative mt-8 font-semibold opacity-80" style="font-size:22px">{{ $business->hotline ?: '' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-[1.5rem] border border-ink/8 bg-white/95 p-4 shadow-[0_12px_36px_rgba(17,17,20,0.05)] sm:p-5">
            <div class="mb-4 flex gap-2">
                @foreach (range(1, 4) as $n)
                    <div class="h-1.5 flex-1 rounded-full" :class="step >= {{ $n }} ? 'bg-mint-deep' : 'bg-ink/10'"></div>
                @endforeach
            </div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-muted">
                {{ __('loop.step') }} <span class="text-ink" x-text="step"></span> {{ __('loop.of') }} <span x-text="total"></span>
            </p>

            <div class="mt-4 space-y-4" :class="step === 1 ? '' : 'hidden'">
                <p class="font-display text-lg font-semibold">{{ __('loop.studio_choose_look') }}</p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($modes as $mode)
                        <button type="button" class="rounded-2xl border px-3 py-4 text-left"
                                :class="mode === '{{ $mode['key'] }}' ? 'border-violet bg-violet-soft/50' : 'border-ink/10'"
                                @click="mode = '{{ $mode['key'] }}'">
                            <span class="block text-sm font-semibold">{{ $mode['name'] }}</span>
                        </button>
                    @endforeach
                </div>
                <div :class="mode === 'photo_story' ? '' : 'hidden'" class="space-y-3">
                    <div>
                        <label class="loop-label">{{ __('loop.studio_upload_or_capture') }}</label>
                        <label class="loop-btn-ghost mt-1 flex w-full cursor-pointer items-center justify-center gap-2 !py-3">
                            <input type="file" accept="image/*" capture="environment" class="sr-only" @change="onImage($event)">
                            {{ __('loop.studio_upload_or_capture') }}
                        </label>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.studio_text_color') }}</label>
                        <div class="mt-2 grid grid-cols-6 gap-2">
                            @foreach ($textColors as $c)
                                <button
                                    type="button"
                                    class="h-10 rounded-xl ring-2 transition"
                                    style="background: {{ $c['hex'] }}"
                                    :class="textColor === '{{ $c['key'] }}' ? 'ring-violet scale-105' : 'ring-ink/10'"
                                    @click="textColor = '{{ $c['key'] }}'"
                                    title="{{ $c['name'] }}"
                                ></button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div :class="mode === 'clean_type' ? '' : 'hidden'">
                    <label class="loop-label">{{ __('loop.studio_design') }}</label>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @foreach ($designs as $d)
                            <button type="button" class="rounded-2xl border px-3 py-3 text-left text-sm font-semibold"
                                    :class="design==='{{ $d['key'] }}' ? 'border-violet bg-violet-soft/40' : 'border-ink/10'"
                                    @click="design='{{ $d['key'] }}'">{{ $d['name'] }}</button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.studio_font_size') }}</label>
                    <input type="range" min="0.8" max="1.25" step="0.05" x-model.number="fontScale" class="mt-2 w-full accent-violet">
                    <p class="mt-1 text-xs text-ink-muted">{{ __('loop.studio_font_size_hint') }}</p>
                </div>
                <button type="button" class="loop-btn-mint w-full" @click="go(2)">{{ __('loop.continue') }}</button>
            </div>

            <div class="mt-4 space-y-4" :class="step === 2 ? '' : 'hidden'">
                <p class="font-display text-lg font-semibold">{{ __('loop.studio_choose_size') }}</p>
                <button type="button" class="loop-input flex w-full items-center justify-between text-left sm:hidden" @click="sheet = 'size'">
                    <span x-text="size().name + ' · ' + size().label"></span>
                    <span class="text-violet">▾</span>
                </button>
                <div class="hidden grid-cols-2 gap-2 sm:grid">
                    @foreach ($sizes as $size)
                        <button type="button" class="rounded-2xl border px-3 py-3 text-left"
                                :class="sizeKey==='{{ $size['key'] }}' ? 'border-violet bg-violet-soft/50' : 'border-ink/10'"
                                @click="sizeKey='{{ $size['key'] }}'; measurePreview()">
                            <span class="block text-sm font-semibold">{{ $size['name'] }}</span>
                            <span class="mt-1 block text-xs text-ink-muted">{{ $size['label'] }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="flex gap-2">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(1)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click="go(3)">{{ __('loop.continue') }}</button>
                </div>
            </div>

            <div class="mt-4 space-y-4" :class="step === 3 ? '' : 'hidden'">
                <p class="font-display text-lg font-semibold">{{ __('loop.studio_choose_copy') }}</p>
                <div class="flex gap-2">
                    <button type="button" class="rounded-xl px-3 py-2 text-xs font-semibold" :class="lang==='en' ? 'bg-ink text-white' : 'bg-chalk'" @click="lang='en'">EN</button>
                    <button type="button" class="rounded-xl px-3 py-2 text-xs font-semibold" :class="lang==='sw' ? 'bg-ink text-white' : 'bg-chalk'" @click="lang='sw'">SW</button>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach (['general' => __('loop.studio_cat_general'), 'offers' => __('loop.studio_cat_offers'), 'campaigns' => __('loop.studio_cat_campaigns')] as $cat => $label)
                        <button type="button" class="rounded-xl px-3 py-2 text-xs font-semibold"
                                :class="category==='{{ $cat }}' ? 'bg-violet text-white' : 'bg-chalk text-ink'"
                                @click="category='{{ $cat }}'; const first = filteredCopies()[0]; if (first) copyKey = first.key;">{{ $label }}</button>
                    @endforeach
                </div>
                <button type="button" class="loop-input flex w-full items-center justify-between text-left sm:hidden" @click="sheet = 'copy'">
                    <span class="truncate" x-text="text()"></span>
                    <span class="text-violet">▾</span>
                </button>
                <div class="hidden max-h-56 space-y-2 overflow-y-auto sm:block">
                    <template x-for="copy in filteredCopies()" :key="copy.key">
                        <button type="button" class="w-full rounded-2xl border px-4 py-3 text-left text-sm"
                                :class="copyKey === copy.key ? 'border-violet bg-violet-soft/40' : 'border-ink/10'"
                                @click="copyKey = copy.key"
                                x-text="copy[lang]"></button>
                    </template>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(2)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click="go(4)">{{ __('loop.continue') }}</button>
                </div>
            </div>

            <div class="mt-4 space-y-3" :class="step === 4 ? '' : 'hidden'">
                <p class="font-display text-lg font-semibold">{{ __('loop.studio_share_download') }}</p>
                <button type="button" class="loop-btn-mint w-full" :disabled="busy" @click="downloadPng()">
                    <span x-text="busy ? @js(__('loop.saving')) : @js(__('loop.download_png'))"></span>
                </button>
                <button type="button" class="loop-btn-ghost w-full" @click="shareCard()">{{ __('loop.share_creative') }}</button>
                <button type="button" class="w-full text-sm font-semibold text-ink-muted" @click="go(1)">{{ __('loop.back') }}</button>
            </div>
        </div>

        {{-- Mobile bottom sheets for size / copy --}}
        <template x-teleport="body">
            <div x-show="sheet" x-cloak class="fixed inset-0 z-[90]" @keydown.escape.window="sheet = null">
                <div class="absolute inset-0 bg-ink/50" @click="sheet = null"></div>
                <div class="absolute inset-x-0 bottom-0 max-h-[80vh] overflow-hidden rounded-t-[1.75rem] bg-white" @click.stop>
                    <div class="mx-auto mt-3 h-1.5 w-12 rounded-full bg-ink/15"></div>
                    <div class="border-b border-ink/5 px-5 py-3">
                        <p class="font-display text-lg font-semibold" x-text="sheet === 'size' ? @js(__('loop.studio_choose_size')) : @js(__('loop.studio_choose_copy'))"></p>
                    </div>
                    <div class="max-h-[60vh] space-y-2 overflow-y-auto p-3 pb-8">
                        <template x-if="sheet === 'size'">
                            <div class="space-y-2">
                                <template x-for="sz in Object.values(sizes)" :key="sz.key">
                                    <button type="button" class="flex w-full items-center justify-between rounded-2xl border px-4 py-3 text-left"
                                            :class="sizeKey === sz.key ? 'border-violet bg-violet-soft/40' : 'border-ink/10'"
                                            @click="sizeKey = sz.key; measurePreview(); sheet = null">
                                        <span class="text-sm font-semibold" x-text="sz.name"></span>
                                        <span class="text-xs text-ink-muted" x-text="sz.label"></span>
                                    </button>
                                </template>
                            </div>
                        </template>
                        <template x-if="sheet === 'copy'">
                            <div class="space-y-2">
                                <template x-for="copy in filteredCopies()" :key="copy.key">
                                    <button type="button" class="w-full rounded-2xl border px-4 py-3 text-left text-sm"
                                            :class="copyKey === copy.key ? 'border-violet bg-violet-soft/40' : 'border-ink/10'"
                                            @click="copyKey = copy.key; sheet = null"
                                            x-text="copy[lang]"></button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
