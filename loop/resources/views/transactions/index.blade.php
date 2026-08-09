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
            <div class="flex items-center justify-between gap-4 rounded-[1.5rem] border border-ink/8 bg-white/90 px-5 py-4">
                <div class="min-w-0">
                    <p class="font-semibold">{{ $visit->customer?->name ?? __('loop.customer') }}</p>
                    <p class="mt-1 text-xs text-ink-muted">
                        {{ $visit->shop?->name }} · {{ $visit->created_at->format('d M Y · H:i') }}
                        @if ($visit->channel === 'phone_order')
                            · {{ __('loop.phone_order') }}
                        @elseif ($visit->channel)
                            · {{ __('loop.in_store') }}
                        @endif
                    </p>
                    <p class="mt-1 text-xs font-medium text-mint-deep">
                        +{{ $visit->points_earned }} pts
                        @if ($visit->points_redeemed)
                            · −{{ $visit->points_redeemed }} pts
                        @endif
                    </p>
                </div>
                <p class="shrink-0 font-display text-2xl font-semibold tracking-tight">
                    @if ($showAmounts ?? true)
                        {{ $business->currency }} {{ number_format($visit->amount_spent, 0) }}
                    @else
                        <span class="text-base text-ink-muted">{{ __('loop.amount_hidden') }}</span>
                    @endif
                </p>
            </div>
        @empty
            <div class="loop-panel p-8 text-center text-sm text-ink-muted">{{ __('loop.no_sales') }}</div>
        @endforelse
    </div>

    <div class="mt-8">{{ $visits->links() }}</div>
</x-app-layout>
