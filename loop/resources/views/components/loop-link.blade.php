@props([
    'href',
    'kind' => 'tab',
    'current' => false,
])

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => '']) }}
    @if ($current) aria-current="page" @endif
    @click="$store.loopNav.go(@js($href), $event, { kind: @js($kind) })"
>
    {{ $slot }}
</a>
