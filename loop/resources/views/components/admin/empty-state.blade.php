@props([
    'title' => null,
    'empty' => false,
    'message' => null,
])

@if ($empty)
    <div class="rounded-[1.5rem] border border-dashed border-ink/15 bg-white/50 px-5 py-8 text-center">
        @if ($title)
            <p class="font-display text-lg font-semibold text-ink">{{ $title }}</p>
        @endif
        <p class="mt-2 text-sm text-ink-muted">{{ $message ?? __('loop.no_information') }}</p>
    </div>
@else
    {{ $slot }}
@endif
