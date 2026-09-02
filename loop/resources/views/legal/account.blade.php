<x-app-layout>
    <x-slot name="header">
        <h1 class="loop-page-title font-display text-3xl font-semibold">{{ __('loop.legal_documents') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.legal_account_blurb') }}</p>
    </x-slot>

    <div class="divide-y divide-ink/8 overflow-hidden rounded-[1.5rem] border border-ink/8 bg-white/90">
        @foreach ($docs as $doc)
            <a href="{{ route('legal.show', $doc->slug) }}" class="loop-more-row">
                <span class="min-w-0 flex-1 font-semibold">{{ $doc->title() }}</span>
                <span class="text-ink-muted">→</span>
            </a>
        @endforeach
    </div>

    @if ($acceptances->isNotEmpty())
        <section class="mt-8">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.agreements_accepted') }}</h2>
            <div class="mt-3 space-y-2">
                @foreach ($acceptances as $row)
                    <div class="rounded-[1.25rem] border border-ink/8 bg-white/80 px-4 py-3">
                        <p class="font-semibold">{{ $row->document?->title() }}</p>
                        <p class="mt-0.5 text-[12px] text-ink-muted">{{ __('loop.legal_accepted_meta', ['version' => $row->document?->version, 'date' => $row->accepted_at?->format('d M Y')]) }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mt-8">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.your_privacy_rights') }}</h2>
        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.your_privacy_rights_blurb') }}</p>
        <form method="POST" action="{{ route('legal.privacy-request') }}" class="mt-4 space-y-3">
            @csrf
            <label class="loop-label">{{ __('loop.privacy_request_kind') }}</label>
            <select name="kind" class="loop-input">
                <option value="access">{{ __('loop.privacy_kind_access') }}</option>
                <option value="correction">{{ __('loop.privacy_kind_correction') }}</option>
                <option value="export">{{ __('loop.privacy_kind_export') }}</option>
                <option value="erasure">{{ __('loop.privacy_kind_erasure') }}</option>
                <option value="restriction">{{ __('loop.privacy_kind_restriction') }}</option>
                <option value="objection">{{ __('loop.privacy_kind_objection') }}</option>
                <option value="consent_withdraw">{{ __('loop.privacy_kind_consent') }}</option>
                <option value="marketing">{{ __('loop.privacy_kind_marketing') }}</option>
                <option value="complaint">{{ __('loop.privacy_kind_complaint') }}</option>
            </select>
            <textarea name="detail" class="loop-input" rows="3" placeholder="{{ __('loop.privacy_request_detail') }}"></textarea>
            <button class="loop-btn">{{ __('loop.make_privacy_request') }}</button>
        </form>
    </section>
</x-app-layout>
