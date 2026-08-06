<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-3xl font-semibold">{{ __('loop.customers') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.customers_blurb', ['count' => $memberCount]) }}</p>
        </div>
    </x-slot>

    @if ($topSpenders->isNotEmpty())
        <section class="mb-8 rounded-[2rem] border border-ink/8 bg-gradient-to-br from-ink to-ink-soft p-6 text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint">{{ __('loop.best_customers') }}</p>
            <h2 class="mt-2 font-display text-2xl font-semibold">{{ __('loop.best_customers_title') }}</h2>
            <p class="mt-1 text-sm text-white/65">{{ __('loop.best_customers_body') }}</p>
            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                @foreach ($topSpenders as $i => $customer)
                    <a href="{{ route('customers.show', $customer) }}" class="rounded-2xl bg-white/10 px-4 py-4 backdrop-blur-sm transition hover:bg-white/15">
                        <p class="text-xs text-white/50">#{{ $i + 1 }}</p>
                        <p class="mt-1 font-semibold">{{ $customer->name }}</p>
                        <p class="mt-2 text-sm text-mint">{{ $business->currency }} {{ number_format($customer->total_spend ?? 0, 0) }}</p>
                        <p class="text-xs text-white/55">{{ $customer->visits_count ?? 0 }} {{ __('loop.visits') }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (['spend' => __('loop.sort_by_spend'), 'visits' => __('loop.sort_by_visits'), 'points' => __('loop.sort_by_points')] as $key => $label)
            <a href="{{ route('customers.index', ['sort' => $key]) }}"
               class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $sort === $key ? 'bg-ink text-white' : 'bg-white text-ink-muted ring-1 ring-ink/10' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

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
            <div class="loop-panel p-8 text-center text-sm text-ink-muted">{{ __('loop.no_customers_yet') }}</div>
        @endforelse
    </div>

    <div class="mt-8">{{ $customers->links() }}</div>
</x-app-layout>
