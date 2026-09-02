<form method="GET" action="{{ route('help') }}" class="mt-5">
    <div class="loop-discover-search">
        <label class="sr-only" for="help-q">{{ __('loop.search') }}</label>
        <input id="help-q" type="search" name="q" value="{{ $search }}" placeholder="{{ __('loop.search_help') }}" class="loop-input" autocomplete="off">
        <button class="loop-btn">{{ __('loop.search') }}</button>
    </div>
</form>

<div class="mt-8 space-y-8">
    @forelse ($groups as $group)
        <section>
            <h2 class="font-display text-xl font-semibold">{{ $group['title'] }}</h2>
            <div class="mt-3 space-y-2">
                @foreach ($group['items'] as $item)
                    <details class="loop-panel group px-5 py-4">
                        <summary class="cursor-pointer list-none font-display text-base font-semibold">{{ $item['q'] }}</summary>
                        <p class="mt-1.5 text-sm text-ink-muted">{{ $item['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </section>
    @empty
        <p class="text-sm text-ink-muted">{{ __('loop.help_no_results') }}</p>
    @endforelse
</div>
