@props([
    'action',
    'title',
    'body',
])

<div x-data="{ open: false }" class="contents">
    <button type="button" {{ $attributes->merge(['class' => 'loop-btn-ghost !py-2.5']) }} @click="open = true">
        {{ __('loop.pause_campaign') }}
    </button>

    <x-loop-sheet :title="$title">
        <p class="text-base font-medium leading-relaxed text-ink-muted">{{ $body }}</p>
        <form method="POST" action="{{ $action }}" class="mt-6 space-y-3">
            @csrf
            <button type="submit" class="loop-btn-danger w-full">{{ __('loop.confirm_pause') }}</button>
            <button type="button" class="loop-btn-ghost w-full" @click="open = false">{{ __('loop.cancel') }}</button>
        </form>
    </x-loop-sheet>
</div>
