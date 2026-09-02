<x-app-layout>
    <section>
        <div class="loop-wallet relative overflow-hidden p-5 sm:p-8">
            <div class="relative grid gap-5 sm:gap-6 lg:grid-cols-[auto_1fr_auto] lg:items-center">
                <div class="mx-auto lg:mx-0">
                    @if ($business->logoUrl())
                        <div class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-[1.5rem] bg-ink ring-4 ring-white/15 sm:h-36 sm:w-36">
                            <img src="{{ $business->logoUrl() }}" alt="{{ $business->name }}" class="h-full w-full object-cover">
                        </div>
                    @else
                        <div class="flex h-28 w-28 items-center justify-center rounded-[1.5rem] bg-gradient-to-br from-violet to-lime/70 font-display text-4xl font-semibold text-white ring-4 ring-white/15 sm:h-36 sm:w-36">
                            {{ mb_substr($business->name, 0, 1) }}
                        </div>
                    @endif
                </div>
                <div class="min-w-0 text-center lg:text-left">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-lime">{{ __('loop.nav_members') }}</p>
                    <h1 class="mt-1.5 font-display text-3xl font-semibold sm:text-4xl">{{ __('loop.customers') }}</h1>
                    <p class="mt-1.5 text-sm text-white/70">{{ __('loop.customers_blurb', ['count' => number_format($memberCount)]) }}</p>
                </div>
                @if (! empty($smsEnabled))
                    <div class="text-center lg:text-right">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white/50">{{ __('loop.member_messages') }}</p>
                        <a href="{{ route('members.messages.index') }}" class="loop-btn-lime mt-1.5 inline-flex">{{ __('loop.send_messages') }}</a>
                    </div>
                @endif
            </div>
        </div>
    </section>

    @if ($topSpenders->isNotEmpty())
        <section class="mt-6">
            <div class="grid grid-cols-3 gap-2.5">
                <div class="rounded-2xl border border-ink/10 bg-white px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.customers') }}</p>
                    <p class="mt-1 font-display text-2xl font-semibold">{{ number_format($memberCount) }}</p>
                </div>
                <div class="rounded-2xl border border-ink/10 bg-white px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.best_customers') }}</p>
                    <p class="mt-1 truncate font-display text-lg font-semibold">{{ $topSpenders->first()?->name }}</p>
                </div>
                <div class="rounded-2xl border border-ink/10 bg-white px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.spend') }}</p>
                    <p class="mt-1 font-display text-xl font-semibold">{{ number_format($topSpenders->first()?->total_spend ?? 0, 0) }}</p>
                    <p class="mt-0.5 text-[10px] text-ink-muted">{{ $business->currency }}</p>
                </div>
            </div>
        </section>
    @endif

    <section class="mt-6">
        <div class="flex flex-wrap items-center gap-2">
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

        <div class="mt-3 grid gap-2.5">
            @forelse ($customers as $customer)
                <a href="{{ route('customers.show', $customer) }}" class="flex items-start gap-3 rounded-2xl border border-ink/10 bg-white px-4 py-3 transition hover:border-violet/30">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">{{ $customer->name }}</p>
                        <p class="mt-0.5 text-sm text-ink-muted">
                            {{ $customer->full_phone }}
                            @if ($customer->gender)
                                · {{ $customer->gender === 'female' ? __('loop.gender_female') : __('loop.gender_male') }}
                            @endif
                            · {{ ($customer->visits_count ?? 0) }} {{ __('loop.visits') }}
                            · {{ $business->currency }} {{ number_format($customer->total_spend ?? 0, 0) }}
                        </p>
                    </div>
                    <span class="shrink-0 rounded-lg bg-violet-soft px-2.5 py-1 text-xs font-semibold text-violet-deep">
                        {{ number_format((int) $customer->points_balance) }} {{ __('loop.pts') }}
                    </span>
                </a>
            @empty
                <div class="loop-panel p-8 text-center">
                    <p class="font-display text-lg font-semibold">{{ __('loop.empty_customers_title') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.empty_customers_body') }}</p>
                    <a href="{{ route('till.index') }}" class="loop-btn-mint mt-5 inline-flex">{{ __('loop.open_sale') }}</a>
                </div>
            @endforelse
        </div>
    </section>

    <div class="mt-6">{{ $customers->links() }}</div>
</x-app-layout>
