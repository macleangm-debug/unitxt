<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-3xl font-semibold">{{ __('loop.admin_reports') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.admin_reports_blurb') }}</p>
        </div>
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

    <section class="mt-10 loop-panel p-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.export_reports') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.export_reports_blurb') }}</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.reports.export') }}" class="mt-6 space-y-6">
            <div>
                <p class="loop-label">{{ __('loop.export_report') }}</p>
                <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($exportTypes as $key => $label)
                        <label class="flex items-center gap-3 rounded-xl border border-ink/10 bg-chalk/60 px-4 py-3 text-sm has-[:checked]:border-mint has-[:checked]:bg-mint-soft/40">
                            <input type="checkbox" name="reports[]" value="{{ $key }}" class="rounded border-ink/20 text-mint-deep focus:ring-mint" @checked($key === 'customers_by_sector')>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="loop-label">{{ __('loop.export_format') }}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach (['csv' => 'CSV', 'tsv' => 'TSV', 'json' => 'JSON'] as $value => $label)
                        <label class="inline-flex items-center gap-2 rounded-xl border border-ink/10 bg-white px-4 py-2.5 text-sm font-medium has-[:checked]:border-mint has-[:checked]:bg-mint-soft/50">
                            <input type="checkbox" name="formats[]" value="{{ $value }}" class="rounded border-ink/20 text-mint-deep focus:ring-mint" @checked($value === 'csv')>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-ink-muted">{{ __('loop.export_zip_hint') }}</p>
            </div>

            <button class="loop-btn-mint">{{ __('loop.download') }}</button>
        </form>
    </section>

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
