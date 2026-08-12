@props([
    'business',
    'points' => null,
    'showPoints' => false,
    'carousel' => false,
    'footnote' => null,
])

@php
    $campaign = $business->campaigns?->first();
    $sectorLabel = \App\Support\Sectors::label($business->sector, $business->sector_other);
    $branchCount = $business->shops_count ?? $business->shops?->count() ?? 0;
    $city = $business->shops?->first()?->city ?: $business->city;
    $href = auth()->user()?->isCustomer() && $points !== null
        ? route('memberships.show', $business)
        : route('discover.show', $business);
    $sizeClass = $carousel ? 'group w-40 shrink-0 sm:w-44' : 'group block w-full min-w-0';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $sizeClass]) }}>
    <div class="overflow-hidden rounded-[1.5rem] border border-white/55 bg-white/55 shadow-[0_12px_40px_rgba(17,17,20,0.06)] backdrop-blur-xl transition group-hover:-translate-y-1">
        <div class="relative aspect-[4/5] overflow-hidden bg-ink" @if($carousel) data-loop-parallax @endif>
            @if ($business->logoUrl())
                <img src="{{ $business->logoUrl() }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            @else
                <div class="flex h-full w-full flex-col justify-between bg-gradient-to-br from-ink via-[#1a1228] to-violet/50 p-4">
                    <span class="font-display text-3xl font-semibold text-lime">{{ mb_substr($business->name, 0, 1) }}</span>
                    <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white/55">{{ $sectorLabel }}</span>
                </div>
            @endif
            @if ($showPoints && $points !== null)
                <div class="absolute bottom-3 left-3 rounded-xl bg-lime px-2.5 py-1 text-xs font-semibold text-ink">
                    {{ number_format($points) }} pts
                </div>
            @elseif ($branchCount > 1)
                <div class="absolute bottom-3 left-3 rounded-xl bg-ink/80 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur">
                    {{ $branchCount }} {{ __('loop.branches') }}
                </div>
            @endif
        </div>
        <div class="p-3">
            <p class="truncate text-sm font-semibold">{{ $business->name }}</p>
            <p class="truncate text-[11px] text-ink-muted">{{ $sectorLabel }}@if($city) · {{ $city }}@endif</p>
            @if ($footnote)
                <p class="mt-1 truncate text-[11px] font-medium text-violet">{{ $footnote }}</p>
            @elseif ($campaign)
                <p class="mt-1 truncate text-[11px] font-medium text-violet">{{ $campaign->ruleSummary($business->currency) }}</p>
            @endif
        </div>
    </div>
</a>
