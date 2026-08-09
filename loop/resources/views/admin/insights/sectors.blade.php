<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.admin_overview') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.sectors_insight_title') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.sectors_insight_blurb') }}</p>
            </div>
            <a href="{{ route('admin.dashboard', ['tab' => 'sectors']) }}" class="loop-btn-ghost !py-2">{{ __('loop.back') }}</a>
        </div>
    </x-slot>

    @include('admin.partials.nav')

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="loop-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.configured_sectors') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $sectorCount }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.best_sector_gmv') }}</p>
            <p class="mt-2 font-display text-xl font-semibold">{{ $topGmv->sector_label ?? '—' }}</p>
            <p class="mt-1 text-xs text-ink-muted">TZS {{ number_format((float) ($topGmv->revenue ?? 0)) }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.best_sector_businesses') }}</p>
            <p class="mt-2 font-display text-xl font-semibold">{{ $topBiz->sector_label ?? '—' }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ (int) ($topBiz->businesses ?? 0) }} {{ __('loop.admin_businesses') }}</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section>
            <h2 class="mb-3 font-display text-xl font-semibold">{{ __('loop.best_sector_gmv') }}</h2>
            <x-admin.empty-state :empty="$salesBySector->isEmpty()" :title="__('loop.best_sector_gmv')">
                <div class="loop-table-wrap">
                    <table class="loop-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('loop.sector') }}</th>
                                <th>{{ __('loop.till_sales') }}</th>
                                <th>{{ __('loop.customers') }}</th>
                                <th>{{ __('loop.revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($salesBySector as $i => $row)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td class="font-semibold">{{ $row->sector_label }}</td>
                                    <td>{{ $row->sales_count }}</td>
                                    <td>{{ $row->unique_customers }}</td>
                                    <td>TZS {{ number_format($row->revenue) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-admin.empty-state>
        </section>

        <section>
            <h2 class="mb-3 font-display text-xl font-semibold">{{ __('loop.best_sector_businesses') }}</h2>
            <x-admin.empty-state :empty="$businessesBySector->isEmpty()" :title="__('loop.best_sector_businesses')">
                <div class="loop-table-wrap">
                    <table class="loop-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('loop.sector') }}</th>
                                <th>{{ __('loop.admin_businesses') }}</th>
                                <th>{{ __('loop.live') }}</th>
                                <th>{{ __('loop.paid') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($businessesBySector as $i => $row)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td class="font-semibold">{{ $row->sector_label }}</td>
                                    <td>{{ $row->businesses }}</td>
                                    <td>{{ $row->active_businesses }}</td>
                                    <td>{{ $row->paid_businesses }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-admin.empty-state>
        </section>
    </div>
</x-app-layout>
