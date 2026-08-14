<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.settings') }}</p>
                <h1 class="mt-1 font-display text-2xl font-semibold sm:text-3xl">{{ __('loop.content_studio') }}</h1>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.content_studio_blurb') }}</p>
            </div>
            <x-settings-back />
        </div>
    </x-slot>

    <div
        class="mx-auto max-w-lg"
        x-data="{
            step: 1,
            total: 4,
            mode: 'photo_story',
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
            go(n) { this.step = n; },
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
            async downloadPng() {
                if (this.busy) return;
                if (! window.html2canvas) {
                    alert(@js(__('loop.studio_download_unavailable')));
                    return;
                }
                this.busy = true;
                const s = this.size();
                const source = document.getElementById('studio-card');
                const host = document.createElement('div');
                host.setAttribute('aria-hidden', 'true');
                host.style.cssText = 'position:fixed;left:-12000px;top:0;width:'+s.w+'px;height:'+s.h+'px;overflow:hidden;pointer-events:none;';
                const clone = source.cloneNode(true);
                clone.id = 'studio-card-export';
                clone.style.cssText = 'position:relative;width:'+s.w+'px;height:'+s.h+'px;transform:none;display:flex;flex-direction:column;justify-content:space-between;overflow:hidden;padding:48px;box-sizing:border-box;';
                host.appendChild(clone);
                document.body.appendChild(host);
                try {
                    const imgs = [...clone.querySelectorAll('img')];
                    await Promise.all(imgs.map(img => {
                        if (img.complete) return null;
                        return new Promise(r => { img.onload = r; img.onerror = r; });
                    }));
                    await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
                    const canvas = await window.html2canvas(clone, {
                        backgroundColor: null,
                        scale: 1,
                        width: s.w,
                        height: s.h,
                        windowWidth: s.w,
                        windowHeight: s.h,
                        useCORS: true,
                        logging: false,
                        imageTimeout: 5000,
                    });
                    const out = document.createElement('canvas');
                    out.width = s.w;
                    out.height = s.h;
                    const ctx = out.getContext('2d');
                    ctx.drawImage(canvas, 0, 0, s.w, s.h);
                    const link = document.createElement('a');
                    link.download = 'loop-' + this.sizeKey + '.png';
                    link.href = out.toDataURL('image/png');
                    link.click();
                } catch (err) {
                    console.error(err);
                    alert(@js(__('loop.studio_download_unavailable')));
                } finally {
                    host.remove();
                    this.busy = false;
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
    >
        {{-- Preview uses CSS aspect-ratio (no transform) so PNG clone matches --}}
        <div class="mb-4">
            <div class="mx-auto w-full max-w-sm overflow-hidden rounded-[1.5rem] border border-ink/10 bg-chalk/50 p-3 shadow-inner">
                <div
                    id="studio-card"
                    class="relative flex w-full flex-col justify-between overflow-hidden rounded-[1.15rem] shadow-lg"
                    :style="'aspect-ratio:' + size().w + '/' + size().h + ';padding:1.1rem;' + (mode === 'photo_story' ? 'color:' + colorHex() + ';' : '')"
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
                            <img :src="imageUrl" alt="" class="h-full w-full object-cover" crossorigin="anonymous" draggable="false">
                            <div class="absolute inset-0 bg-gradient-to-t from-ink/75 via-ink/30 to-ink/15"></div>
                        </div>
                    </template>

                    <div class="relative flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2.5">
                            @if ($business->logoUrl())
                                <img src="{{ $business->logoUrl() }}" alt="" class="h-10 w-10 shrink-0 rounded-xl object-cover ring-2 ring-white/40" crossorigin="anonymous">
                            @else
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/20 font-display text-base font-semibold ring-2 ring-white/30">{{ mb_substr($business->name, 0, 1) }}</div>
                            @endif
                            <p class="truncate font-display text-sm font-semibold leading-tight sm:text-base">{{ $business->name }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5 rounded-xl bg-white/15 px-2 py-1 backdrop-blur-sm">
                            <x-loop-logo class="h-5 w-5" />
                            <span class="text-[10px] font-semibold uppercase tracking-[0.12em]">Loop</span>
                        </div>
                    </div>

                    <p
                        class="relative my-4 font-display font-semibold leading-snug"
                        :style="'font-size:' + (0.95 + (fontScale - 1) * 0.55) + 'rem;color:' + (mode === 'photo_story' ? colorHex() : 'inherit')"
                        x-text="text()"
                    ></p>

                    <p class="relative text-[11px] font-semibold opacity-85 sm:text-xs">{{ $business->hotline ?: '' }}</p>
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
                {{ __('loop.step') }} <span class="text-ink" x-text="step"></span> {{ __('loop.of') }} 4
            </p>

            {{-- 1 · Look + upload --}}
            <div class="mt-4 space-y-4" x-show="step === 1">
                <p class="font-display text-lg font-semibold">{{ __('loop.studio_choose_look') }}</p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($modes as $mode)
                        <button type="button" class="min-h-[4.5rem] rounded-2xl border px-3 py-4 text-left"
                                :class="mode === '{{ $mode['key'] }}' ? 'border-violet bg-violet-soft/50 ring-2 ring-violet/20' : 'border-ink/10'"
                                @click="mode = '{{ $mode['key'] }}'">
                            <span class="block text-sm font-semibold">{{ $mode['name'] }}</span>
                        </button>
                    @endforeach
                </div>
                <div x-show="mode === 'photo_story'" class="space-y-3">
                    <label class="loop-label">{{ __('loop.studio_upload_or_capture') }}</label>
                    <label class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border border-dashed border-violet/40 bg-violet-soft/20 px-4 py-5 text-sm font-semibold text-violet">
                        <input type="file" accept="image/*" capture="environment" class="sr-only" @change="onImage($event)">
                        {{ __('loop.studio_upload_or_capture') }}
                    </label>
                </div>
                <div x-show="mode === 'clean_type'">
                    <label class="loop-label">{{ __('loop.studio_design') }}</label>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @foreach ($designs as $d)
                            <button type="button" class="min-h-[3.5rem] rounded-2xl border px-3 py-3 text-left text-sm font-semibold"
                                    :class="design==='{{ $d['key'] }}' ? 'border-violet bg-violet-soft/40 ring-2 ring-violet/20' : 'border-ink/10'"
                                    @click="design='{{ $d['key'] }}'">{{ $d['name'] }}</button>
                        @endforeach
                    </div>
                </div>
                <button type="button" class="loop-btn-mint w-full" @click="go(2)">{{ __('loop.continue') }}</button>
            </div>

            {{-- 2 · Size --}}
            <div class="mt-4 space-y-4" x-show="step === 2" x-cloak>
                <p class="font-display text-lg font-semibold">{{ __('loop.studio_choose_size') }}</p>
                <button type="button" class="loop-input flex min-h-[3.25rem] w-full items-center justify-between text-left" @click="sheet = 'size'">
                    <span>
                        <span class="block text-sm font-semibold" x-text="size().name"></span>
                        <span class="text-xs text-ink-muted" x-text="size().label"></span>
                    </span>
                    <span class="rounded-lg bg-violet-soft px-2 py-1 text-xs font-semibold text-violet">▾</span>
                </button>
                <div class="flex gap-2">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(1)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click="go(3)">{{ __('loop.continue') }}</button>
                </div>
            </div>

            {{-- 3 · Words --}}
            <div class="mt-4 space-y-4" x-show="step === 3" x-cloak>
                <p class="font-display text-lg font-semibold">{{ __('loop.studio_choose_copy') }}</p>

                <button type="button" class="loop-input flex min-h-[3rem] w-full items-center justify-between" @click="sheet = 'lang'">
                    <span class="text-sm font-semibold" x-text="lang === 'sw' ? 'Kiswahili' : 'English'"></span>
                    <span class="text-violet">▾</span>
                </button>

                <div class="grid grid-cols-3 gap-2">
                    @foreach (['general' => __('loop.studio_cat_general'), 'offers' => __('loop.studio_cat_offers'), 'campaigns' => __('loop.studio_cat_campaigns')] as $cat => $label)
                        <button type="button" class="min-h-[3rem] rounded-2xl border px-2 py-3 text-center text-xs font-semibold"
                                :class="category==='{{ $cat }}' ? 'border-violet bg-violet text-white' : 'border-ink/10 bg-chalk text-ink'"
                                @click="category='{{ $cat }}'; const first = filteredCopies()[0]; if (first) copyKey = first.key;">{{ $label }}</button>
                    @endforeach
                </div>

                <button type="button" class="loop-input flex min-h-[3.5rem] w-full items-start justify-between gap-3 text-left" @click="sheet = 'copy'">
                    <span class="text-sm font-medium leading-snug" x-text="text()"></span>
                    <span class="shrink-0 text-violet">▾</span>
                </button>

                <div x-show="mode === 'photo_story'" class="space-y-3 rounded-2xl border border-ink/8 bg-chalk/40 p-3">
                    <div>
                        <label class="loop-label">{{ __('loop.studio_text_color') }}</label>
                        <div class="mt-2 grid grid-cols-6 gap-2">
                            @foreach ($textColors as $c)
                                <button type="button" class="h-10 rounded-xl ring-2 transition" style="background: {{ $c['hex'] }}"
                                        :class="textColor === '{{ $c['key'] }}' ? 'ring-violet scale-105' : 'ring-ink/10'"
                                        @click="textColor = '{{ $c['key'] }}'" title="{{ $c['name'] }}"></button>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.studio_font_size') }}</label>
                        <input type="range" min="0.85" max="1.35" step="0.05" x-model.number="fontScale" class="mt-2 w-full accent-violet">
                        <p class="mt-1 text-xs text-ink-muted">{{ __('loop.studio_font_size_hint') }}</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(2)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click="go(4)">{{ __('loop.continue') }}</button>
                </div>
            </div>

            {{-- 4 · Download --}}
            <div class="mt-4 space-y-3" x-show="step === 4" x-cloak>
                <p class="font-display text-lg font-semibold">{{ __('loop.studio_share_download') }}</p>
                <button type="button" class="loop-btn-mint w-full" :disabled="busy" @click="downloadPng()">
                    <span x-text="busy ? @js(__('loop.saving')) : @js(__('loop.download_png'))"></span>
                </button>
                <button type="button" class="loop-btn-ghost w-full" @click="shareCard()">{{ __('loop.share_creative') }}</button>
                <button type="button" class="w-full text-sm font-semibold text-ink-muted" @click="go(3)">{{ __('loop.back') }}</button>
            </div>
        </div>

        <template x-teleport="body">
            <div x-show="sheet" x-cloak class="fixed inset-0 z-[90]" @keydown.escape.window="sheet = null">
                <div class="absolute inset-0 bg-ink/50" @click="sheet = null"></div>
                <div class="absolute inset-x-0 bottom-0 max-h-[80vh] overflow-hidden rounded-t-[1.75rem] bg-white" @click.stop>
                    <div class="mx-auto mt-3 h-1.5 w-12 rounded-full bg-ink/15"></div>
                    <div class="border-b border-ink/5 px-5 py-3">
                        <p class="font-display text-lg font-semibold"
                           x-text="sheet === 'size' ? @js(__('loop.studio_choose_size')) : (sheet === 'lang' ? @js(__('loop.language')) : @js(__('loop.studio_choose_copy')))"></p>
                    </div>
                    <div class="max-h-[60vh] space-y-2 overflow-y-auto p-3 pb-8">
                        <template x-if="sheet === 'size'">
                            <div class="space-y-2">
                                <template x-for="sz in Object.values(sizes)" :key="sz.key">
                                    <button type="button" class="flex min-h-[3.5rem] w-full items-center justify-between rounded-2xl border px-4 py-3 text-left"
                                            :class="sizeKey === sz.key ? 'border-violet bg-violet-soft/40' : 'border-ink/10'"
                                            @click="sizeKey = sz.key; sheet = null">
                                        <span class="text-sm font-semibold" x-text="sz.name"></span>
                                        <span class="text-xs text-ink-muted" x-text="sz.label"></span>
                                    </button>
                                </template>
                            </div>
                        </template>
                        <template x-if="sheet === 'lang'">
                            <div class="space-y-2">
                                <button type="button" class="flex min-h-[3.25rem] w-full items-center justify-between rounded-2xl border px-4 py-3"
                                        :class="lang === 'en' ? 'border-violet bg-violet-soft/40' : 'border-ink/10'"
                                        @click="lang = 'en'; sheet = null">English <span x-show="lang==='en'">✓</span></button>
                                <button type="button" class="flex min-h-[3.25rem] w-full items-center justify-between rounded-2xl border px-4 py-3"
                                        :class="lang === 'sw' ? 'border-violet bg-violet-soft/40' : 'border-ink/10'"
                                        @click="lang = 'sw'; sheet = null">Kiswahili <span x-show="lang==='sw'">✓</span></button>
                            </div>
                        </template>
                        <template x-if="sheet === 'copy'">
                            <div class="space-y-2">
                                <template x-for="copy in filteredCopies()" :key="copy.key">
                                    <button type="button" class="w-full rounded-2xl border px-4 py-3.5 text-left text-sm leading-snug"
                                            :class="copyKey === copy.key ? 'border-violet bg-violet-soft/40 font-semibold' : 'border-ink/10'"
                                            @click="copyKey = copy.key; sheet = null"
                                            x-text="copy[lang]"></button>
                                </template>
                                <p x-show="filteredCopies().length === 0" class="px-3 py-6 text-center text-sm text-ink-muted">{{ __('loop.no_results') }}</p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
