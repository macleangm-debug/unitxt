@props(['shop'])
@php
    $initials = collect(explode(' ', $shop->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    $logo = $shop->logo_path ?: $shop->business?->logo_path;
@endphp
@if ($logo)
    <img src="{{ asset('storage/'.$logo) }}" alt="{{ $shop->name }}" {{ $attributes->merge(['class' => 'h-12 w-12 rounded-2xl object-cover']) }}>
@else
    <x-shop-mark :initials="$initials" {{ $attributes->merge(['class' => 'h-12 w-12']) }} />
@endif
