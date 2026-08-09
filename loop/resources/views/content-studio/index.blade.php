<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.settings') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.content_studio') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.content_studio_blurb') }}</p>
        </div>
    </x-slot>

    <div x-data="{
        design: 'mint_card',
        copyKey: @js($copies[0]['key'] ?? 'now_on_loop'),
        lang: @js($locale === 'sw' ? 'sw' : 'en'),
        copies: @js(collect($copies)->keyBy('key')),
        text() { return this.copies[this.copyKey]?.[this.lang] || ''; }
    }" class="grid gap-8 lg:grid-cols-[0.9fr_1.1fr]">
        <div class="space-y-5">
            <section>
                <p class="loop-label">{{ __('loop.studio_language') }}</p>
                <div class="mt-2 flex gap-2">
                    <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold" :class="lang==='en' ? 'bg-ink text-white' : 'bg-white ring-1 ring-ink/10'" @click="lang='en'">EN</button>
                    <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold" :class="lang==='sw' ? 'bg-ink text-white' : 'bg-white ring-1 ring-ink/10'" @click="lang='sw'">SW</button>
                </div>
            </section>

            <section>
                <p class="loop-label">{{ __('loop.studio_copy') }}</p>
                <div class="mt-2 space-y-2">
                    @foreach ($copies as $copy)
                        <label class="flex cursor-pointer gap-3 rounded-2xl border border-ink/10 bg-white px-4 py-3 has-[:checked]:border-mint has-[:checked]:bg-mint-soft/40">
                            <input type="radio" class="mt-1" name="copy" value="{{ $copy['key'] }}" x-model="copyKey">
                            <span class="text-sm">{{ $locale === 'sw' ? $copy['sw'] : $copy['en'] }}</span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section>
                <p class="loop-label">{{ __('loop.studio_design') }}</p>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    @foreach ($designs as $d)
                        <button type="button" class="rounded-2xl border px-3 py-3 text-left text-sm font-semibold"
                                :class="design==='{{ $d['key'] }}' ? 'border-mint bg-mint-soft/40' : 'border-ink/10 bg-white'"
                                @click="design='{{ $d['key'] }}'">{{ $d['name'] }}</button>
                    @endforeach
                </div>
            </section>

            <div class="flex flex-wrap gap-3">
                <button type="button" class="loop-btn-mint" @click="
                    const node = document.getElementById('studio-card');
                    if (!node) return;
                    // Simple download via SVG foreignObject fallback: open print dialog
                    window.print();
                ">{{ __('loop.download_creative') }}</button>
                <button type="button" class="loop-btn-ghost" @click="
                    const text = text() + ' — ' + @js($business->name) + ' on Loop';
                    if (navigator.share) { navigator.share({ title: @js($business->name), text }); }
                    else { navigator.clipboard.writeText(text); alert(@js(__('loop.copied'))); }
                ">{{ __('loop.share_creative') }}</button>
            </div>
        </div>

        <div class="flex items-start justify-center">
            <div id="studio-card" class="relative w-full max-w-md overflow-hidden rounded-[2rem] p-8 shadow-[0_30px_80px_rgba(11,31,42,0.18)]"
                 :class="{
                    'bg-gradient-to-br from-mint to-mint-deep text-ink': design==='mint_card',
                    'bg-ink text-white': design==='ink_bold',
                    'bg-gradient-to-br from-coral to-[#ff8f75] text-ink': design==='coral_pop',
                    'bg-[#F7F3EA] text-ink ring-1 ring-ink/10': design==='cream_soft'
                 }">
                <div class="flex items-center gap-3">
                    @if ($business->logoUrl())
                        <img src="{{ $business->logoUrl() }}" alt="" class="h-14 w-14 rounded-2xl object-cover">
                    @else
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 font-display text-xl font-semibold">{{ mb_substr($business->name,0,1) }}</div>
                    @endif
                    <div>
                        <p class="font-display text-xl font-semibold">{{ $business->name }}</p>
                        <p class="text-xs opacity-70">Loop</p>
                    </div>
                </div>
                <p class="mt-10 font-display text-3xl font-semibold leading-tight" x-text="text()"></p>
                @if ($business->hotline)
                    <p class="mt-8 text-sm font-semibold opacity-80">{{ $business->hotline }}</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
