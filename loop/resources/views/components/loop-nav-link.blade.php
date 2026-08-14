@props([
    'href',
    'active' => false,
    'kind' => 'tab', // tab | push | fade
])

@php
    $classes = ($active ?? false)
        ? 'inline-flex items-center border-b-2 border-violet px-1 pt-1 text-sm font-semibold leading-5 text-ink transition duration-150 ease-in-out focus:outline-none'
        : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium leading-5 text-ink-muted transition duration-150 ease-in-out hover:border-ink/20 hover:text-ink focus:outline-none';
@endphp

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => $classes]) }}
    @click="$store.loopNav.go(@js($href), $event, { kind: @js($kind) })"
>
    {{ $slot }}
</a>
