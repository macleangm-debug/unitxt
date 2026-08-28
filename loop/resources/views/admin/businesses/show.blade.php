<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex min-w-0 items-start gap-4">
                @if ($business->logoUrl())
                    <img src="{{ $business->logoUrl() }}" alt="" class="h-16 w-16 rounded-2xl object-cover ring-1 ring-ink/10">
                @else
                    <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-ink font-display text-2xl font-semibold text-lime">{{ mb_substr($business->name, 0, 1) }}</span>
                @endif
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.admin_businesses') }}</p>
                    <h1 class="mt-1 font-display text-3xl font-semibold">{{ $business->name }}</h1>
                    <p class="mt-1 text-ink-muted">
                        {{ \App\Support\Sectors::label($business->sector, $business->sector_other) }}
                        · {{ $business->city }}
                        · {{ $business->owner?->full_phone }}
                    </p>
                </div>
            </div>
            <a href="{{ route('admin.businesses.index') }}" class="admin-btn-ghost !py-2">{{ __('loop.back') }}</a>
        </div>
    </x-slot>

    @if ($abuseFlag)
        <p class="mb-4 rounded-2xl bg-coral/10 px-4 py-3 text-sm font-medium">{{ __('loop.admin_multi_branch_flag') }}</p>
    @endif

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <div class="admin-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.revenue_all') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">TZS {{ number_format($revenue) }}</p>
        </div>
        <div class="admin-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.revenue_month') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">TZS {{ number_format($revenueMonth) }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ $salesMonth }} {{ __('loop.sales') }}</p>
        </div>
        <div class="admin-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.members') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ number_format((int) $business->memberships_count) }}</p>
        </div>
        <div class="admin-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.campaigns') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ number_format((int) $business->campaigns_count) }}</p>
        </div>
        <div class="admin-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.offers') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ number_format((int) $business->rewards_count) }}</p>
        </div>
        <div class="admin-stat">
            <p class="text-xs text-ink-muted">{{ __('loop.admin_roles') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $roleCounts['owner'] + $roleCounts['front_desk'] }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ $roleCounts['owner'] }} {{ __('loop.owner') }} · {{ $roleCounts['front_desk'] }} {{ __('loop.front_desk') }}</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="admin-card space-y-4">
            <h2 class="font-display text-xl font-semibold">{{ $business->name }}</h2>
            <dl class="admin-dl">
                <dt>{{ __('loop.hotline') }}</dt>
                <dd>{{ $business->hotline ?: '—' }}</dd>
                <dt>{{ __('loop.presence_label') }}</dt>
                <dd>
                    @if ($business->presence === 'online')
                        {{ __('loop.presence_online') }}
                    @elseif ($business->presence === 'both')
                        {{ __('loop.presence_both') }}
                    @else
                        {{ __('loop.presence_physical') }}
                    @endif
                </dd>
                <dt>{{ __('loop.plan') }}</dt>
                <dd class="capitalize">{{ $business->plan?->name ?: $business->plan_key }} · {{ $business->billing_status }}</dd>
                <dt>{{ __('loop.branches') }}</dt>
                <dd>{{ $business->shops_count }}</dd>
                <dt>{{ __('loop.ref_code') }}</dt>
                <dd>{{ $business->referral_code ?: '—' }} · {{ $business->referrals_made_count }} {{ __('loop.referrals') }}</dd>
            </dl>
            @if ($business->description)
                <p class="text-sm leading-relaxed text-ink-muted">{{ $business->description }}</p>
            @endif
            <h3 class="font-display text-lg font-semibold">{{ __('loop.branches') }}</h3>
            <ul class="space-y-1 text-sm text-ink-muted">
                @forelse ($business->shops as $shop)
                    <li>{{ $shop->name }} · {{ $shop->city }}</li>
                @empty
                    <li>{{ __('loop.no_data_yet') }}</li>
                @endforelse
            </ul>
        </section>

        <form method="POST" action="{{ route('admin.businesses.update', $business) }}" class="admin-card space-y-4">
            @csrf
            @method('PATCH')
            <h2 class="font-display text-xl font-semibold">{{ __('loop.manage_business') }}</h2>
            <div>
                <label class="loop-label">{{ __('loop.plan') }}</label>
                <select name="plan_key" class="loop-input">
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->key }}" @selected($business->plan_key === $plan->key)>{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.billing_status') }}</label>
                <select name="billing_status" class="loop-input">
                    @foreach (['trialing', 'active', 'free', 'past_due', 'paused', 'suspended'] as $status)
                        <option value="{{ $status }}" @selected($business->billing_status === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm font-semibold">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="rounded border-ink/20 text-violet focus:ring-violet" @checked($business->is_active)>
                {{ __('loop.active') }}
            </label>
            <p class="text-xs text-ink-muted">{{ __('loop.ref_code') }}: <span class="font-semibold text-ink">{{ $business->referral_code }}</span> · {{ $business->referrals_made_count }} {{ __('loop.referrals') }}</p>
            <button class="admin-btn w-full">{{ __('loop.save') }}</button>
        </form>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="admin-card">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.campaigns') }}</h2>
            <div class="mt-4 space-y-2">
                @forelse ($business->campaigns as $campaign)
                    <div class="rounded-xl bg-white/70 px-3 py-2.5">
                        <p class="font-semibold">{{ $campaign->name }}</p>
                        <p class="mt-0.5 text-xs text-ink-muted">{{ $campaign->ruleSummary($business->currency) }} · {{ $campaign->is_active ? __('loop.live') : __('loop.off') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">{{ __('loop.no_data_yet') }}</p>
                @endforelse
            </div>
        </section>
        <section class="admin-card">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.offers') }}</h2>
            <div class="mt-4 space-y-2">
                @forelse ($business->rewards as $reward)
                    <div class="rounded-xl bg-white/70 px-3 py-2.5">
                        <p class="font-semibold">{{ $reward->name }}</p>
                        <p class="mt-0.5 text-xs text-ink-muted">{{ $reward->label() }} · {{ number_format((int) $reward->points_cost) }} {{ __('loop.pts') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">{{ __('loop.no_data_yet') }}</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="admin-card mt-8">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.raffles') }}</h2>
        <div class="mt-4 space-y-2">
            @forelse ($business->raffles as $raffle)
                <div class="rounded-xl bg-white/70 px-3 py-2.5">
                    <p class="font-semibold">{{ $raffle->name }}</p>
                    <p class="mt-0.5 text-xs text-ink-muted">{{ $raffle->prize_name }} · {{ __('loop.freq_'.$raffle->frequency) }} · {{ $raffle->nextDrawDate()->format('d M Y') }}</p>
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.no_data_yet') }}</p>
            @endforelse
        </div>
    </section>

    <section class="admin-card mt-8">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.sector_peers') }}</h2>
        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.sector_peers_blurb', ['sector' => \App\Support\Sectors::label($business->sector, $business->sector_other)]) }}</p>
        <div class="mt-4 space-y-2">
            @forelse ($sectorPeers as $peer)
                <a href="{{ route('admin.businesses.show', $peer) }}" class="flex items-center justify-between rounded-xl bg-white/70 px-3 py-2.5 text-sm hover:bg-white">
                    <span class="font-semibold">{{ $peer->name }}</span>
                    <span class="text-ink-muted">{{ $peer->visits_count }} {{ __('loop.sales') }}</span>
                </a>
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.no_data_yet') }}</p>
            @endforelse
        </div>
    </section>

    @if ($recentVisits->isNotEmpty())
    <section class="mt-8">
        <h2 class="mb-3 font-display text-xl font-semibold">{{ __('loop.recent_sales') }}</h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>{{ __('loop.when') }}</th>
                        <th>{{ __('loop.customer') }}</th>
                        <th>{{ __('loop.shop') }}</th>
                        <th>{{ __('loop.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentVisits as $visit)
                        <tr>
                            <td>{{ $visit->created_at?->diffForHumans() }}</td>
                            <td>
                                @if ($visit->customer)
                                    <a href="{{ route('admin.insights.customers.show', $visit->customer) }}" class="font-semibold text-violet">{{ $visit->customer->name }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $visit->shop?->name ?? '—' }}</td>
                            <td>TZS {{ number_format($visit->amount_spent) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif
</x-admin-layout>
