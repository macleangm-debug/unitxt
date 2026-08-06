@props([
    'shop',
    'points' => null,
    'showPoints' => false,
])

@php
    $campaign = $shop->business?->campaigns?->first();
    $sectorLabel = \App\Support\Sectors::label($shop->business?->sector);
    $href = auth()->user()?->isCustomer() && $points !== null
        ? route('memberships.show', $shop->business)
        : route('discover.show', $shop->business);
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'group w-40 shrink-0 sm:w-44']) }}>
    <div class="overflow-hidden rounded-3xl border border-ink/10 bg-white shadow-[0_12px_40px_rgba(11,31,42,0.06)] transition group-hover:-translate-y-1 group-hover:shadow-[0_18px_50px_rgba(11,31,42,0.1)]">
        <div class="relative aspect-[4/5] overflow-hidden bg-ink">
            @php $logo = $shop->logo_path ?: $shop->business?->logo_path; @endphp
            @if ($logo)
                <img src="{{ asset('storage/'.$logo) }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            @else
                <div class="flex h-full w-full flex-col justify-between bg-gradient-to-br from-ink via-ink-soft to-mint/40 p-4">
                    <span class="font-display text-3xl font-semibold text-mint">{{ mb_substr($shop->name, 0, 1) }}</span>
                    <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white/55">{{ $sectorLabel }}</span>
                </div>
            @endif
            @if ($showPoints && $points !== null)
                <div class="absolute bottom-3 left-3 rounded-xl bg-white/95 px-2.5 py-1 text-xs font-semibold text-ink shadow-sm">
                    {{ number_format($points) }} pts
                </div>
            @endif
        </div>
        <div class="p-3">
            <p class="truncate text-sm font-semibold">{{ $shop->name }}</p>
            <p class="truncate text-[11px] text-ink-muted">{{ $shop->business->name }}@if($shop->city) · {{ $shop->city }}@endif</p>
            @if ($campaign)
                <p class="mt-1 truncate text-[11px] font-medium text-mint-deep">{{ $campaign->ruleSummary($shop->business->currency) }}</p>
            @endif
        </div>
    </div>
</a>
