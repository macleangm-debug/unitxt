<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.admin_reports') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.admin_reports_blurb') }}</p>
    </x-slot>

    @include('admin.partials.nav')

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="loop-panel p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.revenue_all') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">TZS {{ number_format($overview['revenue_all']) }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ $overview['sales_all'] }} {{ __('loop.sales') }}</p>
        </div>
        <div class="loop-panel p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.revenue_month') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">TZS {{ number_format($overview['revenue_month']) }}</p>
        </div>
        <div class="loop-panel p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.unique_customers') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $overview['unique_customers'] }}</p>
        </div>
        <div class="loop-panel p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.paid') }} / {{ __('loop.trialing') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $overview['paid_active'] }} / {{ $overview['trialing'] }}</p>
        </div>
    </div>

    <div class="mt-10 grid gap-8 lg:grid-cols-2">
        <section>
            <h2 class="font-display text-xl font-semibold">{{ __('loop.customers_by_sector') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.customers_by_sector_blurb') }}</p>
            <div class="mt-4 space-y-2">
                @forelse ($customersBySector as $row)
                    <div class="loop-panel flex justify-between p-4">
                        <p class="font-semibold">{{ $row->sector_label }}</p>
                        <p class="font-display text-xl font-semibold">{{ $row->unique_customers }}</p>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">{{ __('loop.no_data_yet') }}</p>
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="font-display text-xl font-semibold">{{ __('loop.sales_by_sector') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.sales_by_sector_blurb') }}</p>
            <div class="mt-4 space-y-2">
                @forelse ($salesBySector as $row)
                    <div class="loop-panel flex justify-between gap-3 p-4">
                        <div>
                            <p class="font-semibold">{{ $row->sector_label }}</p>
                            <p class="text-xs text-ink-muted">{{ $row->sales_count }} {{ __('loop.sales') }} · {{ $row->unique_customers }} {{ __('loop.customers') }}</p>
                        </div>
                        <p class="font-semibold">TZS {{ number_format($row->revenue) }}</p>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">{{ __('loop.no_data_yet') }}</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="mt-10">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.customers_by_business') }}</h2>
        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.customers_by_business_blurb') }}</p>
        <div class="mt-4 overflow-x-auto rounded-[1.5rem] border border-ink/10 bg-white">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-ink/5 text-xs uppercase tracking-wide text-ink-muted">
                    <tr>
                        <th class="px-4 py-3">{{ __('loop.business') }}</th>
                        <th class="px-4 py-3">{{ __('loop.sector') }}</th>
                        <th class="px-4 py-3">{{ __('loop.unique_customers') }}</th>
                        <th class="px-4 py-3">{{ __('loop.sales') }}</th>
                        <th class="px-4 py-3">{{ __('loop.revenue') }}</th>
                        <th class="px-4 py-3">{{ __('loop.plan') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customersByBusiness as $row)
                        <tr class="border-b border-ink/5 last:border-0">
                            <td class="px-4 py-3 font-medium">{{ $row->name }}</td>
                            <td class="px-4 py-3">{{ $row->sector_label }}</td>
                            <td class="px-4 py-3">{{ $row->unique_customers }}</td>
                            <td class="px-4 py-3">{{ $row->sales_count }}</td>
                            <td class="px-4 py-3">TZS {{ number_format($row->revenue) }}</td>
                            <td class="px-4 py-3">{{ $row->plan_key }} · {{ $row->billing_status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
