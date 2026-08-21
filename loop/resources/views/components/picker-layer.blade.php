@props([
    'title',
    'searchPlaceholder',
])

<template x-teleport="body">
    <div
        x-show="open"
        x-transition.opacity.duration.150ms
        class="loop-picker-layer"
        style="display: none;"
        x-effect="document.documentElement.classList.toggle('loop-picker-open', open)"
        @keydown.escape.window="open = false"
        role="presentation"
    >
        <div class="loop-picker-backdrop" @click="open = false"></div>
        <div class="loop-picker-panel" @click.stop role="dialog" aria-modal="true" aria-label="{{ $title }}">
            <div class="loop-picker-handle"></div>
            <div class="loop-picker-head">
                <p class="loop-picker-title">{{ $title }}</p>
                <input x-ref="search" type="search" x-model="q" placeholder="{{ $searchPlaceholder }}" class="loop-picker-search" autocomplete="off">
            </div>
            <div class="loop-picker-list">
                {{ $slot }}
            </div>
        </div>
    </div>
</template>
