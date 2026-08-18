<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start gap-3">
            <x-back-icon :href="route('stories.index')" />
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ $article->country ? $article->countryLabel() : __('loop.story_general') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ $article->title() }}</h1>
                @if ($article->published_at)
                    <p class="mt-1 text-sm text-ink-muted">{{ $article->published_at->format('d M Y') }}</p>
                @endif
            </div>
        </div>
    </x-slot>

    <article class="overflow-hidden rounded-[1.75rem] border border-white/55 bg-white/75 shadow-[0_12px_40px_rgba(17,17,20,0.05)]">
        @if ($article->imageUrl())
            <img src="{{ $article->imageUrl() }}" alt="" class="h-56 w-full object-cover sm:h-72">
        @endif
        <div class="space-y-4 p-5 sm:p-8">
            @if ($article->excerpt())
                <p class="text-lg text-ink-muted">{{ $article->excerpt() }}</p>
            @endif
            <div class="prose-loop text-sm leading-relaxed text-ink">{!! $article->bodyHtml() !!}</div>
        </div>
    </article>

    @if ($more->isNotEmpty())
        <section class="mt-10">
            <x-section-heading
                :eyebrow="__('loop.stories')"
                :title="__('loop.more_stories')"
                :href="route('stories.index')"
                :link="__('loop.view_all').' →'"
                class="mb-5"
            />
            <div class="space-y-3">
                @foreach ($more as $story)
                    <a href="{{ route('stories.show', $story) }}" class="flex items-center gap-3 rounded-[1.25rem] border border-ink/8 bg-white/70 p-3">
                        @if ($story->imageUrl())
                            <img src="{{ $story->imageUrl() }}" alt="" class="h-16 w-16 rounded-2xl object-cover">
                        @else
                            <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-ink font-display text-xl font-semibold text-lime">{{ mb_substr($story->title(), 0, 1) }}</span>
                        @endif
                        <span class="min-w-0">
                            <span class="block truncate font-semibold">{{ $story->title() }}</span>
                            <span class="mt-0.5 block truncate text-xs text-ink-muted">{{ $story->excerpt() }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
