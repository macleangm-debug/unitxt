@props([
    'live' => true,
    'size' => 'sm',
])

@php
    $classes = $size === 'lg'
        ? 'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-bold uppercase tracking-wide text-white'
        : 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-white';
@endphp

@if ($live)
    <span {{ $attributes->class([$classes, 'bg-mint shadow-[0_0_0_3px_rgba(46,125,50,0.22)]']) }}>
        <span @class(['animate-pulse rounded-full bg-white', 'h-2 w-2' => $size === 'lg', 'h-1.5 w-1.5' => $size !== 'lg'])></span>
        {{ __('loop.live') }}
    </span>
@else
    <span {{ $attributes->class([$classes, 'bg-coral shadow-[0_0_0_3px_rgba(255,79,112,0.22)]']) }}>
        {{ __('loop.paused') }}
    </span>
@endif
