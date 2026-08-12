<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.admin') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.admin_customers_title') }}</h1>
                <p class="mt-1 max-w-2xl text-ink-muted">{{ __('loop.admin_customers_blurb') }}</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="loop-btn-ghost !py-2">{{ __('loop.back') }}</a>
        </div>
    </x-slot>

    @include('admin.partials.nav')

    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <div class="loop-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.admin_customers_total') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ number_format($totals['customers']) }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.admin_customers_active_month') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ number_format($totals['active_month']) }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.admin_customers_avg_shops') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $totals['avg_shops'] }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.admin_customers_month_gmv') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">TZS {{ number_format($totals['month_gmv']) }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.admin_customers_memberships') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ number_format($totals['memberships']) }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.admin_customers_scouts') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ number_format($totals['scouts']) }}</p>
        </div>
    </div>

    @if ($bySector->isNotEmpty())
        <section class="mb-8">
            <h2 class="mb-3 font-display text-xl font-semibold">{{ __('loop.customers_by_sector') }}</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($bySector as $row)
                    <div class="loop-glass p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ $row->sector_label ?? $row->sector }}</p>
                        <p class="mt-2 font-display text-2xl font-semibold">{{ number_format($row->unique_customers) }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <x-admin.empty-state :empty="$customers->isEmpty()" :title="__('loop.admin_customers_title')">
        <div class="loop-table-wrap">
            <table class="loop-table">
                <thead>
                    <tr>
                        <th>{{ __('loop.customer') }}</th>
                        <th>{{ __('loop.phone') }}</th>
                        <th>{{ __('loop.city') }}</th>
                        <th>{{ __('loop.shops') }}</th>
                        <th>{{ __('loop.visits') }}</th>
                        <th>{{ __('loop.month') }}</th>
                        <th>{{ __('loop.lifetime_spend') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr>
                            <td class="font-semibold">{{ $customer->name }}</td>
                            <td class="font-mono text-sm">{{ $customer->full_phone ?? $customer->phone }}</td>
                            <td>{{ $customer->city ?: '—' }}</td>
                            <td>{{ $customer->memberships_count }}</td>
                            <td>{{ $customer->visits_count }}</td>
                            <td>{{ $customer->month_visits_count }} · TZS {{ number_format((float) $customer->month_spend) }}</td>
                            <td>TZS {{ number_format((float) $customer->lifetime_spend) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $customers->links() }}</div>
    </x-admin.empty-state>
</x-app-layout>
