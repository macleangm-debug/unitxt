<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.dash_tab_till') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.till_businesses_title') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.till_businesses_blurb') }}</p>
            </div>
            <a href="{{ route('admin.dashboard', ['tab' => 'till']) }}" class="admin-btn-ghost !py-2">{{ __('loop.back') }}</a>
        </div>
    </x-slot>

    <div class="mb-6 grid gap-3 sm:grid-cols-2">
        <div class="admin-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.till_gmv_all') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">TZS {{ number_format($totalGmv) }}</p>
        </div>
        <div class="admin-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.till_sales') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ number_format($totalSales) }}</p>
        </div>
    </div>

    <x-admin.empty-state :empty="$rows->isEmpty()" :title="__('loop.till_businesses_title')">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>{{ __('loop.business') }}</th>
                        <th>{{ __('loop.sector') }}</th>
                        <th>{{ __('loop.customers') }}</th>
                        <th>{{ __('loop.till_sales') }}</th>
                        <th>{{ __('loop.revenue') }}</th>
                        <th>{{ __('loop.plan') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td class="font-semibold">{{ $row->name }}</td>
                            <td>{{ $row->sector_label }}</td>
                            <td>{{ $row->unique_customers }}</td>
                            <td>{{ number_format((int) $row->sales_count) }}</td>
                            <td>TZS {{ number_format($row->revenue) }}</td>
                            <td>{{ $row->plan_key }}</td>
                            <td class="text-right"><a href="{{ route('admin.businesses.show', $row->id) }}" class="text-sm font-semibold text-violet">{{ __('loop.view') }} →</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-admin.empty-state>
</x-admin-layout>
