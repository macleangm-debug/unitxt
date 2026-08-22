@props([
    'membership',
    'carousel' => false,
])

@php
    $business = $membership->business;
    $shop = $business->shops?->first();
    $ready = method_exists($membership, 'availableRewards')
        ? $membership->availableRewards()->count()
        : 0;
    $next = method_exists($membership, 'nextReward') ? $membership->nextReward() : null;
    $needed = $next ? ($membership->progressTo($next)['needed'] ?? 0) : 0;
    $logoUrl = $business->logoUrl();
    $href = route('memberships.show', $business);
    $width = $carousel ? 'w-[17.5rem] shrink-0' : 'w-full';
    $morphId = 'business-'.$business->id;
    $pausedHere = app(\App\Services\LoopAccess::class)->isPaused($business);
@endphp

<article
    {{ $attributes->merge(['class' => "loop-wallet-card relative overflow-hidden rounded-[1.6rem] {$width}"]) }}
    x-data
>
    <div class="pointer-events-none absolute inset-0 opacity-90" aria-hidden="true">
        <div class="loop-orb loop-orb--a !opacity-60"></div>
        <div class="loop-orb loop-orb--b !opacity-50"></div>
    </div>

    <div class="relative flex h-full flex-col p-4 sm:p-5">
        <a
            href="{{ $href }}"
            class="group flex min-w-0 items-center gap-3"
            @click="$store.loopNav.go($el.href, $event, { morph: $refs.logo })"
        >
            <span
                x-ref="logo"
                data-loop-morph="{{ $morphId }}"
                class="loop-morph-logo flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-[1.05rem] bg-white/10 ring-1 ring-white/20"
            >
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="" class="h-full w-full object-cover" draggable="false">
                @else
                    <span class="font-display text-lg font-semibold text-lime">{{ mb_substr($business->name, 0, 1) }}</span>
                @endif
            </span>
            <span class="min-w-0 flex-1">
                <span class="block truncate font-display text-base font-semibold tracking-tight text-white transition group-hover:text-lime">
                    {{ $business->name }}
                </span>
                @if ($shop?->city || $business->city)
                    <span class="mt-0.5 block truncate text-[11px] text-white/50">
                        {{ $shop?->city ?: $business->city }}
                    </span>
                @endif
            </span>
        </a>

        <div class="mt-5 flex flex-1 items-end justify-between gap-3">
            <a
                href="{{ $href }}"
                class="min-w-0 flex-1 self-end"
                @click="$store.loopNav.go($el.href, $event, { morph: $refs.logo })"
            >
                <p class="font-display text-[2.65rem] font-semibold leading-none tracking-tight text-lime">
                    <x-count-up :value="$membership->points_balance" class="font-display text-[2.65rem] font-semibold text-lime" />
                </p>
                <p class="mt-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/50">{{ __('loop.pts') }}</p>
                @if ($ready > 0 && empty($pausedHere))
                    <p class="mt-2 text-xs font-semibold text-lime/90">{{ __('loop.offers_ready_count', ['count' => $ready]) }}</p>
                @elseif ($next && $needed > 0 && empty($pausedHere))
                    <p class="mt-2 text-xs font-semibold text-lime/90">{{ __('loop.pts_to_unlock_named', ['points' => $needed, 'offer' => $next->name]) }}</p>
                    @php $pct = max(0, min(100, (int) round(($membership->points_balance / max(1, $next->points_cost)) * 100))); @endphp
                    <div class="loop-hbar mt-2 h-1.5 bg-white/15">
                        <span class="loop-fill block h-full rounded-full bg-lime" style="width: {{ $pct }}%"></span>
                    </div>
                @endif
                @if ($pausedHere)
                    <p class="mt-2 text-xs font-semibold text-white/80">{{ __('loop.member_paused_title') }}</p>
                    <p class="mt-1 text-xs text-white/55">{{ __('loop.member_paused_points', ['points' => number_format($membership->points_balance)]) }}</p>
                @endif
            </a>

            <x-wallet-qr :size="96" class="self-end" />
        </div>
    </div>
</article>
