@props([
    'business',
    'points' => null,
    'showPoints' => false,
    'carousel' => false,
    'footnote' => null,
    'headline' => null,
])

@php
    $campaign = $business->campaigns?->first();
    $sectorLabel = \App\Support\Sectors::label($business->sector, $business->sector_other);
    $branchCount = $business->shops_count ?? $business->shops?->count() ?? 0;
    $city = $business->shops?->first()?->city ?: $business->city;
    $isMemberLink = auth()->user()?->isCustomer() && $points !== null;
    $href = $isMemberLink
        ? route('memberships.show', $business)
        : route('discover.show', $business);
    $sizeClass = $carousel ? 'group w-40 shrink-0 sm:w-44' : 'group block w-full min-w-0';
    $morphId = 'business-'.$business->id;
    $rewards = $business->rewards ?? collect();
    $readyReward = $points !== null
        ? $rewards->first(fn ($reward) => (int) $reward->points_cost <= (int) $points)
        : null;
    $nextReward = $rewards->first(fn ($reward) => $points === null || (int) $reward->points_cost > (int) $points);
    $needed = ($nextReward && $points !== null)
        ? max(0, (int) $nextReward->points_cost - (int) $points)
        : null;
    $almost = $nextReward && $needed !== null && (int) $nextReward->points_cost > 0
        && ($points / $nextReward->points_cost) >= 0.7
        && $needed > 0;
    if ($headline === null && $readyReward) {
        $headline = $readyReward->name;
    }
    if ($footnote === null && $readyReward && $points !== null) {
        $footnote = __('loop.offer_ready_hint_short');
    } elseif ($footnote === null && $almost) {
        $footnote = __('loop.almost_there');
    } elseif ($footnote === null && $nextReward && $needed !== null && $needed > 0) {
        $footnote = __('loop.pts_to_unlock_named', ['points' => $needed, 'offer' => $nextReward->name]);
    }
    $hay = strtolower(trim(implode(' ', array_filter([
        $business->name,
        $sectorLabel,
        $city,
        $headline,
        $footnote,
        $campaign?->name,
    ]))));
@endphp

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => $sizeClass, 'data-loop-hay' => $hay]) }}
    x-data
    @if ($isMemberLink)
        @click="$store.loopNav.go($el.href, $event, { morph: $refs.logo })"
    @endif
>
    <div class="loop-press-card overflow-hidden rounded-[1.5rem] border border-white/55 bg-white/80 shadow-[0_12px_40px_rgba(17,17,20,0.06)] transition group-hover:-translate-y-0.5">
        <div class="relative aspect-[4/5] overflow-hidden bg-ink" @if($carousel) data-loop-parallax @endif>
            <span
                @if ($isMemberLink) x-ref="logo" data-loop-morph="{{ $morphId }}" @endif
                class="loop-morph-logo absolute inset-0 block"
            >
                @if ($business->logoUrl())
                    <img src="{{ $business->logoUrl() }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" draggable="false">
                @else
                    <span class="flex h-full w-full flex-col justify-start bg-gradient-to-br from-ink via-[#1a1228] to-violet/50 p-4">
                        <span class="font-display text-3xl font-semibold text-lime">{{ mb_substr($business->name, 0, 1) }}</span>
                    </span>
                @endif
            </span>
            @if ($showPoints && $points !== null)
                <div class="absolute right-3 top-3 z-[1] rounded-xl bg-lime px-2.5 py-1 text-xs font-semibold text-ink">
                    {{ number_format($points) }} pts
                </div>
            @elseif ($branchCount > 1)
                <div class="absolute right-3 top-3 z-[1] rounded-xl bg-ink/80 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur">
                    {{ $branchCount }} {{ __('loop.branches') }}
                </div>
            @endif
        </div>
        <div class="p-3">
            <p class="truncate text-sm font-semibold">{{ $business->name }}</p>
            <p class="truncate text-[11px] text-ink-muted">{{ $sectorLabel }}@if($city) · {{ $city }}@endif</p>
            @if ($headline)
                <p class="mt-1.5 truncate font-display text-sm font-semibold text-ink">{{ $headline }}</p>
            @endif
            @if ($footnote)
                <p class="mt-1 truncate text-[11px] font-medium text-violet">{{ $footnote }}</p>
            @elseif ($campaign)
                <p class="mt-1 truncate text-[11px] font-medium text-violet">{{ $campaign->ruleSummary($business->currency) }}</p>
            @endif
            @if ($nextReward && $needed !== null && (int) $nextReward->points_cost > 0 && ! $readyReward)
                <div class="loop-hbar mt-2 h-1.5">
                    <span class="loop-fill block h-full rounded-full bg-violet" style="width: {{ min(100, (int) round(($points / $nextReward->points_cost) * 100)) }}%"></span>
                </div>
            @endif
            <p class="mt-2 text-[11px] font-semibold text-ink-muted">{{ __('loop.view') }}</p>
        </div>
    </div>
</a>
