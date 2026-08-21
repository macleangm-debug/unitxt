<div class="mx-auto mb-3 h-1 w-10 rounded-full bg-ink/15"></div>
<div class="mb-3 flex items-center justify-between gap-3">
    <div class="flex gap-1 rounded-full bg-chalk p-1">
        <button type="button" class="rounded-full px-3 py-1 text-xs font-semibold" :class="lang==='en' ? 'bg-ink text-white' : 'text-ink-muted'" @click="lang='en'">EN</button>
        <button type="button" class="rounded-full px-3 py-1 text-xs font-semibold" :class="lang==='sw' ? 'bg-ink text-white' : 'text-ink-muted'" @click="lang='sw'">SW</button>
    </div>
    <button type="button" class="text-sm font-semibold text-mint-deep" @click="editOpen = false">{{ __('loop.done') }}</button>
</div>

<div class="flex gap-1">
    @foreach ($topics as $topicRow)
        <button
            type="button"
            class="min-w-0 flex-1 truncate rounded-full px-2 py-2 text-[11px] font-semibold"
            :class="topic === @js($topicRow['key']) ? 'bg-ink text-white' : 'bg-chalk text-ink'"
            @click="setTopic(@js($topicRow['key']))"
        >{{ $topicRow['label'] }}</button>
    @endforeach
</div>

<template x-if="topicCopies().length">
    <div class="mt-3 rounded-2xl bg-chalk px-3 py-3">
        <div class="flex items-start justify-between gap-3">
            <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.studio_copy') }}</p>
            <p class="text-[11px] font-semibold text-ink-muted" x-text="copyPosition()"></p>
        </div>
        <p class="mt-1 text-sm font-semibold leading-snug" x-text="headline()"></p>
        <button type="button" class="mt-2 text-sm font-semibold text-mint-deep" @click="tryAnother()" x-show="topicCopies().length > 1">{{ __('loop.studio_try_another') }}</button>
    </div>
</template>
<template x-if="!topicCopies().length">
    <div class="mt-3 rounded-2xl bg-chalk px-3 py-3">
        <p class="text-sm font-semibold" x-text="emptyHints[topic]?.title"></p>
        <a class="mt-1 inline-flex text-sm font-semibold text-mint-deep" :href="emptyHints[topic]?.url" x-text="emptyHints[topic]?.cta"></a>
    </div>
</template>

<div class="mt-3 flex items-center gap-2">
    <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold" :class="look === 'plain' ? 'bg-ink text-white' : 'bg-chalk text-ink'" @click="look = 'plain'">{{ __('loop.studio_look_plain') }}</button>
    <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold" :class="look === 'photo' ? 'bg-ink text-white' : 'bg-chalk text-ink'" @click="look = 'photo'">{{ __('loop.studio_look_photo') }}</button>
    <div class="ml-auto flex items-center gap-1.5">
        <template x-for="item in designs" :key="item.key">
            <button
                type="button"
                class="h-7 w-7 rounded-full ring-2 ring-offset-2"
                :class="design === item.key ? 'ring-ink' : 'ring-transparent'"
                :style="'background:' + item.accent"
                :aria-label="item.name"
                @click="design = item.key"
            ></button>
        </template>
    </div>
</div>

<div x-show="look === 'photo'" x-cloak class="mt-3 flex items-center gap-3">
    <button type="button" class="loop-btn-ghost !px-3 !py-2 text-sm" @click="$refs.photoInput.click()">
        <span x-show="!photoUrl">{{ __('loop.studio_add_photo') }}</span>
        <span x-show="photoUrl" x-cloak>{{ __('loop.studio_change_photo') }}</span>
    </button>
    <input x-show="photoUrl" x-cloak type="range" min="0" max="100" x-model.number="photoY" class="min-w-0 flex-1 accent-mint-deep" :aria-label="@js(__('loop.studio_adjust_photo'))">
</div>

<button type="button" class="mt-3 w-full text-center text-sm font-semibold text-ink-muted" @click="saveImage()" :disabled="saving">{{ __('loop.save_image') }}</button>
