<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.affiliates') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.affiliate_dashboard') }}</h1>
                <p class="mt-1 text-ink-muted">{{ $affiliate->name }} · {{ $affiliate->promo_code }}</p>
            </div>
            <div class="text-right text-xs text-ink-muted">
                <p>{{ __('loop.tracking_code') }}</p>
                <p class="mt-1 font-semibold text-ink">{{ $affiliate->tracking_code }}</p>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-[1.5rem] bg-ink p-5 text-white">
            <p class="text-xs text-white/55">{{ __('loop.signups') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $stats['signups'] }}</p>
        </div>
        <div class="rounded-[1.5rem] bg-mint-soft p-5">
            <p class="text-xs text-ink-muted">{{ __('loop.qualified') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $stats['qualified'] }}</p>
        </div>
        <div class="rounded-[1.5rem] border border-ink/8 bg-white p-5">
            <p class="text-xs text-ink-muted">{{ __('loop.earned') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ number_format($stats['earned']) }}</p>
        </div>
        <div class="rounded-[1.5rem] border border-ink/8 bg-white p-5">
            <p class="text-xs text-ink-muted">{{ __('loop.pending') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $stats['pending'] }}</p>
        </div>
    </div>

    <section class="mt-8 overflow-hidden rounded-[2rem] bg-gradient-to-br from-mint/25 to-white p-6 ring-1 ring-mint/20">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.your_promo_code') }}</p>
        <p class="mt-3 font-display text-4xl font-semibold tracking-tight">{{ $affiliate->promo_code }}</p>
        <p class="mt-2 text-sm text-ink-muted">{{ __('loop.share_promo_blurb') }}</p>
        <div class="mt-4 flex flex-wrap gap-2">
            <input readonly value="{{ $shareUrl }}" class="loop-input max-w-xl flex-1 text-sm" id="share-url">
            <button type="button" class="loop-btn-mint !py-2" onclick="navigator.clipboard.writeText(document.getElementById('share-url').value); this.textContent=@js(__('loop.copied'))">{{ __('loop.copy_link') }}</button>
        </div>
    </section>

    <section class="mt-10">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.your_referrals') }}</h2>
        <div class="mt-4 space-y-3">
            @forelse ($referrals as $row)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.5rem] border border-ink/8 bg-white/90 px-5 py-4">
                    <div>
                        <p class="font-semibold">{{ $row->business->name }}</p>
                        <p class="mt-1 text-sm text-ink-muted">
                            {{ __('loop.affiliate_ref_status_'.$row->status) }}
                            @if ($row->attribution_ends_at)
                                · {{ __('loop.attribution_until') }} {{ $row->attribution_ends_at->format('d M Y') }}
                            @endif
                        </p>
                    </div>
                    <p class="font-display text-xl font-semibold">{{ number_format($row->commission_amount) }}</p>
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.no_affiliate_referrals') }}</p>
            @endforelse
        </div>
    </section>
</x-app-layout>
