<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="font-display text-2xl font-semibold sm:text-3xl">{{ __('loop.campaigns_and_offers') }}</h1>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.campaigns_and_offers_blurb') }}</p>
            </div>
            <x-settings-back />
        </div>
    </x-slot>

    <section class="mb-12">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-2xl font-semibold">{{ __('loop.how_they_earn') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.how_they_earn_body') }}</p>
            </div>
            <a href="{{ route('campaigns.create') }}" class="loop-btn-mint shrink-0 !py-2.5">{{ __('loop.new_campaign') }}</a>
        </div>
        <p class="mb-4 rounded-2xl border border-ink/8 bg-chalk/50 px-4 py-3 text-sm text-ink-muted">{{ __('loop.campaigns_vs_offers_bridge') }}</p>

        <div class="space-y-3">
            @forelse ($campaigns as $campaign)
                <a href="{{ route('campaigns.show', $campaign) }}" class="loop-panel flex items-center justify-between gap-4 p-5 transition hover:-translate-y-0.5 hover:bg-white">
                    <div class="min-w-0">
                        <p class="font-display text-lg font-semibold">{{ $campaign->displayName() }}</p>
                        <p class="mt-0.5 truncate text-sm text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        @if ($campaign->isCurrentlyActive())
                            <span class="rounded-lg bg-mint-deep px-2.5 py-1 text-xs font-semibold text-white">{{ __('loop.live') }}</span>
                        @endif
                        <span class="text-ink-muted">→</span>
                    </div>
                </a>
            @empty
                <x-empty-state
                    :title="__('loop.no_campaigns_yet')"
                    :blurb="__('loop.how_they_earn_body')"
                    :cta="__('loop.new_campaign')"
                    :url="route('campaigns.create')"
                />
            @endforelse
        </div>
    </section>

    <section id="offers">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-2xl font-semibold">{{ __('loop.what_they_choose') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.what_they_choose_body') }}</p>
            </div>
            <a href="{{ route('rewards.create') }}" class="loop-btn-mint shrink-0 !py-2.5">{{ __('loop.add_offer') }}</a>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            @forelse ($rewards as $reward)
                <a href="{{ route('rewards.show', $reward) }}" class="loop-panel block p-5 transition hover:-translate-y-0.5 hover:bg-white">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-display text-lg font-semibold">{{ $reward->name }}</p>
                            <p class="mt-1 text-sm text-ink-muted">{{ $reward->label() }}</p>
                            @if ($reward->product_name)
                                <p class="mt-2 text-sm">{{ __('loop.product') }}: <span class="font-semibold">{{ $reward->product_name }}</span></p>
                            @endif
                            @if ($reward->stock === 0)
                                <p class="mt-2 text-xs font-semibold text-coral">{{ __('loop.offer_finished') }}</p>
                            @elseif ($reward->stock !== null)
                                <p class="mt-2 text-xs text-ink-muted">{{ __('loop.offer_remaining_count', ['count' => $reward->stock]) }}</p>
                            @endif
                        </div>
                        <span class="shrink-0 rounded-xl bg-ink px-3 py-1.5 text-sm font-semibold text-white">{{ $reward->points_cost }} pts</span>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-mint-deep">{{ __('loop.view_stats') }} →</p>
                </a>
            @empty
                <div class="loop-panel p-8 text-center sm:col-span-2">
                    <p class="font-display text-lg font-semibold">{{ __('loop.no_offers_yet_hint') }}</p>
                    <a href="{{ route('rewards.create') }}" class="loop-btn-mint mt-5 inline-flex">{{ __('loop.add_offer') }}</a>
                </div>
            @endforelse
        </div>
    </section>
</x-app-layout>
