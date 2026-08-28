<form method="GET" action="{{ route('legal.index') }}" class="mt-5">
    <div class="loop-discover-search">
        <label class="sr-only" for="legal-q">{{ __('loop.search_documents') }}</label>
        <input id="legal-q" type="search" name="q" value="{{ $search }}" placeholder="{{ __('loop.search_documents') }}" class="loop-input" autocomplete="off">
        <button class="loop-btn">{{ __('loop.search') }}</button>
    </div>
</form>

<div class="mt-8 space-y-8">
    @foreach ($groups as $key => $label)
        @if (! empty($byGroup[$key]))
            <section>
                <h2 class="font-display text-xl font-semibold">{{ $label }}</h2>
                <div class="mt-3 divide-y divide-ink/8 overflow-hidden rounded-[1.5rem] border border-ink/8 bg-white/90">
                    @foreach ($byGroup[$key] as $doc)
                        <a href="{{ route('legal.show', $doc->slug) }}" class="loop-more-row">
                            <span class="loop-more-row__icon"><x-loop-icon name="legal" class="h-5 w-5" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold">{{ $doc->title() }}</span>
                                <span class="mt-0.5 block text-[12px] text-ink-muted">{{ __('loop.legal_version_date', ['version' => $doc->version, 'date' => optional($doc->effective_on)->format('d M Y')]) }} · {{ $doc->summary() }}</span>
                            </span>
                            <span class="text-ink-muted">→</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach
</div>
