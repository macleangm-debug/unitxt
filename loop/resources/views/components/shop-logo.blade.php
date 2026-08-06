@props(['shop'])
@php
    $business = $shop->business;
    $name = $business?->name ?: $shop->name;
    $initials = collect(explode(' ', $name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    $logo = $business?->logo_path;
@endphp
@if ($logo)
    <img src="{{ asset('storage/'.$logo) }}" alt="{{ $name }}" {{ $attributes->merge(['class' => 'h-12 w-12 rounded-2xl object-cover']) }}>
@else
    <x-shop-mark :initials="$initials" {{ $attributes->merge(['class' => 'h-12 w-12']) }} />
@endif
