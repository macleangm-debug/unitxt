<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.stories') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.stories_title') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.stories_blurb') }}</p>
        </div>
    </x-slot>

    <div class="space-y-4">
        @forelse ($stories as $story)
            <a href="{{ route('stories.show', $story) }}" class="flex overflow-hidden rounded-[1.5rem] border border-white/55 bg-white/70 shadow-[0_12px_40px_rgba(17,17,20,0.05)]">
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
        @empty
            <p class="rounded-[1.5rem] border border-dashed border-ink/15 px-5 py-10 text-center text-sm text-ink-muted">{{ __('loop.no_stories_yet') }}</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $stories->links() }}</div>
</x-app-layout>
