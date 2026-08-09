@php
    $tab = request('tab', 'progress');
    if (! in_array($tab, ['progress', 'program'], true)) {
        $tab = 'progress';
    }
@endphp
<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.admin_referrals') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.admin_referrals_blurb') }}</p>
    </x-slot>

    @include('admin.partials.nav')

    <div class="loop-admin-tabs" role="tablist">
        <a href="{{ route('admin.referrals.index', ['tab' => 'progress']) }}"
           class="loop-admin-tab {{ $tab === 'progress' ? 'is-active' : '' }}">{{ __('loop.referral_tab_progress') }}</a>
        <a href="{{ route('admin.referrals.program') }}"
           class="loop-admin-tab {{ request()->routeIs('admin.referrals.program*') ? 'is-active' : '' }}">{{ __('loop.referral_tab_program') }}</a>
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="loop-stat">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.referral_pending') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $counts['pending'] }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.referral_qualified') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $counts['qualified'] }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.referral_rewarded') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $counts['rewarded'] }}</p>
        </div>
        <div class="loop-stat">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.referral_total') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $counts['total'] }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.referral_biz_to_biz') }}</p>
        </div>
    </div>

    <section class="mb-6 loop-glass p-5">
        <h2 class="font-display text-lg font-semibold">{{ __('loop.referral_progress_title') }}</h2>
        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.referral_progress_blurb') }}</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl bg-white/70 px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.referral_conversion') }}</p>
                <p class="mt-1 font-display text-xl font-semibold">{{ $counts['conversion_pct'] }}%</p>
                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.referral_conversion_blurb') }}</p>
            </div>
            <div class="rounded-2xl bg-white/70 px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.referral_active_referrers') }}</p>
                <p class="mt-1 font-display text-xl font-semibold">{{ $counts['active_referrers'] }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.referral_active_referrers_blurb') }}</p>
            </div>
            <div class="rounded-2xl bg-white/70 px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.referral_last_7_days') }}</p>
                <p class="mt-1 font-display text-xl font-semibold">{{ $counts['last_7_days'] }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.referral_last_7_days_blurb') }}</p>
            </div>
        </div>
    </section>

    <div class="loop-table-wrap">
        <table class="loop-table">
            <thead>
                <tr>
                    <th>{{ __('loop.referrer') }}</th>
                    <th>{{ __('loop.referred') }}</th>
                    <th>{{ __('loop.ref_code') }}</th>
                    <th>{{ __('loop.status') }}</th>
                    <th>{{ __('loop.when') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($referrals as $referral)
                    <tr>
                        <td class="font-semibold">{{ $referral->referrer?->name ?? '—' }}</td>
                        <td>{{ $referral->referred?->name ?? '—' }}</td>
                        <td class="font-mono text-xs">{{ $referral->code_used }}</td>
                        <td>
                            <span class="rounded-lg bg-chalk px-2 py-1 text-xs font-semibold capitalize">{{ $referral->status }}</span>
                            @if ($referral->reward_type)
                                <span class="mt-1 block text-[11px] text-ink-muted">{{ $referral->reward_type }} × {{ $referral->reward_value }}</span>
                            @endif
                        </td>
                        <td class="text-ink-muted">{{ $referral->created_at->diffForHumans() }}</td>
                        <td class="text-right">
                            <div class="flex flex-wrap justify-end gap-2">
                                @if ($referral->isPending())
                                    <form method="POST" action="{{ route('admin.referrals.qualify', $referral) }}">
                                        @csrf
                                        <button class="loop-btn-ghost !py-1.5 !text-xs">{{ __('loop.mark_qualified') }}</button>
                                    </form>
                                @endif
                                @if (! $referral->isRewarded())
                                    <form method="POST" action="{{ route('admin.referrals.reward', $referral) }}">
                                        @csrf
                                        <button class="loop-btn-mint !py-1.5 !text-xs">{{ __('loop.grant_reward') }}</button>
                                    </form>
                                @else
                                    <span class="rounded-lg bg-mint-soft px-3 py-1.5 text-xs font-semibold">{{ __('loop.rewarded') }}</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-ink-muted">{{ __('loop.no_referrals_yet') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $referrals->links() }}</div>
</x-app-layout>
