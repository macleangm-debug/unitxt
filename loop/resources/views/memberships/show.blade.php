<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ $business->name }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.wallet_show_blurb') }}</p>
            </div>
            <p class="font-display text-4xl font-semibold">{{ $membership->points_balance }} <span class="text-lg text-ink-muted">pts</span></p>
        </div>
    </x-slot>

    <section class="loop-panel mb-6 p-6">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.offers_at_this_shop') }}</h2>
        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.offers_at_this_shop_body') }}</p>
        <div class="mt-4 space-y-3">
            @forelse ($rewards as $reward)
                @php
                    $canRedeem = $membership->points_balance >= $reward->points_cost;
                    $needed = max(0, $reward->points_cost - $membership->points_balance);
                @endphp
                <div class="flex justify-between gap-3 rounded-xl bg-chalk px-4 py-3 {{ $canRedeem ? 'ring-1 ring-mint/40' : '' }}">
                    <div>
                        <p class="font-semibold">{{ $reward->name }}</p>
                        <p class="text-sm text-ink-muted">{{ $reward->label() }}</p>
                    </div>
                    <span class="shrink-0 text-right text-sm font-semibold {{ $canRedeem ? 'text-mint-deep' : 'text-ink-muted' }}">
                        {{ $reward->points_cost }} pts
                        @if ($canRedeem)
                            <span class="mt-1 block text-xs">{{ __('loop.ready') }}</span>
                        @else
                            <span class="mt-1 block text-xs font-normal">{{ __('loop.need_more_points', ['points' => $needed]) }}</span>
                        @endif
                    </span>
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.no_offers_yet') }}</p>
            @endforelse
        </div>
    </section>

    <section class="loop-panel p-6">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.history') }}</h2>
        <div class="mt-4 space-y-3">
            @forelse ($transactions as $tx)
                <div class="flex justify-between rounded-xl bg-chalk px-4 py-3">
                    <div>
                        <p class="font-medium">{{ $tx->description }}</p>
                        <p class="text-xs text-ink-muted">{{ $tx->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="font-semibold {{ $tx->points >= 0 ? 'text-mint-deep' : 'text-coral' }}">{{ $tx->points >= 0 ? '+' : '' }}{{ $tx->points }}</span>
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.no_history_yet') }}</p>
            @endforelse
        </div>
    </section>
</x-app-layout>
