@php
    $maxDaily = max(1, (int) collect($dailySales)->max('revenue'));
    $maxSector = max(1, (float) collect($salesBySector)->take(3)->max('revenue'));
    $topThree = collect($topBusinesses)->take(3);
    $sectorThree = collect($salesBySector)->take(3);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.admin') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.admin_dashboard') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.admin_dashboard_blurb') }}</p>
        </div>
    </x-slot>

    @include('admin.partials.nav')

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="loop-glass--ink rounded-[1.5rem] p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-white/55">{{ __('loop.revenue_today') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">TZS {{ number_format($revenue_today) }}</p>
            <p class="mt-1 text-xs text-white/55">{{ $sales_today }} {{ __('loop.sales') }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.revenue_month') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">TZS {{ number_format($revenue_month) }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ $sales_month }} {{ __('loop.sales') }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.unique_customers') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $unique_customers }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ $unique_customers_with_membership }} {{ __('loop.with_memberships') }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.admin_businesses') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $active_businesses }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ $paid_active }} {{ __('loop.paid') }} · {{ $trialing }} {{ __('loop.trialing') }} · {{ $past_due }} {{ __('loop.past_due') }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-3">
        <div class="loop-stat">
            <p class="text-sm text-ink-muted">{{ __('loop.referral_pending') }}</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ $referralPending }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-sm text-ink-muted">{{ __('loop.referral_qualified') }}</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ $referralQualified }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-sm text-ink-muted">{{ __('loop.referral_rewarded') }}</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ $referralRewarded }}</p>
        </div>
    </div>

    <section class="mt-10 loop-glass p-6">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.last_14_days_sales') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.last_14_days_sales_blurb') }}</p>
            </div>
            <a href="{{ route('admin.reports.index', ['tab' => 'trend']) }}" class="text-sm font-semibold text-violet">{{ __('loop.view_more') }} →</a>
        </div>
        <div class="loop-bar-chart">
            @foreach ($dailySales as $day)
                @php
                    $h = max(4, (int) round(((float) $day->revenue / $maxDaily) * 100));
                    $label = \Illuminate\Support\Carbon::parse($day->day)->format('d');
                @endphp
                <div class="loop-bar-chart__col" title="{{ $day->day }} · TZS {{ number_format($day->revenue) }} · {{ $day->sales_count }} {{ __('loop.sales') }}">
                    <div class="loop-bar-chart__bar" style="height: {{ $h }}%"></div>
                    <span class="loop-bar-chart__label">{{ $label }}</span>
                </div>
            @endforeach
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="loop-table min-w-full">
                <thead>
                    <tr>
                        <th>{{ __('loop.day') }}</th>
                        <th>{{ __('loop.sales') }}</th>
                        <th>{{ __('loop.revenue') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dailySales->take(5) as $day)
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

    <div class="mt-10 grid gap-6 lg:grid-cols-2">
        <section class="loop-glass p-6">
            <div class="mb-4 flex items-end justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.sales_by_sector') }}</h2>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.top_3_sectors_blurb') }}</p>
                </div>
                <a href="{{ route('admin.reports.index', ['tab' => 'sectors']) }}" class="text-sm font-semibold text-violet">{{ __('loop.view_more') }} →</a>
            </div>
            <div class="space-y-4">
                @forelse ($sectorThree as $row)
                    @php $pct = max(4, (int) round(((float) $row->revenue / $maxSector) * 100)); @endphp
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                            <p class="font-semibold">{{ $row->sector_label }}</p>
                            <p class="font-semibold">TZS {{ number_format($row->revenue) }}</p>
                        </div>
                        <div class="loop-hbar"><span style="width: {{ $pct }}%"></span></div>
                        <p class="mt-1 text-xs text-ink-muted">{{ $row->sales_count }} {{ __('loop.sales') }} · {{ $row->unique_customers }} {{ __('loop.unique_customers') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">{{ __('loop.no_sales_yet') }}</p>
                @endforelse
            </div>
        </section>

        <section class="loop-glass p-6">
            <div class="mb-4 flex items-end justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.top_businesses') }}</h2>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.top_3_businesses_blurb') }}</p>
                </div>
                <a href="{{ route('admin.businesses.index') }}" class="text-sm font-semibold text-violet">{{ __('loop.view_more') }} →</a>
            </div>
            <div class="space-y-3">
                @forelse ($topThree as $biz)
                    <a href="{{ route('admin.businesses.show', $biz->id) }}" class="flex items-center justify-between gap-3 rounded-2xl border border-ink/8 bg-white/70 px-4 py-3 transition hover:border-violet/30">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-soft font-display text-sm font-semibold text-violet">
                                {{ mb_substr($biz->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="font-semibold">{{ $biz->name }}</p>
                                <p class="text-xs text-ink-muted">{{ $biz->sector_label }} · {{ $biz->plan_key }}</p>
                            </div>
                        </div>
                        <div class="text-right text-sm">
                            <p class="font-semibold">TZS {{ number_format($biz->revenue) }}</p>
                            <p class="text-xs text-ink-muted">{{ $biz->unique_customers }} {{ __('loop.customers') }}</p>
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-ink-muted">{{ __('loop.no_data_yet') }}</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
