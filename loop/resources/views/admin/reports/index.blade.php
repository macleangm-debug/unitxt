@php
    $tab = request('tab', 'overview');
    $allowed = ['overview', 'sectors', 'businesses', 'trend', 'export'];
    if (! in_array($tab, $allowed, true)) {
        $tab = 'overview';
    }
    $tabs = [
        'overview' => __('loop.reports_tab_overview'),
        'sectors' => __('loop.reports_tab_sectors'),
        'businesses' => __('loop.reports_tab_businesses'),
        'trend' => __('loop.reports_tab_trend'),
        'export' => __('loop.reports_tab_export'),
    ];
    $maxDaily = max(1, (int) collect($dailySales)->max('revenue'));
    $maxSector = max(1, (float) collect($salesBySector)->max('revenue'));
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-3xl font-semibold">{{ __('loop.admin_reports') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.admin_reports_blurb') }}</p>
        </div>
    </x-slot>

    @include('admin.partials.nav')

    <div class="loop-admin-tabs" role="tablist">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.reports.index', ['tab' => $key]) }}"
               class="loop-admin-tab {{ $tab === $key ? 'is-active' : '' }}"
               role="tab"
               aria-selected="{{ $tab === $key ? 'true' : 'false' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($tab === 'overview')
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

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="loop-glass p-6">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <div>
                        <h2 class="font-display text-xl font-semibold">{{ __('loop.sales_by_sector') }}</h2>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.top_3_sectors_blurb') }}</p>
                    </div>
                    <a href="{{ route('admin.reports.index', ['tab' => 'sectors']) }}" class="text-sm font-semibold text-violet">{{ __('loop.view_more') }} →</a>
                </div>
                <div class="space-y-4">
                    @forelse (collect($salesBySector)->take(3) as $row)
                        @php $pct = max(4, (int) round(((float) $row->revenue / $maxSector) * 100)); @endphp
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                <p class="font-semibold">{{ $row->sector_label }}</p>
                                <p class="font-semibold">TZS {{ number_format($row->revenue) }}</p>
                            </div>
                            <div class="loop-hbar"><span style="width: {{ $pct }}%"></span></div>
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted">{{ __('loop.no_data_yet') }}</p>
                    @endforelse
                </div>
            </section>
            <section class="loop-glass p-6">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <div>
                        <h2 class="font-display text-xl font-semibold">{{ __('loop.reports_tab_trend') }}</h2>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.last_14_days_sales_blurb') }}</p>
                    </div>
                    <a href="{{ route('admin.reports.index', ['tab' => 'trend']) }}" class="text-sm font-semibold text-violet">{{ __('loop.view_more') }} →</a>
                </div>
                <div class="loop-bar-chart h-32">
                    @foreach (collect($dailySales)->take(14) as $day)
                        @php $h = max(4, (int) round(((float) $day->revenue / $maxDaily) * 100)); @endphp
                        <div class="loop-bar-chart__col" title="{{ $day->day }} · TZS {{ number_format($day->revenue) }}">
                            <div class="loop-bar-chart__bar" style="height: {{ $h }}%"></div>
                            <span class="loop-bar-chart__label">{{ \Illuminate\Support\Carbon::parse($day->day)->format('d') }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    @endif

    @if ($tab === 'sectors')
        <div class="grid gap-8 lg:grid-cols-2">
            <section class="loop-glass p-6">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.customers_by_sector') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.customers_by_sector_blurb') }}</p>
                <div class="mt-4 space-y-2">
                    @forelse ($customersBySector as $row)
                        <div class="flex justify-between rounded-xl bg-white/70 px-4 py-3">
                            <p class="font-semibold">{{ $row->sector_label }}</p>
                            <p class="font-display text-xl font-semibold">{{ $row->unique_customers }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted">{{ __('loop.no_data_yet') }}</p>
                    @endforelse
                </div>
            </section>
            <section class="loop-glass p-6">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.sales_by_sector') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.sales_by_sector_blurb') }}</p>
                <div class="mt-4 space-y-4">
                    @forelse ($salesBySector as $row)
                        @php $pct = max(4, (int) round(((float) $row->revenue / $maxSector) * 100)); @endphp
                        <div>
                            <div class="mb-1.5 flex flex-wrap items-center justify-between gap-2 text-sm">
                                <div>
                                    <p class="font-semibold">{{ $row->sector_label }}</p>
                                    <p class="text-xs text-ink-muted">{{ $row->sales_count }} {{ __('loop.sales') }} · {{ $row->unique_customers }} {{ __('loop.customers') }}</p>
                                </div>
                                <p class="font-semibold">TZS {{ number_format($row->revenue) }}</p>
                            </div>
                            <div class="loop-hbar"><span style="width: {{ $pct }}%"></span></div>
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted">{{ __('loop.no_data_yet') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    @endif

    @if ($tab === 'businesses')
        <section>
            <h2 class="font-display text-xl font-semibold">{{ __('loop.customers_by_business') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.customers_by_business_blurb') }}</p>
            <div class="mt-4 loop-table-wrap">
                <table class="loop-table">
                    <thead>
                        <tr>
                            <th>{{ __('loop.business') }}</th>
                            <th>{{ __('loop.sector') }}</th>
                            <th>{{ __('loop.unique_customers') }}</th>
                            <th>{{ __('loop.sales') }}</th>
                            <th>{{ __('loop.revenue') }}</th>
                            <th>{{ __('loop.plan') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customersByBusiness as $row)
                            <tr>
                                <td class="font-medium">{{ $row->name }}</td>
                                <td>{{ $row->sector_label }}</td>
                                <td>{{ $row->unique_customers }}</td>
                                <td>{{ $row->sales_count }}</td>
                                <td>TZS {{ number_format($row->revenue) }}</td>
                                <td>{{ $row->plan_key }} · {{ $row->billing_status }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.businesses.show', $row->id) }}" class="text-sm font-semibold text-violet">{{ __('loop.view') }} →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($tab === 'trend')
        <section class="loop-glass p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.last_30_days_sales') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.last_30_days_sales_blurb') }}</p>
            <div class="mt-6 loop-bar-chart">
                @foreach ($dailySales as $day)
                    @php $h = max(4, (int) round(((float) $day->revenue / $maxDaily) * 100)); @endphp
                    <div class="loop-bar-chart__col" title="{{ $day->day }} · TZS {{ number_format($day->revenue) }} · {{ $day->sales_count }} {{ __('loop.sales') }}">
                        <div class="loop-bar-chart__bar" style="height: {{ $h }}%"></div>
                        <span class="loop-bar-chart__label">{{ \Illuminate\Support\Carbon::parse($day->day)->format('d') }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-6 loop-table-wrap">
                <table class="loop-table">
                    <thead>
                        <tr>
                            <th>{{ __('loop.day') }}</th>
                            <th>{{ __('loop.sales') }}</th>
                            <th>{{ __('loop.revenue') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dailySales as $day)
                            <tr>
                                <td class="font-medium">{{ $day->day }}</td>
                                <td>{{ $day->sales_count }}</td>
                                <td>TZS {{ number_format($day->revenue) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($tab === 'export')
        <section class="loop-panel p-6">
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.export_reports') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.export_reports_blurb') }}</p>
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
    @endif
</x-app-layout>
