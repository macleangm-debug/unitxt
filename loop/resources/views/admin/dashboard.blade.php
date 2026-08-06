<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.admin') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.admin_dashboard') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.admin_dashboard_blurb') }}</p>
        </div>
    </x-slot>

    @include('admin.partials.nav')

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-[1.5rem] bg-gradient-to-br from-ink to-ink-soft p-5 text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-white/55">{{ __('loop.revenue_today') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">TZS {{ number_format($revenue_today) }}</p>
            <p class="mt-1 text-xs text-white/55">{{ $sales_today }} {{ __('loop.sales') }}</p>
        </div>
        <div class="loop-panel p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.revenue_month') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">TZS {{ number_format($revenue_month) }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ $sales_month }} {{ __('loop.sales') }}</p>
        </div>
        <div class="loop-panel p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.unique_customers') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $unique_customers }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ $unique_customers_with_membership }} {{ __('loop.with_memberships') }}</p>
        </div>
        <div class="loop-panel p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.admin_businesses') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $active_businesses }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ $paid_active }} {{ __('loop.paid') }} · {{ $trialing }} {{ __('loop.trialing') }} · {{ $past_due }} {{ __('loop.past_due') }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-ink/10 bg-white p-4">
            <p class="text-sm text-ink-muted">{{ __('loop.referral_pending') }}</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ $referralPending }}</p>
        </div>
        <div class="rounded-2xl border border-ink/10 bg-white p-4">
            <p class="text-sm text-ink-muted">{{ __('loop.referral_qualified') }}</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ $referralQualified }}</p>
        </div>
        <div class="rounded-2xl border border-ink/10 bg-white p-4">
            <p class="text-sm text-ink-muted">{{ __('loop.referral_rewarded') }}</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ $referralRewarded }}</p>
        </div>
    </div>

    <div class="mt-10 grid gap-6 lg:grid-cols-2">
        <section>
            <div class="mb-4 flex items-end justify-between">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.sales_by_sector') }}</h2>
                <a href="{{ route('admin.reports.index') }}" class="text-sm font-semibold text-mint-deep">{{ __('loop.admin_reports') }} →</a>
            </div>
            <div class="space-y-2">
                @forelse ($salesBySector as $row)
                    <div class="loop-panel flex items-center justify-between gap-3 p-4">
                        <div>
                            <p class="font-semibold">{{ $row->sector_label }}</p>
                            <p class="text-xs text-ink-muted">{{ $row->unique_customers }} {{ __('loop.unique_customers') }} · {{ $row->sales_count }} {{ __('loop.sales') }}</p>
                        </div>
                        <p class="font-semibold">TZS {{ number_format($row->revenue) }}</p>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">{{ __('loop.no_sales_yet') }}</p>
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="mb-4 font-display text-xl font-semibold">{{ __('loop.top_businesses') }}</h2>
            <div class="space-y-2">
                @foreach ($topBusinesses as $biz)
                    <div class="loop-panel flex items-center justify-between gap-3 p-4">
                        <div>
                            <p class="font-semibold">{{ $biz->name }}</p>
                            <p class="text-xs text-ink-muted">{{ $biz->sector_label }} · {{ $biz->plan_key }} · {{ $biz->billing_status }}</p>
                        </div>
                        <div class="text-right text-sm">
                            <p class="font-semibold">{{ $biz->unique_customers }} {{ __('loop.customers') }}</p>
                            <p class="text-xs text-ink-muted">TZS {{ number_format($biz->revenue) }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <section class="mt-10">
        <h2 class="mb-4 font-display text-xl font-semibold">{{ __('loop.last_14_days') }}</h2>
        <div class="overflow-x-auto rounded-[1.5rem] border border-ink/10 bg-white">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-ink/5 text-xs uppercase tracking-wide text-ink-muted">
                    <tr>
                        <th class="px-4 py-3">{{ __('loop.day') }}</th>
                        <th class="px-4 py-3">{{ __('loop.sales') }}</th>
                        <th class="px-4 py-3">{{ __('loop.revenue') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dailySales as $day)
                        <tr class="border-b border-ink/5 last:border-0">
                            <td class="px-4 py-3 font-medium">{{ $day->day }}</td>
                            <td class="px-4 py-3">{{ $day->sales_count }}</td>
                            <td class="px-4 py-3">TZS {{ number_format($day->revenue) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-ink-muted">{{ __('loop.no_sales_yet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
