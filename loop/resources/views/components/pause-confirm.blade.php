@props([
    'action',
    'title',
    'body',
])

<div x-data="{ open: false }" class="contents">
    <button type="button" {{ $attributes->merge(['class' => 'loop-btn-ghost !py-2.5']) }} @click="open = true">
        {{ __('loop.pause_campaign') }}
    </button>

    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[80] flex items-center justify-center px-4"
        @keydown.escape.window="open = false"
    >
        <div class="absolute inset-0 bg-ink/60 backdrop-blur-sm" @click="open = false"></div>
        <div class="relative w-full max-w-md overflow-hidden rounded-[2rem] border border-ink/10 bg-white p-8 text-center shadow-[0_40px_100px_rgba(17,17,20,0.35)] sm:p-10" @click.stop>
            <p class="font-display text-3xl font-bold tracking-tight text-ink">{{ $title }}</p>
            <p class="mt-3 text-base font-medium leading-relaxed text-ink-muted sm:text-lg">{{ $body }}</p>
            <form method="POST" action="{{ $action }}" class="mt-8 space-y-3">
                @csrf
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-coral px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-coral/90">
                    {{ __('loop.confirm_pause') }}
                </button>
                <button type="button" class="loop-btn-ghost w-full" @click="open = false">{{ __('loop.cancel') }}</button>
            </form>
        </div>
    </div>
</div>
