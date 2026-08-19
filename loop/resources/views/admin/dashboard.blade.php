@php
    $tabs = [
        'pulse' => __('loop.dash_tab_pulse'),
        'till' => __('loop.dash_tab_till'),
        'subscriptions' => __('loop.dash_tab_subscriptions'),
        'sectors' => __('loop.dash_tab_sectors'),
        'growth' => __('loop.dash_tab_growth'),
    ];
    $maxDaily = max(1, (int) collect($dailySales)->max('revenue'));
    $maxSignups = max(1, (int) collect($dailySignups)->max('signups'));
    $maxSectorGmv = max(1, (float) collect($salesBySector)->max('revenue'));
    $maxSectorBiz = max(1, (int) collect($businessesBySector)->max('businesses'));
    $maxPkg = max(1, (int) collect($packages)->max('subscribers'), (int) collect($packages)->max('total'));
    $sectorThree = collect($salesBySector)->take(3);
    $bizSectorThree = collect($businessesBySector)->take(3);
    $topThree = collect($topBusinesses)->take(3);
@endphp
<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.admin') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.admin_dashboard') }}</h1>
            <p class="mt-1 max-w-2xl text-ink-muted">{{ __('loop.admin_dashboard_blurb') }}</p>
        </div>
    </x-slot>

    <div class="mb-4 rounded-2xl border border-violet/20 bg-violet-soft/50 px-4 py-3 text-sm text-ink">
        <p class="font-semibold">{{ __('loop.metric_legend_title') }}</p>
        <p class="mt-1 text-ink-muted">{{ __('loop.metric_legend_body') }}</p>
    </div>

    <div class="admin-subnav" role="tablist">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.dashboard', ['tab' => $key]) }}"
               class="{{ $tab === $key ? 'is-active' : '' }}"
               role="tab"
               aria-selected="{{ $tab === $key ? 'true' : 'false' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($tab === 'pulse')
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="admin-stat admin-stat--emphasis">
                <p class="admin-stat__label">{{ __('loop.estimated_mrr') }}</p>
                <p class="admin-stat__value">TZS {{ number_format($estimated_mrr) }}</p>
                <p class="admin-stat__hint">{{ __('loop.estimated_mrr_blurb') }}</p>
            </div>
            <div class="admin-stat">
                <p class="admin-stat__label">{{ __('loop.till_gmv_month') }}</p>
                <p class="admin-stat__value">TZS {{ number_format($revenue_month) }}</p>
                <p class="admin-stat__hint">{{ $sales_month }} {{ __('loop.till_sales') }} · {{ __('loop.avg_ticket') }} TZS {{ number_format($avg_ticket_month) }}</p>
            </div>
            <div class="admin-stat">
                <p class="admin-stat__label">{{ __('loop.paid_subscribers') }}</p>
                <p class="admin-stat__value">{{ $paid_active }}</p>
                <p class="admin-stat__hint">{{ $trialing }} {{ __('loop.trialing') }} · {{ $trial_conversion_pct }}% {{ __('loop.trial_conversion') }}</p>
            </div>
            <div class="admin-stat">
                <p class="admin-stat__label">{{ __('loop.admin_businesses') }}</p>
                <p class="admin-stat__value">{{ $active_businesses }}</p>
                <p class="admin-stat__hint">+{{ $businesses_new_14d }} {{ __('loop.last_14_days') }} · {{ $unique_customers }} {{ __('loop.customers') }}</p>
            </div>
        </div>

        <section class="mt-8">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.insights_title') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.insights_blurb') }}</p>
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($insights as $insight)
                    <article class="admin-insight admin-insight--{{ $insight['tone'] }}">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em]">{{ $insight['title'] }}</p>
                        <p class="mt-2 text-sm leading-relaxed">{{ $insight['body'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="admin-card">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <div>
                        <h2 class="font-display text-xl font-semibold">{{ __('loop.best_sector_gmv') }}</h2>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.best_sector_gmv_blurb') }}</p>
                    </div>
                    <a href="{{ route('admin.insights.sectors') }}" class="text-sm font-semibold text-violet">{{ __('loop.view_more') }} →</a>
                </div>
                <div class="space-y-4">
                    @forelse ($sectorThree as $row)
                        @php $pct = max(4, (int) round(((float) $row->revenue / $maxSectorGmv) * 100)); @endphp
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                <p class="font-semibold">{{ $row->sector_label }}</p>
                                <p class="font-semibold">TZS {{ number_format($row->revenue) }}</p>
                            </div>
                            <div class="admin-hbar"><span style="width: {{ $pct }}%"></span></div>
                            <p class="mt-1 text-xs text-ink-muted">{{ $row->sales_count }} {{ __('loop.till_sales') }} · {{ $row->unique_customers }} {{ __('loop.customers') }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted">{{ __('loop.no_data_yet') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="admin-card">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <div>
                        <h2 class="font-display text-xl font-semibold">{{ __('loop.best_package') }}</h2>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.best_package_blurb') }}</p>
                    </div>
                    <a href="{{ route('admin.insights.packages') }}" class="text-sm font-semibold text-violet">{{ __('loop.view_more') }} →</a>
                </div>
                <div class="space-y-4">
                    @forelse ($packages->sortByDesc('subscribers')->take(4) as $pkg)
                        @php $pct = max(4, (int) round(((int) $pkg->subscribers / max(1, $maxPkg)) * 100)); @endphp
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                <p class="font-semibold">{{ $pkg->name }}</p>
                                <p class="font-semibold">{{ $pkg->subscribers }} {{ __('loop.paid_subscribers_short') }}</p>
                            </div>
                            <div class="admin-hbar admin-hbar--mint"><span style="width: {{ $pct }}%"></span></div>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.estimated_mrr') }} TZS {{ number_format($pkg->estimated_mrr) }} · {{ $pkg->trialing }} {{ __('loop.trialing') }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted">{{ __('loop.no_data_yet') }}</p>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="admin-card">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.till_gmv_14d') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.till_gmv_14d_blurb') }}</p>
                <div class="mt-4 admin-chart">
                    @foreach ($dailySales as $day)
                        @php $h = max(4, (int) round(((float) $day->revenue / $maxDaily) * 100)); @endphp
                        <div class="admin-chart__col" title="{{ $day->day }} · TZS {{ number_format($day->revenue) }}">
                            <div class="admin-chart__bar" style="height: {{ $h }}%"></div>
                            <span class="admin-chart__label">{{ \Illuminate\Support\Carbon::parse($day->day)->format('d') }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
            <section class="admin-card">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.signups_14d') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.signups_14d_blurb') }}</p>
                <div class="mt-4 admin-chart">
                    @foreach ($dailySignups as $day)
                        @php $h = max(4, (int) round(((int) $day->signups / $maxSignups) * 100)); @endphp
                        <div class="admin-chart__col" title="{{ $day->day }} · {{ $day->signups }}">
                            <div class="admin-chart__bar admin-chart__bar--mint" style="height: {{ $h }}%"></div>
                            <span class="admin-chart__label">{{ \Illuminate\Support\Carbon::parse($day->day)->format('d') }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    @endif

    @if ($tab === 'till')
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="admin-stat">
                <p class="text-xs text-ink-muted">{{ __('loop.till_gmv_today') }}</p>
                <p class="mt-2 font-display text-2xl font-semibold">TZS {{ number_format($revenue_today) }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ $sales_today }} {{ __('loop.till_sales') }}</p>
            </div>
            <div class="admin-stat">
                <p class="text-xs text-ink-muted">{{ __('loop.till_gmv_month') }}</p>
                <p class="mt-2 font-display text-2xl font-semibold">TZS {{ number_format($revenue_month) }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ $sales_month }} {{ __('loop.till_sales') }}</p>
            </div>
            <div class="admin-stat">
                <p class="text-xs text-ink-muted">{{ __('loop.till_gmv_all') }}</p>
                <p class="mt-2 font-display text-2xl font-semibold">TZS {{ number_format($revenue_all) }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ $sales_all }} {{ __('loop.till_sales') }}</p>
            </div>
            <div class="admin-stat">
                <p class="text-xs text-ink-muted">{{ __('loop.avg_ticket') }}</p>
                <p class="mt-2 font-display text-2xl font-semibold">TZS {{ number_format($avg_ticket_month) }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.this_month') }}</p>
            </div>
        </div>

        <section class="mt-8 admin-card">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.till_gmv_14d') }}</h2>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.till_gmv_14d_blurb') }}</p>
                </div>
                <a href="{{ route('admin.reports.index', ['tab' => 'trend']) }}" class="text-sm font-semibold text-violet">{{ __('loop.view_more') }} →</a>
            </div>
            <div class="admin-chart">
                @foreach ($dailySales as $day)
                    @php $h = max(4, (int) round(((float) $day->revenue / $maxDaily) * 100)); @endphp
                    <div class="admin-chart__col" title="{{ $day->day }} · TZS {{ number_format($day->revenue) }} · {{ $day->sales_count }}">
                        <div class="admin-chart__bar" style="height: {{ $h }}%"></div>
                        <span class="admin-chart__label">{{ \Illuminate\Support\Carbon::parse($day->day)->format('d') }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="mt-8 admin-card">
            <div class="mb-4 flex items-end justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.top_businesses') }}</h2>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.top_3_businesses_blurb') }}</p>
                </div>
                <a href="{{ route('admin.insights.till-businesses') }}" class="text-sm font-semibold text-violet">{{ __('loop.view_more') }} →</a>
            </div>
            <div class="space-y-3">
                @forelse ($topThree as $biz)
                    <a href="{{ route('admin.businesses.show', $biz->id) }}" class="flex items-center justify-between gap-3 rounded-2xl border border-ink/8 bg-white/70 px-4 py-3 transition hover:border-violet/30">
                        <div>
                            <p class="font-semibold">{{ $biz->name }}</p>
                            <p class="text-xs text-ink-muted">{{ $biz->sector_label }} · {{ $biz->plan_key }}</p>
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
    @endif

    @if ($tab === 'subscriptions')
        <div class="mb-4 rounded-2xl border border-ink/10 bg-chalk/60 px-4 py-3 text-sm text-ink-muted">
            {{ __('loop.subscriptions_note') }}
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="admin-stat admin-stat--emphasis">
                <p class="admin-stat__label">{{ __('loop.estimated_mrr') }}</p>
                <p class="admin-stat__value">TZS {{ number_format($estimated_mrr) }}</p>
            </div>
            <div class="admin-stat">
                <p class="text-xs text-ink-muted">{{ __('loop.paid_subscribers') }}</p>
                <p class="mt-2 font-display text-2xl font-semibold">{{ $paid_active }}</p>
            </div>
            <div class="admin-stat">
                <p class="text-xs text-ink-muted">{{ __('loop.trialing') }}</p>
                <p class="mt-2 font-display text-2xl font-semibold">{{ $trialing }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ $trial_conversion_pct }}% {{ __('loop.trial_conversion') }}</p>
            </div>
            <div class="admin-stat">
                <p class="text-xs text-ink-muted">{{ __('loop.past_due') }} / {{ __('loop.suspended') }}</p>
                <p class="mt-2 font-display text-2xl font-semibold">{{ $past_due }} / {{ $suspended }}</p>
            </div>
        </div>

        <section class="mt-8 admin-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.package_performance') }}</h2>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.package_performance_blurb') }}</p>
                </div>
                <a href="{{ route('admin.insights.packages') }}" class="admin-btn !py-2">{{ __('loop.view_more') }}</a>
            </div>
            <div class="mt-6 space-y-5">
                @foreach ($packages as $pkg)
                    @php
                        $subPct = max(0, (int) round(((int) $pkg->subscribers / max(1, $maxPkg)) * 100));
                        $totalPct = max(4, (int) round(((int) $pkg->total / max(1, (int) collect($packages)->max('total'))) * 100));
                    @endphp
                    <div class="rounded-2xl border border-ink/8 bg-white/70 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-display text-lg font-semibold">{{ $pkg->name }}</p>
                                <p class="text-xs text-ink-muted">{{ $pkg->plan_key }} · {{ number_format($pkg->price_monthly) }} {{ $pkg->currency }}/{{ __('loop.mo') }}</p>
                            </div>
                            <div class="text-right text-sm">
                                <p class="font-semibold">{{ __('loop.estimated_mrr') }} TZS {{ number_format($pkg->estimated_mrr) }}</p>
                                <p class="text-xs text-ink-muted">{{ $pkg->subscribers }} {{ __('loop.paid') }} · {{ $pkg->trialing }} {{ __('loop.trialing') }} · {{ $pkg->total }} {{ __('loop.total') }}</p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="mb-1 flex justify-between text-[11px] font-semibold uppercase tracking-wide text-ink-muted">
                                <span>{{ __('loop.paid_subscribers') }}</span>
                                <span>{{ $subPct }}%</span>
                            </div>
                            <div class="admin-hbar admin-hbar--mint"><span style="width: {{ max(4, $subPct) }}%"></span></div>
                        </div>
                        <div class="mt-3">
                            <div class="mb-1 flex justify-between text-[11px] font-semibold uppercase tracking-wide text-ink-muted">
                                <span>{{ __('loop.all_on_package') }}</span>
                                <span>{{ $totalPct }}%</span>
                            </div>
                            <div class="admin-hbar"><span style="width: {{ $totalPct }}%"></span></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($tab === 'sectors')
        <div class="grid gap-6 lg:grid-cols-2">
            <section class="admin-card">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.best_sector_gmv') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.best_sector_gmv_full_blurb') }}</p>
                <div class="mt-5 space-y-4">
                    @forelse ($salesBySector as $i => $row)
                        @php $pct = max(4, (int) round(((float) $row->revenue / $maxSectorGmv) * 100)); @endphp
                        <div>
                            <div class="mb-1.5 flex flex-wrap items-center justify-between gap-2 text-sm">
                                <p class="font-semibold">
                                    <span class="mr-2 text-ink-muted">#{{ $i + 1 }}</span>{{ $row->sector_label }}
                                </p>
                                <p class="font-semibold">TZS {{ number_format($row->revenue) }}</p>
                            </div>
                            <div class="admin-hbar"><span style="width: {{ $pct }}%"></span></div>
                            <p class="mt-1 text-xs text-ink-muted">{{ $row->sales_count }} {{ __('loop.till_sales') }} · {{ $row->unique_customers }} {{ __('loop.customers') }} · {{ $row->businesses }} {{ __('loop.admin_businesses') }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted">{{ __('loop.no_data_yet') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="admin-card">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.best_sector_businesses') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.best_sector_businesses_blurb') }}</p>
                <div class="mt-5 space-y-4">
                    @forelse ($businessesBySector as $i => $row)
                        @php $pct = max(4, (int) round(((int) $row->businesses / $maxSectorBiz) * 100)); @endphp
                        <div>
                            <div class="mb-1.5 flex flex-wrap items-center justify-between gap-2 text-sm">
                                <p class="font-semibold">
                                    <span class="mr-2 text-ink-muted">#{{ $i + 1 }}</span>{{ $row->sector_label }}
                                </p>
                                <p class="font-semibold">{{ $row->businesses }}</p>
                            </div>
                            <div class="admin-hbar admin-hbar--mint"><span style="width: {{ $pct }}%"></span></div>
                            <p class="mt-1 text-xs text-ink-muted">{{ $row->active_businesses }} {{ __('loop.live') }} · {{ $row->paid_businesses }} {{ __('loop.paid') }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted">{{ __('loop.no_data_yet') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    @endif

    @if ($tab === 'growth')
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="admin-stat">
                <p class="text-xs text-ink-muted">{{ __('loop.businesses_new_14d') }}</p>
                <p class="mt-2 font-display text-2xl font-semibold">{{ $businesses_new_14d }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ $businesses_new_30d }} {{ __('loop.last_30_days') }}</p>
            </div>
            <div class="admin-stat">
                <p class="text-xs text-ink-muted">{{ __('loop.referral_pending') }}</p>
                <p class="mt-2 font-display text-2xl font-semibold">{{ $referralPending }}</p>
            </div>
            <div class="admin-stat">
                <p class="text-xs text-ink-muted">{{ __('loop.referral_rewarded') }}</p>
                <p class="mt-2 font-display text-2xl font-semibold">{{ $referralRewarded }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ $referral_total }} {{ __('loop.total') }}</p>
            </div>
            <div class="admin-stat">
                <p class="text-xs text-ink-muted">{{ __('loop.affiliate_applications') }}</p>
                <p class="mt-2 font-display text-2xl font-semibold">{{ $affiliate_pending }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ $affiliate_active }} {{ __('loop.affiliate_tab_active') }}</p>
            </div>
        </div>

        <section class="mt-8 admin-card">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.signups_14d') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.signups_14d_blurb') }}</p>
            <div class="mt-4 admin-chart">
                @foreach ($dailySignups as $day)
                    @php $h = max(4, (int) round(((int) $day->signups / $maxSignups) * 100)); @endphp
                    <div class="admin-chart__col" title="{{ $day->day }} · {{ $day->signups }}">
                        <div class="admin-chart__bar admin-chart__bar--mint" style="height: {{ $h }}%"></div>
                        <span class="admin-chart__label">{{ \Illuminate\Support\Carbon::parse($day->day)->format('d') }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="mt-6 grid gap-3 sm:grid-cols-3">
            <a href="{{ route('admin.referrals.index') }}" class="rounded-2xl border border-ink/8 bg-white/70 p-4 transition hover:border-violet/30">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.admin_referrals') }}</p>
                <p class="mt-2 font-display text-lg font-semibold">{{ __('loop.referral_progress_title') }}</p>
            </a>
            <a href="{{ route('admin.insights.affiliate-performance') }}" class="rounded-2xl border border-ink/8 bg-white/70 p-4 transition hover:border-violet/30">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.admin_affiliates') }}</p>
                <p class="mt-2 font-display text-lg font-semibold">{{ __('loop.affiliate_performance') }}</p>
            </a>
            <a href="{{ route('admin.settings', ['tab' => 'product']) }}" class="rounded-2xl border border-ink/8 bg-white/70 p-4 transition hover:border-violet/30">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.admin_product_updates') }}</p>
                <p class="mt-2 font-display text-lg font-semibold">{{ $features_on }} {{ __('loop.on') }} / {{ $features_off }} {{ __('loop.off') }}</p>
            </a>
        </div>
    @endif
</x-admin-layout>
