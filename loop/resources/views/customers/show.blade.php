<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.customers') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ $customer->name }}</h1>
                <p class="mt-1 text-ink-muted">{{ $customer->full_phone }}</p>
            </div>
            <a href="{{ route('customers.index') }}" class="loop-btn-ghost !py-2">{{ __('loop.back') }}</a>
        </div>
    </x-slot>

    <div class="grid gap-5 sm:grid-cols-2">
        <div class="rounded-[1.75rem] bg-gradient-to-br from-mint/25 to-white p-6 ring-1 ring-mint/15">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.points') }}</p>
            <p class="mt-3 font-display text-4xl font-semibold">{{ number_format($points) }}</p>
        </div>
        <div class="rounded-[1.75rem] bg-ink p-6 text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-white/55">{{ __('loop.lifetime_points') }}</p>
            <p class="mt-3 font-display text-4xl font-semibold">{{ number_format($lifetime) }}</p>
        </div>
    </div>

    <section class="mt-10">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.wallets') }}</h2>
        <div class="mt-4 space-y-3">
            @foreach ($memberships as $membership)
                <div class="rounded-[1.25rem] border border-ink/8 bg-white/90 px-4 py-3">
                    <p class="font-semibold">{{ $membership->shop?->name ?? $business->name }}</p>
                    <p class="text-sm text-ink-muted">{{ number_format($membership->points_balance) }} pts · {{ $membership->member_code }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mt-10">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.recent_sales') }}</h2>
        <div class="mt-4 space-y-3">
            @forelse ($visits as $visit)
                <div class="flex items-center justify-between gap-4 rounded-[1.25rem] border border-ink/8 bg-white/90 px-4 py-3">
                    <div>
                        <p class="text-sm font-semibold">{{ $visit->shop?->name }}</p>
                        <p class="text-xs text-ink-muted">{{ $visit->created_at->format('d M Y · H:i') }}</p>
                    </div>
                    <p class="font-display text-xl font-semibold">{{ $business->currency }} {{ number_format($visit->amount_spent, 0) }}</p>
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.no_sales') }}</p>
            @endforelse
        </div>
    </section>
</x-app-layout>
