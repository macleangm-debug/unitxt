@props([
    'plans',
    'ctaRoute' => null,
    'ctaLabel' => null,
    'highlight' => 'growth',
    'showTrialNote' => true,
    'compact' => false,
])

@php
    $ctaRoute = $ctaRoute ?? route('business.register');
    $ctaLabel = $ctaLabel ?? __('loop.get_started');
@endphp

<div class="{{ $compact ? 'mt-8' : 'mt-10' }} grid gap-4 sm:grid-cols-2 {{ $plans->count() >= 4 ? 'xl:grid-cols-4' : 'lg:grid-cols-3' }}">
    @foreach ($plans as $plan)
        @php $isHighlight = $plan->key === $highlight; @endphp
        <div @class([
            'relative flex flex-col overflow-hidden rounded-[1.75rem] border p-6 transition',
            'border-mint bg-gradient-to-b from-mint/15 to-white shadow-[0_24px_70px_rgba(45,212,168,0.18)] ring-2 ring-mint' => $isHighlight,
            'border-ink/10 bg-white/90 shadow-[0_20px_60px_rgba(11,31,42,0.06)] hover:-translate-y-0.5' => ! $isHighlight,
        ])>
            @if ($isHighlight)
                <p class="mb-3 inline-flex w-fit rounded-full bg-ink px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-mint">{{ __('loop.most_popular') }}</p>
            @endif
            <p class="font-display text-xl font-semibold">{{ $plan->name }}</p>
            <p class="mt-1 min-h-[2.5rem] text-sm text-ink-muted">{{ $plan->tagline }}</p>
            <p class="mt-5 font-display text-3xl font-semibold tracking-tight">{{ $plan->priceLabel() }}</p>
            <ul class="mt-5 flex-1 space-y-2.5 text-sm text-ink-muted">
                @foreach ($plan->features ?? [] as $feature)
                    <li class="flex gap-2">
                        <span class="mt-0.5 text-mint-deep">✓</span>
                        <span>{{ $feature }}</span>
                    </li>
                @endforeach
            </ul>
            @if ($plan->key === 'free')
                <a href="{{ $ctaRoute }}" @class([
                    'mt-6 inline-flex w-full justify-center rounded-xl px-4 py-3 text-sm font-semibold transition',
                    'bg-ink text-white hover:bg-black' => true,
                ])>{{ __('loop.start_trial') }}</a>
            @else
                <a href="{{ $ctaRoute }}{{ str_contains($ctaRoute, '?') ? '&' : '?' }}plan={{ $plan->key }}" @class([
                    'mt-6 inline-flex w-full justify-center rounded-xl px-4 py-3 text-sm font-semibold transition',
                    'bg-mint text-ink hover:bg-mint-deep' => $isHighlight,
                    'bg-ink text-white hover:bg-black' => ! $isHighlight,
                ])>{{ $ctaLabel }}</a>
            @endif
        </div>
    @endforeach
</div>

@if ($showTrialNote)
    <p class="mx-auto mt-8 max-w-2xl text-center text-sm text-ink-muted">{{ __('loop.pricing_referral_note') }}</p>
@endif
