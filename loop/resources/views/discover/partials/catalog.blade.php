<section>
    <div class="flex items-start gap-3">
        <x-back-icon :href="route('discover.show', $business)" />
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ $business->name }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ $title }}</h1>
        </div>
    </div>

    <div class="mt-6 grid gap-2.5 sm:grid-cols-2">
        @forelse ($items as $item)
            <div class="rounded-2xl border border-ink/10 bg-white px-4 py-3.5">
                @if ($kind === 'campaigns')
                    <p class="font-semibold leading-snug">{{ $item->displayName() }}</p>
                    <p class="mt-0.5 text-sm text-ink-muted">{{ $item->ruleSummary($business->currency) }}</p>
                @elseif ($kind === 'offers')
                    <p class="font-semibold leading-snug">{{ $item->name }}</p>
                    <p class="mt-0.5 text-sm text-ink-muted">{{ number_format((int) $item->points_cost) }} {{ __('loop.pts') }} · {{ $item->label() }}</p>
                @else
                    <p class="font-semibold leading-snug">{{ $item->name }}</p>
                    <p class="mt-0.5 text-sm text-ink-muted">
                        {{ $item->prize_name }}
                        · {{ __('loop.freq_'.$item->frequency) }}
                        · {{ __('loop.raffle_draw_day', ['day' => $item->nextDrawDate()->format('j M')]) }}
                    </p>
                @endif
            </div>
        @empty
            <p class="text-sm text-ink-muted">
                @if ($kind === 'campaigns')
                    {{ __('loop.no_live_campaigns') }}
                @elseif ($kind === 'offers')
                    {{ __('loop.no_offers_yet') }}
                @else
                    {{ __('loop.no_live_raffles') }}
                @endif
            </p>
        @endforelse
    </div>

    @if ($items->hasPages())
        <div class="mt-6">
            {{ $items->links() }}
        </div>
    @endif
</section>
