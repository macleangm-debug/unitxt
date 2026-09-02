<x-app-layout>
    <div class="mb-3">
        <x-back-icon :href="route('customers.index')" />
    </div>
    <section>
        <div class="loop-wallet relative overflow-hidden p-5 sm:p-8">
            <div class="relative grid gap-5 sm:gap-6 lg:grid-cols-[auto_1fr_auto] lg:items-center">
                <div class="mx-auto lg:mx-0">
                    <div class="flex h-28 w-28 items-center justify-center rounded-[1.5rem] bg-gradient-to-br from-violet to-lime/70 font-display text-4xl font-semibold text-white ring-4 ring-white/15 sm:h-36 sm:w-36">
                        {{ mb_substr($customer->name, 0, 1) }}
                    </div>
                </div>
                <div class="min-w-0 text-center lg:text-left">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-lime">{{ __('loop.customers') }}</p>
                    <h1 class="mt-1.5 font-display text-3xl font-semibold sm:text-4xl">{{ $customer->name }}</h1>
                    <p class="mt-1.5 text-sm text-white/70">
                        {{ $customer->full_phone }}
                        @if ($customer->hasBirthday())
                            · {{ __('loop.birthday') }} {{ $customer->birth_day }}/{{ $customer->birth_month }}
                        @endif
                        @if ($customer->gender)
                            · {{ $customer->gender === 'female' ? __('loop.gender_female') : __('loop.gender_male') }}
                        @endif
                    </p>
                </div>
                <div class="text-center lg:text-right">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white/50">{{ __('loop.points') }}</p>
                    <p class="mt-1.5 font-display text-3xl font-semibold text-lime">{{ number_format($points) }}</p>
                    <p class="mt-1 text-xs text-white/55">{{ __('loop.lifetime_points') }}: {{ number_format($lifetime) }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-6">
        <div class="grid grid-cols-3 gap-2.5">
            <div class="rounded-2xl border border-ink/10 bg-white px-4 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.sales') }}</p>
                <p class="mt-1 font-display text-2xl font-semibold">{{ number_format((int) $visitCount) }}</p>
            </div>
            <div class="rounded-2xl border border-ink/10 bg-white px-4 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.spend') }}</p>
                <p class="mt-1 font-display text-xl font-semibold">{{ number_format($totalSpend, 0) }}</p>
                <p class="mt-0.5 text-[10px] text-ink-muted">{{ $business->currency }}</p>
            </div>
            <div class="rounded-2xl border border-ink/10 bg-white px-4 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.shops') }}</p>
                <p class="mt-1 font-display text-2xl font-semibold">{{ $memberships->count() }}</p>
            </div>
        </div>
    </section>

    @if ($memberships->isNotEmpty())
        <section class="mt-6">
            <h2 class="font-display text-lg font-semibold">{{ __('loop.shops') }}</h2>
            <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
                @foreach ($memberships as $membership)
                    <div class="rounded-2xl border border-ink/10 bg-white px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold">{{ $membership->shop?->name ?? $business->name }}</p>
                                <p class="mt-0.5 text-sm text-ink-muted">{{ $membership->shop?->city }}</p>
                            </div>
                            <span class="shrink-0 rounded-lg bg-violet-soft px-2.5 py-1 text-xs font-semibold text-violet-deep">
                                {{ number_format((int) $membership->points_balance) }} {{ __('loop.pts') }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($visits->isNotEmpty())
        <section class="mt-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-display text-lg font-semibold">{{ __('loop.recent_sales') }}</h2>
                <a href="{{ route('transactions.index') }}" class="text-sm font-semibold text-violet">{{ __('loop.view_all') }}</a>
            </div>
            <div class="mt-3 grid gap-2.5">
                @foreach ($visits as $visit)
                    <div class="flex items-start gap-3 rounded-2xl border border-ink/10 bg-white px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold">{{ $visit->shop?->name }}</p>
                            <p class="mt-0.5 text-sm text-ink-muted">{{ $visit->created_at->format('d M · H:i') }} · {{ $business->currency }} {{ number_format($visit->amount_spent, 0) }}</p>
                        </div>
                        <span class="shrink-0 rounded-lg bg-mint-soft px-2.5 py-1 text-sm font-semibold text-mint-deep">+{{ number_format((int) $visit->points_earned) }} {{ __('loop.pts') }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($raffleWins->isNotEmpty())
        <section class="mt-6">
            <h2 class="font-display text-lg font-semibold">{{ __('loop.raffle_wins') }}</h2>
            <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
                @foreach ($raffleWins as $win)
                    <div class="rounded-2xl border border-lime/40 bg-lime/10 px-4 py-3.5">
                        <p class="font-semibold">{{ $win->raffle->name }}</p>
                        <p class="mt-0.5 text-sm text-ink-muted">
                            {{ $win->raffle->prize_name }}
                            · {{ __('loop.raffle_winner_status_'.$win->status) }}
                            · {{ $win->claimHeadline() }}
                            @if ($win->claim_by)
                                · {{ __('loop.claim_by') }} {{ $win->claim_by->format('d M Y') }}
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
