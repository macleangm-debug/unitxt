@props([
    'value' => 0,
    'duration' => 800,
    'earned' => 0,
    'from' => 0,
])
<span {{ $attributes }} x-data="loopCountUp({{ (int) $value }}, {{ (int) $duration }}, {{ (int) $earned }}, {{ (int) $from }})" x-text="formatted()">{{ number_format((int) $value) }}</span>
