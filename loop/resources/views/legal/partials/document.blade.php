@unless ($document->counsel_reviewed)
    <p class="mt-5 rounded-2xl bg-chalk px-4 py-3 text-sm text-ink-muted">{{ __('loop.legal_draft_banner') }}</p>
@endunless
<article class="loop-panel mt-5 p-5 text-sm leading-relaxed text-ink sm:p-7">
    {!! \App\Support\LegalDrafts::render($document->body()) !!}
</article>
