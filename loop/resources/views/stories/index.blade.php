<x-story-page :title="__('loop.stories_title')">
    <x-slot:header>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.stories') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.stories_title') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.stories_blurb') }}</p>
        </div>
    </x-slot:header>

    <div class="space-y-4">
        @forelse ($stories as $story)
            <x-story-teaser :story="$story" />
        @empty
            <p class="rounded-[1.5rem] border border-dashed border-ink/15 px-5 py-10 text-center text-sm text-ink-muted">{{ __('loop.no_stories_yet') }}</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $stories->links() }}</div>
</x-story-page>
