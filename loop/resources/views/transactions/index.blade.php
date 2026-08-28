<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ __('loop.transactions') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.transactions_blurb') }}</p>
            </div>
            <a href="{{ route('campaigns.index') }}" class="loop-btn-ghost !py-2.5">{{ __('loop.back') }}</a>
        </div>
    </x-slot>

    <div class="space-y-3">
        @forelse ($visits as $visit)
            <div class="flex items-start gap-3 rounded-[1.5rem] border border-ink/8 bg-white/90 px-5 py-4">
                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold">{{ $visit->customer?->name ?? __('loop.customer') }}</p>
                    <p class="mt-0.5 text-sm text-ink-muted">
                        @if ($showAmounts ?? true)
                            {{ $business->currency }} {{ number_format($visit->amount_spent, 0) }}
                        @else
                            {{ __('loop.amount_hidden') }}
                        @endif
                        · {{ $visit->created_at->format('d M Y · H:i') }}
                        @if ($visit->channel === 'phone_order')
                            · {{ __('loop.phone_order') }}
                        @elseif ($visit->channel)
                            · {{ __('loop.in_store') }}
                        @endif
                    </p>
                    <p class="mt-0.5 truncate text-xs text-ink-muted">{{ $visit->shop?->name }}</p>
                    @if ($visit->points_redeemed)
                        <p class="mt-0.5 text-xs font-medium text-ink-muted">−{{ number_format((int) $visit->points_redeemed) }} {{ __('loop.pts') }}</p>
                    @endif
                </div>
                <span class="shrink-0 rounded-lg bg-mint-soft px-2.5 py-1 text-sm font-semibold text-mint-deep">+{{ number_format((int) $visit->points_earned) }} {{ __('loop.pts') }}</span>
            </div>
        @empty
            <div class="loop-panel p-8 text-center">
                <p class="font-display text-lg font-semibold">{{ __('loop.no_sales') }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-8">{{ $visits->links() }}</div>
</x-app-layout>
