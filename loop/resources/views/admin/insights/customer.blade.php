<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.admin_customers_nav') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ $customer->name }}</h1>
                <p class="mt-1 text-ink-muted">
                    {{ $customer->full_phone }}
                    @if ($customer->city)
                        · {{ $customer->city }}
                    @endif
                    @if ($customer->hasBirthday())
                        · {{ __('loop.birthday') }} {{ $customer->birth_day }}/{{ $customer->birth_month }}
                    @endif
                    @if ($customer->gender)
                        · {{ $customer->gender === 'female' ? __('loop.gender_female') : __('loop.gender_male') }}
                    @endif
                </p>
            </div>
            <a href="{{ route('admin.insights.customers') }}" class="admin-btn-ghost !py-2">{{ __('loop.back') }}</a>
        </div>
    </x-slot>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="admin-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.points') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ number_format($points) }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.lifetime_points') }}: {{ number_format($lifetime) }}</p>
        </div>
        <div class="admin-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.sales') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ number_format($visitCount) }}</p>
        </div>
        <div class="admin-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.lifetime_spend') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">TZS {{ number_format($totalSpend) }}</p>
        </div>
        <div class="admin-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.shops') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $memberships->count() }}</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="admin-card">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.member') }}</h2>
            <dl class="admin-dl mt-4">
                <dt>{{ __('loop.phone') }}</dt>
                <dd class="font-mono">{{ $customer->full_phone }}</dd>
                <dt>{{ __('loop.email') }}</dt>
                <dd>{{ $customer->email ?: '—' }}</dd>
                <dt>{{ __('loop.city') }}</dt>
                <dd>{{ $customer->city ?: '—' }}</dd>
                <dt>{{ __('loop.birthday') }}</dt>
                <dd>
                    @if ($customer->hasBirthday())
                        {{ $customer->birth_day }}/{{ $customer->birth_month }}
                    @else
                        —
                    @endif
                </dd>
                <dt>{{ __('loop.gender') }}</dt>
                <dd>
                    @if ($customer->gender === 'female')
                        {{ __('loop.gender_female') }}
                    @elseif ($customer->gender === 'male')
                        {{ __('loop.gender_male') }}
                    @else
                        —
                    @endif
                </dd>
                <dt>{{ __('loop.interests') }}</dt>
                <dd>{{ $interests->isNotEmpty() ? $interests->join(', ') : '—' }}</dd>
            </dl>
        </section>

        <section class="admin-card">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.shops') }}</h2>
            <div class="mt-4 space-y-2">
                @forelse ($memberships as $membership)
                    <a href="{{ route('admin.businesses.show', $membership->business) }}" class="flex items-center justify-between rounded-xl bg-white/70 px-3 py-2.5 text-sm hover:bg-white">
                        <span>
                            <span class="block font-semibold">{{ $membership->business?->name }}</span>
                            <span class="text-xs text-ink-muted">{{ $membership->shop?->name }} · {{ number_format($membership->points_balance) }} {{ __('loop.pts') }}</span>
                        </span>
                        <span class="text-violet">→</span>
                    </a>
                @empty
                    <p class="text-sm text-ink-muted">{{ __('loop.no_data_yet') }}</p>
                @endforelse
            </div>
        </section>
    </div>

    @if ($visits->isNotEmpty())
        <section class="mt-8">
            <h2 class="mb-3 font-display text-xl font-semibold">{{ __('loop.recent_sales') }}</h2>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('loop.when') }}</th>
                            <th>{{ __('loop.business') }}</th>
                            <th>{{ __('loop.shop') }}</th>
                            <th>{{ __('loop.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($visits as $visit)
                            <tr>
                                <td>{{ $visit->created_at?->diffForHumans() }}</td>
                                <td>{{ $visit->business?->name ?? '—' }}</td>
                                <td>{{ $visit->shop?->name ?? '—' }}</td>
                                <td>TZS {{ number_format($visit->amount_spent) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($raffleWins->isNotEmpty())
        <section class="mt-8">
            <h2 class="mb-3 font-display text-xl font-semibold">{{ __('loop.raffle_wins') }}</h2>
            <div class="space-y-2">
                @foreach ($raffleWins as $win)
                    <div class="admin-card">
                        <p class="font-semibold">{{ $win->raffle?->name }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ $win->raffle?->prize_name }} · {{ __('loop.raffle_winner_status_'.$win->status) }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-admin-layout>
