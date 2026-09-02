<section>
    <p class="loop-label">{{ __('loop.studio_what_to_say') }}</p>
    <div class="mt-2 grid grid-cols-2 gap-2">
        @foreach ($topics as $topicRow)
            <button
                type="button"
                class="rounded-2xl border px-3 py-3 text-left text-sm font-semibold"
                :class="topic === @js($topicRow['key']) ? 'border-mint bg-mint-soft/40' : 'border-ink/10 bg-white'"
                @click="setTopic(@js($topicRow['key']))"
            >{{ $topicRow['label'] }}</button>
        @endforeach
    </div>
</section>

<section>
    <p class="loop-label">{{ __('loop.studio_copy') }}</p>
    <template x-if="topicCopies().length">
        <div class="mt-2 space-y-2">
            <template x-for="copy in topicCopies()" :key="copy.key">
                <button type="button" class="flex w-full cursor-pointer gap-3 rounded-2xl border px-4 py-3 text-left" :class="copyKey === copy.key ? 'border-mint bg-mint-soft/40' : 'border-ink/10 bg-white'" @click="copyKey = copy.key">
                    <span>
                        <span class="block text-sm font-medium" x-text="copy[lang] || copy.en"></span>
                        <span class="mt-1 block text-xs text-ink-muted" x-show="copy['support_' + lang] || copy.support_en" x-text="copy['support_' + lang] || copy.support_en"></span>
                    </span>
                </button>
            </template>
        </div>
    </template>
    <template x-if="!topicCopies().length">
        <div class="mt-2 rounded-2xl border border-ink/10 bg-white px-4 py-4">
            <p class="font-semibold" x-text="emptyHints[topic]?.title"></p>
            <p class="mt-1 text-sm text-ink-muted" x-text="emptyHints[topic]?.body"></p>
            <a class="mt-3 inline-flex text-sm font-semibold text-mint-deep" :href="emptyHints[topic]?.url" x-text="emptyHints[topic]?.cta"></a>
        </div>
    </template>
</section>

<section>
    <p class="loop-label">{{ __('loop.studio_look') }}</p>
    <div class="mt-2 flex gap-2">
        <button type="button" class="flex-1 rounded-2xl border px-3 py-2.5 text-sm font-semibold" :class="look === 'plain' ? 'border-mint bg-mint-soft/40' : 'border-ink/10 bg-white'" @click="look = 'plain'">{{ __('loop.studio_look_plain') }}</button>
        <button type="button" class="flex-1 rounded-2xl border px-3 py-2.5 text-sm font-semibold" :class="look === 'photo' ? 'border-mint bg-mint-soft/40' : 'border-ink/10 bg-white'" @click="look = 'photo'">{{ __('loop.studio_look_photo') }}</button>
    </div>
    <div x-show="look === 'photo'" x-cloak class="mt-3 space-y-3">
        <button type="button" class="loop-btn-ghost w-full" @click="$refs.photoInput.click()">
            <span x-show="!photoUrl">{{ __('loop.studio_add_photo') }}</span>
            <span x-show="photoUrl" x-cloak>{{ __('loop.studio_change_photo') }}</span>
        </button>
        <div x-show="photoUrl" x-cloak>
            <label class="loop-label">{{ __('loop.studio_adjust_photo') }}</label>
            <input type="range" min="0" max="100" x-model.number="photoY" class="mt-2 w-full accent-mint-deep">
        </div>
    </div>
</section>

<section>
    <p class="loop-label">{{ __('loop.studio_design') }}</p>
    <div class="mt-2 grid grid-cols-2 gap-2">
        @foreach ($designs as $d)
            <button type="button" class="rounded-2xl border px-3 py-3 text-left text-sm font-semibold"
                    :class="design === @js($d['key']) ? 'border-mint bg-mint-soft/40' : 'border-ink/10 bg-white'"
                    @click="design = @js($d['key'])">{{ $d['name'] }}</button>
        @endforeach
    </div>
    <button type="button" class="mt-3 text-sm font-semibold text-mint-deep" @click="tryAnother()">{{ __('loop.studio_try_another') }}</button>
</section>

<section>
    <p class="loop-label">{{ __('loop.studio_language') }}</p>
    <div class="mt-2 flex gap-2">
        <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold" :class="lang==='en' ? 'bg-ink text-white' : 'bg-white ring-1 ring-ink/10'" @click="lang='en'">EN</button>
        <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold" :class="lang==='sw' ? 'bg-ink text-white' : 'bg-white ring-1 ring-ink/10'" @click="lang='sw'">SW</button>
    </div>
</section>
