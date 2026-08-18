<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.customers') }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ $customer->name }}</h1>
                </div>
                <p class="mt-2 text-sm text-ink-muted">
                    {{ $customer->full_phone }}
                    @if ($customer->gender)
                        · {{ $customer->gender === 'female' ? __('loop.gender_female') : __('loop.gender_male') }}
                    @endif
                </p>
            </div>
            <a href="{{ route('customers.index') }}" class="loop-btn-ghost !py-2.5">{{ __('loop.back') }}</a>
        </div>
    </x-slot>

    <div class="mt-6 rounded-[1.5rem] border border-ink/10 bg-white px-5 py-5">
        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ __('loop.points') }}</p>
        <p class="mt-2 font-display text-2xl font-bold text-ink sm:text-3xl">{{ number_format($points) }} {{ __('loop.pts') }}</p>
        <p class="mt-2 text-sm text-ink-muted">{{ __('loop.lifetime_points') }}: {{ number_format($lifetime) }}</p>
    </div>

    <div class="mt-6 grid grid-cols-3 gap-3">
        <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.sales') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold sm:text-3xl">{{ $visitCount }}</p>
        </div>
        <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.spend') }}</p>
            <p class="mt-2 font-display text-xl font-semibold sm:text-2xl">{{ number_format($totalSpend, 0) }}</p>
            <p class="mt-0.5 text-[10px] text-ink-muted">{{ $business->currency }}</p>
        </div>
        <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.shops') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold sm:text-3xl">{{ $memberships->count() }}</p>
        </div>
    </div>

    @if ($visits->isNotEmpty())
        <section class="mt-8">
            <div class="mb-4 flex items-end justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.recent_sales') }}</h2>
                </div>
                <a href="{{ route('transactions.index') }}" class="shrink-0 text-sm font-semibold text-mint-deep">{{ __('loop.view_all') }} →</a>
            </div>
            <div class="space-y-3">
                @foreach ($visits as $visit)
                    <div class="flex items-center justify-between gap-4 rounded-2xl border border-ink/10 bg-white px-4 py-3.5">
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $visit->shop?->name }}</p>
                            <p class="mt-0.5 text-xs text-ink-muted">{{ $visit->created_at->format('d M · H:i') }} · +{{ $visit->points_earned }} {{ __('loop.pts') }}</p>
                        </div>
                        <p class="shrink-0 font-display text-lg font-semibold">{{ number_format($visit->amount_spent, 0) }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($raffleWins->isNotEmpty())
        <section class="mt-8">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.raffle_wins') }}</h2>
            <div class="mt-4 space-y-3">
                @foreach ($raffleWins as $win)
                    <div class="flex items-center justify-between gap-4 rounded-2xl border border-ink/10 bg-white px-4 py-3.5">
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $win->raffle->name }}</p>
                            <p class="mt-0.5 text-xs text-ink-muted">
                                {{ $win->raffle->prize_name }}
                                · {{ __('loop.raffle_winner_status_'.$win->status) }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
