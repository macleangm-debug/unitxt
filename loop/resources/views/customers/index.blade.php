<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-3xl font-semibold">{{ __('loop.customers') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.customers_blurb', ['count' => $memberCount]) }}</p>
        </div>
    </x-slot>

    @if ($topSpenders->isNotEmpty())
        <div class="mb-6 grid grid-cols-3 gap-3">
            <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.customers') }}</p>
                <p class="mt-2 font-display text-2xl font-semibold sm:text-3xl">{{ $memberCount }}</p>
            </div>
            <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.best_customers') }}</p>
                <p class="mt-2 font-display text-lg font-semibold sm:text-xl">{{ $topSpenders->first()?->name }}</p>
            </div>
            <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.spend') }}</p>
                <p class="mt-2 font-display text-xl font-semibold sm:text-2xl">{{ number_format($topSpenders->first()?->total_spend ?? 0, 0) }}</p>
                <p class="mt-0.5 text-[10px] text-ink-muted">{{ $business->currency }}</p>
            </div>
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-center gap-2">
        @foreach (['spend' => __('loop.sort_by_spend'), 'visits' => __('loop.sort_by_visits'), 'points' => __('loop.sort_by_points')] as $key => $label)
            <a href="{{ route('customers.index', array_filter(['sort' => $key, 'gender' => $gender])) }}"
               class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $sort === $key ? 'bg-ink text-white' : 'bg-white text-ink-muted ring-1 ring-ink/10' }}">
                {{ $label }}
            </a>
        @endforeach
        <span class="mx-1 hidden h-4 w-px bg-ink/10 sm:inline-block"></span>
        @foreach (['' => __('loop.gender_all'), 'male' => __('loop.gender_male'), 'female' => __('loop.gender_female')] as $key => $label)
            <a href="{{ route('customers.index', array_filter(['sort' => $sort, 'gender' => $key])) }}"
               class="rounded-full px-3 py-1.5 text-xs font-semibold {{ ($gender ?? '') === $key ? 'bg-ink text-white' : 'bg-white text-ink-muted ring-1 ring-ink/10' }}">
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
                        @if ($customer->gender)
                            · {{ $customer->gender === 'female' ? __('loop.gender_female') : __('loop.gender_male') }}
                        @endif
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
