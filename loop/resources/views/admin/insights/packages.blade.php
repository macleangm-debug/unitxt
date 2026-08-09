<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.dash_tab_subscriptions') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.package_performance') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.packages_insight_blurb') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.settings', ['tab' => 'packages']) }}" class="loop-btn-mint !py-2">{{ __('loop.edit_packages') }}</a>
                <a href="{{ route('admin.dashboard', ['tab' => 'subscriptions']) }}" class="loop-btn-ghost !py-2">{{ __('loop.back') }}</a>
            </div>
        </div>
    </x-slot>

    @include('admin.partials.nav')

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="loop-glass--ink rounded-[1.5rem] p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-white/55">{{ __('loop.estimated_mrr') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">TZS {{ number_format($estimatedMrr) }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.paid_subscribers') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $paidActive }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.packages') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $packages->count() }}</p>
        </div>
    </div>

    <section class="mt-8">
        <h2 class="mb-3 font-display text-xl font-semibold">{{ __('loop.best_package') }}</h2>
        <x-admin.empty-state :empty="$packages->isEmpty()" :title="__('loop.package_performance')">
            <div class="loop-table-wrap">
                <table class="loop-table">
                    <thead>
                        <tr>
                            <th>{{ __('loop.plan') }}</th>
                            <th>{{ __('loop.price') }}</th>
                            <th>{{ __('loop.paid_subscribers') }}</th>
                            <th>{{ __('loop.trialing') }}</th>
                            <th>{{ __('loop.estimated_mrr') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($packages->sortByDesc('subscribers') as $pkg)
                            <tr>
                                <td class="font-semibold">{{ $pkg->name }}</td>
                                <td>{{ number_format($pkg->price_monthly) }} {{ $pkg->currency }}</td>
                                <td>{{ $pkg->subscribers }}</td>
                                <td>{{ $pkg->trialing }}</td>
                                <td>TZS {{ number_format($pkg->estimated_mrr) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-admin.empty-state>
    </section>

    <section class="mt-10">
        <h2 class="mb-3 font-display text-xl font-semibold">{{ __('loop.payments') }}</h2>
        <p class="mb-3 text-sm text-ink-muted">{{ __('loop.payments_list_blurb') }}</p>
        <x-admin.empty-state :empty="$payments->isEmpty()" :title="__('loop.payments')">
            <div class="loop-table-wrap">
                <table class="loop-table">
                    <thead>
                        <tr>
                            <th>{{ __('loop.when') }}</th>
                            <th>{{ __('loop.business') }}</th>
                            <th>{{ __('loop.plan') }}</th>
                            <th>{{ __('loop.amount') }}</th>
                            <th>{{ __('loop.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $p)
                            <tr>
                                <td>{{ $p->created_at?->diffForHumans() }}</td>
                                <td>{{ $p->business?->name ?? '—' }}</td>
                                <td>{{ $p->plan_key ?? '—' }}</td>
                                <td>{{ $p->currency }} {{ number_format($p->amount) }}</td>
                                <td>{{ $p->status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-admin.empty-state>
    </section>

    <section class="mt-10">
        <h2 class="mb-3 font-display text-xl font-semibold">{{ __('loop.businesses_on_platform') }}</h2>
        <p class="mb-3 text-sm text-ink-muted">{{ __('loop.businesses_on_platform_blurb') }}</p>
        <x-admin.empty-state :empty="$businesses->isEmpty()" :title="__('loop.businesses_on_platform')">
            <div class="loop-table-wrap">
                <table class="loop-table">
                    <thead>
                        <tr>
                            <th>{{ __('loop.business') }}</th>
                            <th>{{ __('loop.plan') }}</th>
                            <th>{{ __('loop.billing_status') }}</th>
                            <th>{{ __('loop.city') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($businesses as $biz)
                            <tr>
                                <td class="font-semibold">{{ $biz->name }}</td>
                                <td>{{ $biz->plan?->name ?? $biz->plan_key }}</td>
                                <td>{{ $biz->billing_status }}</td>
                                <td>{{ $biz->city }}</td>
                                <td class="text-right"><a href="{{ route('admin.businesses.show', $biz) }}" class="text-sm font-semibold text-violet">{{ __('loop.view') }} →</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $businesses->links() }}</div>
        </x-admin.empty-state>
    </section>
</x-app-layout>
