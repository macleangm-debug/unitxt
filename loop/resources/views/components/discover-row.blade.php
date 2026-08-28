@props([
    'business',
    'points' => null,
    'isMember' => false,
])

@php
    $sectorLabel = \App\Support\Sectors::label($business->sector, $business->sector_other);
    $city = $business->shops?->first()?->city ?: $business->city;
    $href = $isMember
        ? route('memberships.show', $business)
        : route('discover.show', $business);
    $rewards = $business->rewards ?? collect();
    $readyReward = $points !== null
        ? $rewards->first(fn ($reward) => (int) $reward->points_cost <= (int) $points)
        : null;
    $nextReward = $rewards->first(fn ($reward) => $points === null || (int) $reward->points_cost > (int) $points);
    $needed = ($nextReward && $points !== null)
        ? max(0, (int) $nextReward->points_cost - (int) $points)
        : null;
    $campaign = $business->campaigns?->first();
    $logoUrl = $business->logoUrl();
@endphp

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'loop-discover-row']) }}
    @if ($isMember)
        x-data
        @click="$store.loopNav.go($el.href, $event, { kind: 'push' })"
    @endif
>
    <span class="loop-discover-row__logo">
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="" class="h-full w-full object-cover" draggable="false">
        @else
            <span class="font-display text-lg font-semibold text-lime">{{ mb_substr($business->name, 0, 1) }}</span>
        @endif
    </span>
    <span class="min-w-0 flex-1">
        <span class="block truncate font-semibold text-ink">{{ $business->name }}</span>
        <span class="mt-0.5 block truncate text-[12px] text-ink-muted">{{ $sectorLabel }}@if($city) · {{ $city }}@endif</span>
        @if ($readyReward)
            <span class="mt-1 block truncate text-[12px] font-semibold text-violet">{{ __('loop.reward_ready_row', ['reward' => $readyReward->name]) }}</span>
        @elseif ($points !== null)
            <span class="mt-1 block truncate text-[12px] font-semibold text-violet">
                {{ number_format((int) $points) }} {{ __('loop.pts') }}
                @if ($needed !== null && $needed > 0)
                    · {{ __('loop.pts_to_unlock_named', ['points' => $needed, 'offer' => $nextReward->name]) }}
                @endif
            </span>
        @elseif ($campaign)
            <span class="mt-1 block truncate text-[12px] font-medium text-violet">{{ $campaign->ruleSummary($business->currency) }}</span>
        @else
            <span class="mt-1 block truncate text-[12px] font-medium text-violet">{{ __('loop.earn_rewards_here') }}</span>
        @endif
    </span>
    <span class="shrink-0 text-[12px] font-semibold text-ink-muted">{{ __('loop.view') }}</span>
</a>
