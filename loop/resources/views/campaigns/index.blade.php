<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ __('loop.campaigns_and_offers') }}</h1>
                <p class="mt-1 max-w-2xl text-ink-muted">{{ __('loop.campaigns_and_offers_blurb') }}</p>
            </div>
        </div>
    </x-slot>

    <section class="mb-10">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.how_they_earn') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.how_they_earn_body') }}</p>
            </div>
            <a href="{{ route('campaigns.create') }}" class="loop-btn-mint !py-2">{{ __('loop.new_campaign') }}</a>
        </div>

        <div class="space-y-3">
            @forelse ($campaigns as $campaign)
                <a href="{{ route('campaigns.show', $campaign) }}" class="loop-panel flex items-center justify-between gap-4 p-5 transition hover:-translate-y-0.5 hover:bg-white">
                    <div class="min-w-0">
                        <p class="font-display text-lg font-semibold">{{ $campaign->displayName() }}</p>
                        <p class="mt-0.5 truncate text-sm text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
                    </div>
                    @if ($campaign->isCurrentlyActive())
                        <span class="shrink-0 rounded-lg bg-mint-soft px-2.5 py-1 text-xs font-semibold text-ink">{{ __('loop.live') }}</span>
                    @endif
                </a>
            @empty
                <div class="loop-panel p-6 text-center">
                    <p class="text-sm text-ink-muted">{{ __('loop.no_campaigns_yet') }}</p>
                    <a href="{{ route('campaigns.create') }}" class="loop-btn-mint mt-4 inline-flex">{{ __('loop.new_campaign') }}</a>
                </div>
            @endforelse
        </div>
    </section>

    <section id="offers">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.what_they_choose') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.what_they_choose_body') }}</p>
            </div>
            <a href="{{ route('rewards.create') }}" class="loop-btn-ghost !py-2">{{ __('loop.add_offer') }}</a>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            @forelse ($rewards as $reward)
                <div class="loop-panel p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-display text-lg font-semibold">{{ $reward->name }}</p>
                            <p class="mt-1 text-sm text-ink-muted">{{ $reward->label() }}</p>
                            @if ($reward->product_name)
                                <p class="mt-2 text-sm">{{ __('loop.product') }}: <span class="font-semibold">{{ $reward->product_name }}</span>
                                    @if ($reward->product_sku) <span class="text-ink-muted">({{ $reward->product_sku }})</span> @endif
                                </p>
                            @endif
                        </div>
                        <span class="shrink-0 rounded-xl bg-ink px-3 py-1.5 text-sm font-semibold text-mint">{{ $reward->points_cost }} pts</span>
                    </div>
                    @if ($reward->stock !== null)
                        <p class="mt-3 text-xs text-ink-muted">{{ __('loop.stock') }}: {{ $reward->stock }}</p>
                    @endif
                </div>
            @empty
                <div class="loop-panel p-6 text-sm text-ink-muted sm:col-span-2">
                    {{ __('loop.no_offers_yet_hint') }}
                    <a href="{{ route('rewards.create') }}" class="mt-2 inline-flex font-semibold text-mint-deep">{{ __('loop.add_offer') }} →</a>
                </div>
            @endforelse
        </div>
    </section>
</x-app-layout>
