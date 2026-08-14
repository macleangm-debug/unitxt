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
            textColor: 'auto',
            imageUrl: '',
            busy: false,
            go(n) { this.step = n; window.scrollTo({ top: 0, behavior: 'smooth' }); },
            text() { return this.copies[this.copyKey]?.[this.lang] || ''; },
            size() { return this.sizes[this.sizeKey] || this.sizes.ig_post; },
            aspect() {
                const s = this.size();
                return (s.h / s.w) * 100;
            },
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
            cardClass() {
                if (this.mode === 'photo_story') return 'text-white';
                return {
                    mint_card: 'bg-gradient-to-br from-lime to-[#8fd63a] text-ink',
                    ink_bold: 'bg-ink text-white',
                    coral_pop: 'bg-gradient-to-br from-coral to-[#ff8f75] text-ink',
                    cream_soft: 'bg-[#F7F3EA] text-ink',
                }[this.design] || 'bg-ink text-white';
            },
            resolvedTextColor() {
                if (this.mode !== 'photo_story') return null;
                if (this.textColor === 'white') return '#fff';
                if (this.textColor === 'ink') return '#111114';
                return '#fff';
            },
            async downloadPng() {
                if (this.busy) return;
                const node = document.getElementById('studio-card');
                if (!node || !window.html2canvas) {
                    alert(@js(__('loop.studio_download_unavailable')));
                    return;
                }
                this.busy = true;
                try {
                    const s = this.size();
                    const canvas = await window.html2canvas(node, {
                        backgroundColor: null,
                        scale: Math.max(2, s.w / Math.max(node.clientWidth, 1)),
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
        <div class="mb-4 flex items-center justify-center">
            <div class="w-full max-w-sm overflow-hidden rounded-[1.5rem] border border-ink/10 bg-chalk/50 p-3 shadow-inner">
                <div class="relative mx-auto w-full overflow-hidden rounded-[1.15rem] shadow-lg" :style="'padding-top:' + aspect() + '%'">
                    <div
                        id="studio-card"
                        class="absolute inset-0 flex flex-col justify-between overflow-hidden p-5 sm:p-6"
                        :class="{
                            'text-white': mode === 'photo_story' || design === 'ink_bold',
                            'bg-gradient-to-br from-lime to-[#8fd63a] text-ink': mode === 'clean_type' && design === 'mint_card',
                            'bg-ink text-white': mode === 'clean_type' && design === 'ink_bold',
                            'bg-gradient-to-br from-coral to-[#ff8f75] text-ink': mode === 'clean_type' && design === 'coral_pop',
                            'bg-[#F7F3EA] text-ink': mode === 'clean_type' && design === 'cream_soft',
                            'bg-ink': mode === 'photo_story' && !imageUrl
                        }"
                        :style="mode === 'photo_story' && textColor === 'ink' ? 'color:#111114' : (mode === 'photo_story' && textColor === 'white' ? 'color:#fff' : '')"
                    >
                        <template x-if="mode === 'photo_story' && imageUrl">
                            <div class="absolute inset-0">
                                <img :src="imageUrl" alt="" class="h-full w-full object-cover" style="filter: brightness(0.55) contrast(1.05);">
                                <div class="absolute inset-0 bg-gradient-to-t from-ink/70 via-ink/25 to-ink/10"></div>
                            </div>
                        </template>
                        <div class="relative flex items-center justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-2.5">
                                @if ($business->logoUrl())
                                    <img src="{{ $business->logoUrl() }}" alt="" class="h-11 w-11 rounded-xl object-cover ring-2 ring-white/40">
                                @else
                                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 font-display text-lg font-semibold ring-2 ring-white/30">{{ mb_substr($business->name, 0, 1) }}</div>
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate font-display text-base font-semibold leading-tight">{{ $business->name }}</p>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-1.5 rounded-xl bg-white/15 px-2 py-1 backdrop-blur-sm">
                                <x-loop-logo class="h-5 w-5" />
                                <span class="text-[10px] font-semibold uppercase tracking-[0.12em]">Loop</span>
                            </div>
                        </div>
                        <p class="relative mt-6 font-display text-2xl font-semibold leading-snug sm:text-3xl" x-text="text()"></p>
                        <p class="relative mt-4 text-xs font-semibold opacity-80">{{ $business->hotline ?: '' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-[1.5rem] border border-ink/8 bg-white/95 p-4 shadow-[0_12px_36px_rgba(17,17,20,0.05)] sm:p-5">
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
                        <label class="loop-label">{{ __('loop.studio_upload_photo') }}</label>
                        <input type="file" accept="image/*" class="loop-input" @change="onImage($event)">
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.studio_text_color') }}</label>
                        <div class="mt-2 flex gap-2">
                            <button type="button" class="rounded-xl px-3 py-2 text-xs font-semibold" :class="textColor==='auto' ? 'bg-ink text-white' : 'bg-chalk'" @click="textColor='auto'">{{ __('loop.studio_auto') }}</button>
                            <button type="button" class="rounded-xl px-3 py-2 text-xs font-semibold" :class="textColor==='white' ? 'bg-ink text-white' : 'bg-chalk'" @click="textColor='white'">{{ __('loop.studio_white') }}</button>
                            <button type="button" class="rounded-xl px-3 py-2 text-xs font-semibold" :class="textColor==='ink' ? 'bg-ink text-white' : 'bg-chalk'" @click="textColor='ink'">{{ __('loop.studio_ink') }}</button>
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
                <button type="button" class="loop-btn-mint w-full" @click="go(2)">{{ __('loop.continue') }}</button>
            </div>

            <div class="mt-4 space-y-4" :class="step === 2 ? '' : 'hidden'">
                <p class="font-display text-lg font-semibold">{{ __('loop.studio_choose_size') }}</p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($sizes as $size)
                        <button type="button" class="rounded-2xl border px-3 py-3 text-left"
                                :class="sizeKey==='{{ $size['key'] }}' ? 'border-violet bg-violet-soft/50' : 'border-ink/10'"
                                @click="sizeKey='{{ $size['key'] }}'">
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
                <div class="max-h-56 space-y-2 overflow-y-auto">
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
    </div>
</x-app-layout>
