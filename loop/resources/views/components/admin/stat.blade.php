@props([
    'label' => '',
    'value' => '',
    'hint' => null,
])

<div class="admin-stat">
    <p class="admin-stat__label">{{ $label }}</p>
    <p class="admin-stat__value">{{ $value }}</p>
    @if ($hint)
        <p class="admin-stat__hint">{{ $hint }}</p>
    @endif
    {{ $slot }}
</div>
