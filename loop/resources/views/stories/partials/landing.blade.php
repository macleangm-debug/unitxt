@if (($landingStories ?? collect())->isNotEmpty())
    <section class="relative z-10 border-t border-ink/10 bg-white/40">
        <div class="loop-shell py-14 sm:py-20">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.stories') }}</p>
                    <h2 class="mt-1 font-display text-2xl font-semibold sm:text-3xl">{{ __('loop.stories_title') }}</h2>
                    <p class="mt-2 max-w-xl text-sm text-ink-muted sm:text-base">{{ __('loop.stories_blurb') }}</p>
                </div>
                <a href="{{ route('stories.index') }}" class="text-sm font-semibold text-violet hover:text-ink">{{ __('loop.more_stories') }} →</a>
            </div>
            <div class="mt-8 space-y-4">
                @foreach ($landingStories as $story)
                    <x-story-teaser :story="$story" />
                @endforeach
            </div>
        </div>
    </section>
@endif
