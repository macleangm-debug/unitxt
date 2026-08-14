<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ __('loop.transactions') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.transactions_blurb') }}</p>
            </div>
            <a href="{{ route('dashboard') }}" class="loop-btn-ghost !py-2.5" @click="$store.loopNav.go(@js(route('dashboard')), $event, { kind: 'back' })">{{ __('loop.back') }}</a>
        </div>
    </x-slot>

    <div class="mb-5 grid gap-3 sm:grid-cols-3">
        <div class="rounded-[1.35rem] border border-ink/8 bg-white/90 px-4 py-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.today') }}</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ $summary['today_count'] }}</p>
            @if ($showAmounts ?? true)
                <p class="mt-0.5 text-sm text-ink-muted">{{ $business->currency }} {{ number_format($summary['today_spend'], 0) }}</p>
            @endif
        </div>
        <div class="rounded-[1.35rem] border border-ink/8 bg-white/90 px-4 py-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.this_week') }}</p>
            @if ($showAmounts ?? true)
                <p class="mt-1 font-display text-2xl font-semibold">{{ $business->currency }} {{ number_format($summary['week_spend'], 0) }}</p>
            @else
                <p class="mt-1 font-display text-2xl font-semibold">—</p>
            @endif
        </div>
        <div class="rounded-[1.35rem] border border-ink/8 bg-white/90 px-4 py-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.this_month') }}</p>
            @if ($showAmounts ?? true)
                <p class="mt-1 font-display text-2xl font-semibold">{{ $business->currency }} {{ number_format($summary['month_spend'], 0) }}</p>
            @else
                <p class="mt-1 font-display text-2xl font-semibold">—</p>
            @endif
        </div>
    </div>

    <div class="mb-5 flex flex-wrap items-center gap-2">
        <form method="GET" action="{{ route('transactions.index') }}" class="flex min-w-0 flex-1 gap-2">
            <input type="search" name="q" value="{{ $filters['q'] }}" class="loop-input !py-2.5" placeholder="{{ __('loop.search_name_or_phone') }}">
            <input type="hidden" name="period" value="{{ $filters['period'] }}">
            <input type="hidden" name="shop" value="{{ $filters['shop'] }}">
            <input type="hidden" name="channel" value="{{ $filters['channel'] }}">
            <button class="loop-btn-mint shrink-0 !py-2.5">{{ __('loop.search') }}</button>
        </form>

        <x-filter-sheet :title="__('loop.filters')">
            <form method="GET" action="{{ route('transactions.index') }}" class="space-y-4">
                <input type="hidden" name="q" value="{{ $filters['q'] }}">
                <div>
                    <p class="loop-label">{{ __('loop.period') }}</p>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @foreach (['all' => __('loop.all'), 'today' => __('loop.today'), 'week' => __('loop.this_week'), 'month' => __('loop.this_month')] as $key => $label)
                            <label class="flex cursor-pointer items-center gap-2 rounded-2xl border border-ink/10 px-3 py-3 text-sm has-[:checked]:border-violet has-[:checked]:bg-violet-soft/40">
                                <input type="radio" name="period" value="{{ $key }}" class="text-violet" @checked(($filters['period'] ?? 'all') === $key)>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.shops') }}</label>
                    <select name="shop" class="loop-input mt-1">
                        <option value="">{{ __('loop.all_shops') }}</option>
                        @foreach ($shops as $shop)
                            <option value="{{ $shop->id }}" @selected((string) $filters['shop'] === (string) $shop->id)>{{ $shop->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.channel') }}</label>
                    <select name="channel" class="loop-input mt-1">
                        <option value="">{{ __('loop.all_channels') }}</option>
                        <option value="in_store" @selected($filters['channel'] === 'in_store')>{{ __('loop.in_store') }}</option>
                        <option value="phone_order" @selected($filters['channel'] === 'phone_order')>{{ __('loop.phone_order') }}</option>
                        <option value="online" @selected($filters['channel'] === 'online')>{{ __('loop.online') }}</option>
                    </select>
                </div>
                <button class="loop-btn-mint w-full">{{ __('loop.apply') }}</button>
            </form>
        </x-filter-sheet>
    </div>

    <div class="mb-4 flex flex-wrap gap-2 overflow-x-auto pb-1">
        @foreach (['all' => __('loop.all'), 'today' => __('loop.today'), 'week' => __('loop.this_week'), 'month' => __('loop.this_month')] as $key => $label)
            <a href="{{ route('transactions.index', array_merge(request()->except('page'), ['period' => $key])) }}"
               class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-semibold {{ ($filters['period'] ?? 'all') === $key ? 'bg-ink text-white' : 'bg-chalk text-ink-muted' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse ($visits as $visit)
            <div class="flex items-center justify-between gap-4 rounded-[1.5rem] border border-ink/8 bg-white/90 px-5 py-4">
                <div class="min-w-0">
                    <p class="font-semibold">{{ $visit->customer?->name ?? __('loop.customer') }}</p>
                    <p class="mt-1 text-xs text-ink-muted">
                        {{ $visit->shop?->name }} · {{ $visit->created_at->format('d M Y · H:i') }}
                        @if ($visit->channel === 'phone_order')
                            · {{ __('loop.phone_order') }}
                        @elseif ($visit->channel === 'online')
                            · {{ __('loop.online') }}
                        @elseif ($visit->channel)
                            · {{ __('loop.in_store') }}
                        @endif
                    </p>
                    <p class="mt-1 text-xs font-medium text-violet">
                        +{{ $visit->points_earned }} {{ __('loop.pts') }}
                        @if ($visit->points_redeemed)
                            · −{{ $visit->points_redeemed }} {{ __('loop.pts') }}
                        @endif
                    </p>
                </div>
                <p class="shrink-0 font-display text-2xl font-semibold tracking-tight">
                    @if ($showAmounts ?? true)
                        {{ $business->currency }} {{ number_format($visit->amount_spent, 0) }}
                    @else
                        <span class="text-base text-ink-muted">{{ __('loop.amount_hidden') }}</span>
                    @endif
                </p>
            </div>
        @empty
            <x-empty-state
                :title="__('loop.no_sales')"
                :blurb="__('loop.no_sales_blurb')"
                :cta="__('loop.start_selling')"
                :url="route('till.index')"
            />
        @endforelse
    </div>

    @if ($visits->hasPages())
        <div class="mt-8">{{ $visits->links() }}</div>
    @endif
</x-app-layout>
