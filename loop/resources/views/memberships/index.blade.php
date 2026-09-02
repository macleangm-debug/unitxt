<x-app-layout>
    <section class="loop-wallet mb-8 px-5 py-7 sm:px-8 sm:py-9">
        <div class="loop-orb loop-orb--a"></div>
        <div class="loop-orb loop-orb--b"></div>
        <div class="loop-orb loop-orb--c"></div>
        <div class="relative">
            <div class="flex items-end justify-between gap-4">
                <div class="flex min-w-0 flex-1 flex-col justify-between self-stretch">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-lime/80">Loop</p>
                        <h1 class="mt-2 font-display text-[clamp(1.85rem,7vw,2.65rem)] font-semibold leading-tight tracking-tight text-white">
                            {{ __('loop.your_rewards') }}
                        </h1>
                        @if ($readyCount > 0)
                            <p class="mt-2 max-w-xs text-sm leading-relaxed text-white/70">{{ trans_choice('loop.rewards_ready_hero', $readyCount, ['count' => $readyCount]) }}</p>
                        @else
                            <p class="mt-2 max-w-xs text-sm leading-relaxed text-white/55">{{ __('loop.rewards_empty_hero') }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex shrink-0 flex-col items-center">
                    <x-wallet-qr :size="120" class="shrink-0" />
                    <p class="mt-2 text-center text-[10px] font-semibold uppercase tracking-[0.14em] text-white/50">{{ __('loop.wallet_qr_title') }}</p>
                </div>
            </div>
        </div>
    </section>

    <form method="GET" action="{{ route('memberships.index') }}" class="mb-6 space-y-3">
        <div class="loop-discover-search">
            <label class="sr-only" for="rewards-q">{{ __('loop.search_rewards') }}</label>
            <input id="rewards-q" type="search" name="q" value="{{ $search }}" placeholder="{{ __('loop.search_rewards') }}" class="loop-input" autocomplete="off">
            <button class="loop-btn">{{ __('loop.search') }}</button>
        </div>
        <div class="loop-discover-chips">
            <a href="{{ route('memberships.index', array_filter(['q' => $search ?: null])) }}" class="loop-chip {{ $filter === '' ? 'is-on' : '' }}">{{ __('loop.all') }}</a>
            <a href="{{ route('memberships.index', array_filter(['q' => $search ?: null, 'filter' => 'ready'])) }}" class="loop-chip {{ $filter === 'ready' ? 'is-on' : '' }}">{{ __('loop.filter_reward_ready') }}</a>
            <a href="{{ route('memberships.index', array_filter(['q' => $search ?: null, 'filter' => 'almost'])) }}" class="loop-chip {{ $filter === 'almost' ? 'is-on' : '' }}">{{ __('loop.filter_almost_there') }}</a>
            <a href="{{ route('memberships.index', array_filter(['q' => $search ?: null, 'filter' => 'offers'])) }}" class="loop-chip {{ $filter === 'offers' ? 'is-on' : '' }}">{{ __('loop.filter_offers') }}</a>
            <a href="{{ route('memberships.index', array_filter(['q' => $search ?: null, 'filter' => 'used'])) }}" class="loop-chip {{ $filter === 'used' ? 'is-on' : '' }}">{{ __('loop.filter_used') }}</a>
        </div>
    </form>

    @if ($filter === '' && $ready->isEmpty() && $almost->isEmpty() && $offers->isEmpty())
        <div class="rounded-[1.5rem] border border-dashed border-ink/15 bg-white/60 px-6 py-12 text-center">
            <p class="font-display text-xl font-semibold">{{ __('loop.nothing_ready_yet') }}</p>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.nothing_ready_yet_body') }}</p>
            <a href="{{ route('discover') }}" class="loop-btn mt-5 inline-flex">{{ __('loop.discover') }}</a>
        </div>
    @endif

    @if ($ready->isNotEmpty())
        <section class="mb-8">
            <x-section-heading :title="__('loop.ready_to_use')" class="mb-4" />
            <div class="space-y-3">
                @foreach ($ready as $item)
                    <a href="{{ route('memberships.show', $item['business']) }}" class="loop-panel flex items-center gap-3 p-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-[0.95rem] bg-ink">
                            @if ($item['business']->logoUrl())
                                <img src="{{ $item['business']->logoUrl() }}" alt="" class="h-full w-full object-cover">
                            @else
                                <span class="font-display text-lg font-semibold text-lime">{{ mb_substr($item['business']->name, 0, 1) }}</span>
                            @endif
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold">{{ $item['business']->name }}</span>
                            <span class="mt-0.5 block truncate text-sm font-semibold text-violet">{{ $item['reward']->name }}</span>
                            <span class="mt-0.5 block text-[12px] text-ink-muted">{{ __('loop.earned_with_points', ['points' => number_format((int) $item['reward']->points_cost)]) }}</span>
                        </span>
                        <span class="loop-btn-lime !px-3 !py-2 text-sm">{{ __('loop.use_reward') }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($almost->isNotEmpty())
        <section class="mb-8">
            <x-section-heading :title="__('loop.almost_there')" class="mb-4" />
            <div class="space-y-3">
                @foreach ($almost as $item)
                    <a href="{{ route('memberships.show', $item['business']) }}" class="loop-panel flex items-center gap-3 p-4">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold">{{ $item['business']->name }}</span>
                            <span class="mt-0.5 block text-sm text-ink-muted">{{ number_format((int) $item['membership']->points_balance) }} / {{ number_format((int) $item['reward']->points_cost) }} {{ __('loop.pts') }}</span>
                            <span class="mt-1 block text-sm text-violet">{{ __('loop.pts_to_unlock_named', ['points' => $item['progress']['needed'], 'offer' => $item['reward']->name]) }}</span>
                        </span>
                        <span class="text-[12px] font-semibold text-ink-muted">{{ __('loop.view') }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($offers->isNotEmpty())
        <section class="mb-8">
            <x-section-heading :title="__('loop.offers_for_you')" class="mb-4" />
            <div class="space-y-3">
                @foreach ($offers as $item)
                    <a href="{{ route('memberships.show', $item['business']) }}" class="loop-panel flex items-center justify-between gap-3 p-4">
                        <span class="min-w-0">
                            <span class="block truncate font-semibold">{{ $item['business']->name }}</span>
                            <span class="mt-0.5 block truncate text-sm text-violet">{{ $item['campaign']->name }}</span>
                            <span class="mt-0.5 block text-[12px] text-ink-muted">{{ $item['campaign']->ruleSummary($item['business']->currency) }}</span>
                        </span>
                        <span class="text-[12px] font-semibold text-ink-muted">{{ __('loop.view') }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($used->isNotEmpty())
        <section class="mb-8">
            <x-section-heading :title="__('loop.used_rewards')" class="mb-4" />
            <div class="space-y-2">
                @foreach ($used as $tx)
                    <div class="flex items-center justify-between rounded-[1.25rem] border border-ink/8 bg-white/80 px-4 py-3">
                        <p class="text-sm font-semibold">{{ $tx->membership?->business?->name }}</p>
                        <p class="font-display text-sm font-semibold text-coral">{{ $tx->points }} {{ __('loop.pts') }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
