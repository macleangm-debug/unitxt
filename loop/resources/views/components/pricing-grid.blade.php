@props([
    'plans',
    'ctaRoute' => null,
    'ctaLabel' => null,
    'highlight' => 'growth',
    'showTrialNote' => true,
    'compact' => false,
    'animate' => true,
])

@php
    $ctaRoute = $ctaRoute ?? route('business.register');
    $ctaLabel = $ctaLabel ?? __('loop.get_started');
@endphp

<div class="{{ $compact ? 'mt-8' : 'mt-10' }} grid gap-4 sm:grid-cols-2 {{ $plans->count() >= 4 ? 'xl:grid-cols-4' : 'lg:grid-cols-3' }}">
    @foreach ($plans as $index => $plan)
        @php $isHighlight = $plan->key === $highlight; @endphp
        <div
            @class([
                'loop-pricing-card',
                'loop-reveal' => $animate,
                'loop-pricing-card--featured' => $isHighlight,
                'border-ink/10 bg-white' => ! $isHighlight,
            ])
            @if ($animate)
                x-data="loopReveal({{ 60 + ($index * 70) }})"
                :class="{ 'is-shown': shown }"
            @endif
        >
            @if ($isHighlight)
                <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-violet/25 blur-2xl" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-10 -left-6 h-24 w-24 rounded-full bg-lime/25 blur-2xl" aria-hidden="true"></div>
                <p class="relative mb-3 inline-flex w-fit rounded-full bg-ink px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-lime">{{ __('loop.most_popular') }}</p>
            @endif
            <p class="relative font-display text-xl font-semibold">{{ $plan->name }}</p>
            @php
                $taglineKey = 'loop.plan_'.$plan->key.'_tagline';
                $translatedTagline = __($taglineKey);
                $tagline = $translatedTagline === $taglineKey ? $plan->tagline : $translatedTagline;
            @endphp
            <p class="relative mt-1 min-h-[2.5rem] text-sm text-ink-muted">{{ $tagline }}</p>
            <p class="relative mt-5 font-display text-3xl font-semibold tracking-tight">{{ $plan->priceLabel() }}</p>
            <ul class="relative mt-5 flex-1 space-y-2.5 text-sm text-ink-muted">
                @foreach ($plan->features ?? [] as $feature)
                    <li class="flex gap-2">
                        <span class="mt-0.5 text-violet">✓</span>
                        <span>{{ $feature }}</span>
                    </li>
                @endforeach
            </ul>
            @if ($plan->key === 'free')
                <a href="{{ $ctaRoute }}" class="loop-btn relative mt-6 w-full">{{ __('loop.start_trial') }}</a>
            @else
                <a href="{{ $ctaRoute }}{{ str_contains($ctaRoute, '?') ? '&' : '?' }}plan={{ $plan->key }}" @class([
                    'relative mt-6 inline-flex w-full justify-center rounded-2xl px-4 py-3.5 text-sm font-semibold transition',
                    'bg-violet text-white hover:bg-violet-deep' => $isHighlight,
                    'bg-ink text-white hover:bg-ink-soft' => ! $isHighlight,
                ])>{{ $ctaLabel }}</a>
            @endif
        </div>
    @endforeach
</div>

@if ($showTrialNote)
    <p class="mx-auto mt-8 max-w-2xl text-center text-sm text-ink-muted">{{ __('loop.pricing_referral_note') }}</p>
@endif
