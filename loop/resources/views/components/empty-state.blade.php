@props([
    'title' => null,
    'blurb' => null,
    'cta' => null,
    'url' => null,
])

<div {{ $attributes->class(['rounded-[1.6rem] border border-dashed border-ink/15 bg-white/70 px-6 py-12 text-center']) }}>
    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-soft text-violet">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h10M4 17h7" />
        </svg>
    </div>
    @if ($title)
        <p class="mt-4 font-display text-xl font-semibold text-ink">{{ $title }}</p>
    @endif
    @if ($blurb)
        <p class="mx-auto mt-2 max-w-sm text-sm text-ink-muted">{{ $blurb }}</p>
    @endif
    @if ($cta && $url)
        <a href="{{ $url }}" class="loop-btn-mint mt-6 inline-flex" @click="$store.loopNav.go(@js($url), $event, { kind: 'push' })">{{ $cta }}</a>
    @endif
    {{ $slot }}
</div>
