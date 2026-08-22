@props([
    'title',
    'searchPlaceholder',
    'search' => true,
])

<template x-teleport="body">
    <div
        x-show="open"
        x-transition:enter="loop-sheet-enter-active"
        x-transition:enter-start="loop-sheet-enter-from"
        x-transition:enter-end="loop-sheet-enter-to"
        x-transition:leave="loop-sheet-leave-active"
        x-transition:leave-start="loop-sheet-leave-from"
        x-transition:leave-end="loop-sheet-leave-to"
        class="loop-picker-layer"
        x-cloak
        style="display: none;"
        x-effect="document.documentElement.classList.toggle('loop-picker-open', open)"
        @keydown.escape.window="open = false"
        role="presentation"
    >
        <div class="loop-picker-backdrop" @click="open = false"></div>
        <div
            class="loop-picker-panel"
            x-ref="sheetPanel"
            @click.stop
            @touchstart.passive="window.loopSheet?.down($event, $refs.sheetPanel, () => open, (v) => { open = v }, () => String(q || '').trim() !== '')"
            @touchmove="window.loopSheet?.move($event)"
            @touchend="window.loopSheet?.up()"
            @touchcancel="window.loopSheet?.up()"
            role="dialog"
            aria-modal="true"
            aria-label="{{ $title }}"
        >
            <div class="loop-picker-handle"></div>
            <div class="loop-picker-head">
                <p class="loop-picker-title">{{ $title }}</p>
                @if ($search)
                    <input x-ref="search" type="search" x-model="q" placeholder="{{ $searchPlaceholder }}" class="loop-picker-search" autocomplete="off">
                @endif
            </div>
            <div class="loop-picker-list">
                {{ $slot }}
            </div>
        </div>
    </div>
</template>
