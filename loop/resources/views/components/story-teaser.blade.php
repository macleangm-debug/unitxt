@props(['story'])

<a href="{{ route('stories.show', $story) }}" {{ $attributes->merge(['class' => 'flex overflow-hidden rounded-[1.5rem] border border-white/55 bg-white/70 shadow-[0_12px_40px_rgba(17,17,20,0.05)]']) }}>
    @if ($story->imageUrl())
        <img src="{{ $story->imageUrl() }}" alt="" class="h-28 w-28 shrink-0 object-cover sm:h-32 sm:w-40">
    @else
        <span class="flex h-28 w-28 shrink-0 items-center justify-center bg-gradient-to-br from-ink to-violet/60 font-display text-3xl font-semibold text-lime sm:h-32 sm:w-40">
            {{ mb_substr($story->title(), 0, 1) }}
        </span>
    @endif
    <span class="min-w-0 p-4">
        <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-violet">{{ $story->country ? $story->countryLabel() : __('loop.story_general') }}</span>
        <span class="mt-1 block font-display text-lg font-semibold">{{ $story->title() }}</span>
        <span class="mt-1 block text-sm text-ink-muted">{{ $story->excerpt() }}</span>
    </span>
</a>
