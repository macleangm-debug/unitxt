<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.admin_affiliates') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.affiliate_performance') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.affiliate_performance_blurb') }}</p>
            </div>
            <a href="{{ route('admin.affiliates.index', ['tab' => 'performance']) }}" class="loop-btn-ghost !py-2">{{ __('loop.back') }}</a>
        </div>
    </x-slot>

    @include('admin.partials.nav')

    <div class="mb-4 rounded-2xl border border-violet/20 bg-violet-soft/50 px-4 py-3 text-sm">
        <p class="font-semibold">{{ __('loop.affiliate_kpi_banner') }}</p>
        <p class="mt-1 text-ink-muted">{{ __('loop.affiliate_kpi_banner_body', ['target' => $totals['target']]) }}</p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="loop-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.affiliate_tab_active') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $totals['active'] }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.month_signups') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $totals['month_signups'] }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.month_paying') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $totals['month_paying'] }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.kpi_target') }}: {{ $totals['target'] }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.commission_earned') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">TZS {{ number_format($totals['commission']) }}</p>
        </div>
    </div>

    <section class="mt-8">
        <x-admin.empty-state :empty="$affiliates->isEmpty()" :title="__('loop.affiliate_performance')">
            <div class="loop-table-wrap">
                <table class="loop-table">
                    <thead>
                        <tr>
                            <th>{{ __('loop.name') }}</th>
                            <th>{{ __('loop.status') }}</th>
                            <th>{{ __('loop.signups') }}</th>
                            <th>{{ __('loop.month_paying') }}</th>
                            <th>{{ __('loop.kpi') }}</th>
                            <th>{{ __('loop.earned') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($affiliates as $row)
                            @php
                                $paying = (int) ($row->month_paying_count ?? 0);
                                $hit = $paying >= $totals['target'];
                            @endphp
                            <tr>
                                <td class="font-semibold">{{ $row->name }}</td>
                                <td>{{ __('loop.affiliate_status_'.$row->status) }}</td>
                                <td>{{ $row->referrals_count }}</td>
                                <td>{{ $paying }}</td>
                                <td>
                                    <span class="rounded-lg px-2 py-1 text-xs font-semibold {{ $hit ? 'bg-mint-soft' : 'bg-coral/15' }}">
                                        {{ $hit ? __('loop.kpi_met') : __('loop.kpi_behind') }}
                                    </span>
                                </td>
                                <td>TZS {{ number_format((int) ($row->commission_earned ?? 0)) }}</td>
                                <td class="text-right"><a href="{{ route('admin.affiliates.show', $row) }}" class="text-sm font-semibold text-violet">{{ __('loop.view') }} →</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $affiliates->links() }}</div>
        </x-admin.empty-state>
    </section>
</x-app-layout>
