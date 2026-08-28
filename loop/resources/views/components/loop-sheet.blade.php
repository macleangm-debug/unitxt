@props([
    'title' => null,
    'model' => 'open',
    'show' => null,
    'variant' => 'auto',
    'lockSwipe' => 'false',
    'labelledby' => null,
])

@php
    $visible = $show ?: $model;
    $panelClass = $variant === 'list'
        ? 'loop-picker-panel'
        : 'loop-picker-panel loop-picker-panel--auto';
@endphp

<template x-teleport="body">
    <div
        x-show="{{ $visible }}"
        x-transition:enter="loop-sheet-enter-active"
        x-transition:enter-start="loop-sheet-enter-from"
        x-transition:enter-end="loop-sheet-enter-to"
        x-transition:leave="loop-sheet-leave-active"
        x-transition:leave-start="loop-sheet-leave-from"
        x-transition:leave-end="loop-sheet-leave-to"
        class="loop-picker-layer"
        x-cloak
        style="display: none;"
        x-effect="{{ $visible }}; window.loopSheet?.syncLock()"
        @keydown.escape.window="{{ $model }} = false"
        role="presentation"
        {{ $attributes }}
    >
        <div class="loop-picker-backdrop" @click="{{ $model }} = false"></div>
        <div
            class="{{ $panelClass }}"
            x-ref="sheetPanel"
            @click.stop
            @touchstart.passive="window.loopSheet?.down($event, $refs.sheetPanel, () => !!({{ $visible }}), (v) => { {{ $model }} = v }, () => !!({{ $lockSwipe }}))"
            @touchmove="window.loopSheet?.move($event)"
            @touchend="window.loopSheet?.up()"
            @touchcancel="window.loopSheet?.up()"
            role="dialog"
            aria-modal="true"
            @if ($title)
                aria-label="{{ $title }}"
            @elseif ($labelledby)
                aria-labelledby="{{ $labelledby }}"
            @endif
        >
            <div class="loop-picker-handle"></div>
            @if ($title)
                <div class="loop-picker-head">
                    <p class="loop-picker-title">{{ $title }}</p>
                </div>
            @endif
            <div class="loop-picker-body">
                {{ $slot }}
            </div>
        </div>
    </div>
</template>
