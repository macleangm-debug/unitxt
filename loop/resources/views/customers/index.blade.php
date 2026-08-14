<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-3xl font-semibold">{{ __('loop.customers') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.customers_blurb', ['count' => $memberCount]) }}</p>
        </div>
    </x-slot>

    <div class="mb-5 grid gap-3 sm:grid-cols-3">
        <div class="rounded-[1.35rem] border border-ink/8 bg-white/90 px-4 py-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.members') }}</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ number_format($summary['members']) }}</p>
        </div>
        <div class="rounded-[1.35rem] border border-ink/8 bg-white/90 px-4 py-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.new_this_month') }}</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ number_format($summary['new_month']) }}</p>
        </div>
        <div class="rounded-[1.35rem] border border-ink/8 bg-white/90 px-4 py-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.ready_to_redeem') }}</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ number_format($summary['ready']) }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('customers.index') }}" class="mb-5 space-y-3 rounded-[1.5rem] border border-ink/8 bg-white/90 p-4">
        <div class="flex flex-wrap gap-2">
            @foreach (['all' => __('loop.all'), 'new' => __('loop.tab_new_members'), 'ready' => __('loop.tab_ready_redeem')] as $key => $label)
                <a href="{{ route('customers.index', array_merge(request()->except('page'), ['tab' => $key])) }}"
                   class="rounded-xl px-3 py-1.5 text-xs font-semibold {{ ($tab ?? 'all') === $key ? 'bg-ink text-white' : 'bg-chalk text-ink-muted' }}">{{ $label }}</a>
            @endforeach
        </div>
        <div class="grid gap-3 sm:grid-cols-[1fr_auto]">
            <input type="search" name="q" value="{{ $q }}" class="loop-input" placeholder="{{ __('loop.search_name_or_phone') }}">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <button class="loop-btn-mint !py-2.5">{{ __('loop.search') }}</button>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach (['spend' => __('loop.sort_by_spend'), 'visits' => __('loop.sort_by_visits'), 'points' => __('loop.sort_by_points'), 'recent' => __('loop.sort_by_recent')] as $key => $label)
                <a href="{{ route('customers.index', array_merge(request()->except('page'), ['sort' => $key])) }}"
                   class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $sort === $key ? 'bg-violet text-white' : 'bg-white text-ink-muted ring-1 ring-ink/10' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </form>

    @if ($topSpenders->isNotEmpty() && ($tab ?? 'all') === 'all' && $q === '')
        <section class="mb-6 rounded-[1.75rem] border border-ink/8 bg-gradient-to-br from-ink to-[#1a1430] p-5 text-white sm:p-6">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-lime">{{ __('loop.best_customers') }}</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                @foreach ($topSpenders as $i => $customer)
                    <a href="{{ route('customers.show', $customer) }}" class="rounded-2xl bg-white/10 px-4 py-4 transition hover:bg-white/15">
                        <p class="text-xs text-white/50">#{{ $i + 1 }}</p>
                        <p class="mt-1 font-semibold">{{ $customer->name }}</p>
                        <p class="mt-2 text-sm text-lime">{{ $business->currency }} {{ number_format($customer->total_spend ?? 0, 0) }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <div class="space-y-3">
        @forelse ($customers as $customer)
            <a href="{{ route('customers.show', $customer) }}" class="flex items-center justify-between gap-4 rounded-[1.5rem] border border-ink/8 bg-white/90 px-5 py-4 transition hover:-translate-y-0.5 hover:bg-white">
                <div class="min-w-0">
                    <p class="font-semibold">{{ $customer->name }}</p>
                    <p class="mt-1 text-xs text-ink-muted">
                        {{ $customer->full_phone }}
                        · {{ ($customer->visits_count ?? 0) }} {{ __('loop.visits') }}
                        · {{ $business->currency }} {{ number_format($customer->total_spend ?? 0, 0) }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="font-display text-2xl font-semibold">{{ number_format((int) $customer->points_balance) }}</p>
                    <p class="text-xs text-ink-muted">{{ __('loop.pts') }}</p>
                </div>
            </a>
        @empty
            <x-empty-state
                :title="__('loop.no_customers_yet')"
                :blurb="__('loop.no_customers_blurb')"
                :cta="__('loop.start_selling')"
                :url="route('till.index')"
            />
        @endforelse
    </div>

    @if ($customers->hasPages())
        <div class="mt-8">{{ $customers->links() }}</div>
    @endif
</x-app-layout>
