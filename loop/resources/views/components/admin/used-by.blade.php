@props(['items' => []])

<p class="mt-2 text-xs text-ink-muted">
    <span class="font-semibold text-ink">{{ __('loop.used_by') }}:</span>
    {{ collect($items)->join(' · ') }}
</p>
