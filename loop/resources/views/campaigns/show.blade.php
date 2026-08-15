<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-3" x-data="{ confirmToggle: false }">
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.campaigns') }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <h1 class="font-display text-2xl font-semibold tracking-tight sm:text-3xl">{{ $campaign->displayName() }}</h1>
                    @if ($campaign->isCurrentlyActive())
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-mint-deep px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-white">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white"></span>
                            {{ __('loop.live') }}
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-ink/10 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-ink-muted">
                            {{ __('loop.paused') }}
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-ink-muted">{{ $campaign->scheduleLabel() }}</p>
            </div>
            <x-settings-back :href="route('campaigns.index')" :label="__('loop.back')" />
        </div>
    </x-slot>

    <div class="mt-4 flex flex-wrap gap-2" x-data="{ confirmToggle: false }">
        <button type="button" class="loop-btn-ghost !py-2.5" @click="confirmToggle = true">
            {{ $campaign->is_active ? __('loop.pause_campaign') : __('loop.resume_campaign') }}
        </button>
        <a href="{{ route('campaigns.edit', $campaign) }}" class="loop-btn-mint !py-2.5">{{ __('loop.edit') }}</a>

        <div
            x-show="confirmToggle"
            x-cloak
            class="fixed inset-0 z-[80] flex items-end justify-center bg-ink/50 p-4 sm:items-center"
            @keydown.escape.window="confirmToggle = false"
        >
            <div class="absolute inset-0" @click="confirmToggle = false"></div>
            <div class="relative w-full max-w-md rounded-[1.5rem] bg-white p-6 shadow-2xl" @click.stop>
                <p class="font-display text-xl font-semibold">
                    {{ $campaign->is_active ? __('loop.pause_campaign_confirm_title') : __('loop.resume_campaign_confirm_title') }}
                </p>
                <p class="mt-2 text-sm text-ink-muted">
                    {{ $campaign->is_active ? __('loop.pause_campaign_confirm_body') : __('loop.resume_campaign_confirm_body') }}
                </p>
                <div class="mt-5 flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="confirmToggle = false">{{ __('loop.cancel') }}</button>
                    <form method="POST" action="{{ route('campaigns.toggle', $campaign) }}" class="flex-1">
                        @csrf
                        <button class="loop-btn w-full {{ $campaign->is_active ? '!bg-coral' : '' }}">
                            {{ $campaign->is_active ? __('loop.pause_campaign') : __('loop.resume_campaign') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if (in_array($campaign->type, ['earn', 'product_push'], true) && $campaign->spend_step && $campaign->points_per_step)
        <div class="mt-6 rounded-[1.5rem] border border-ink/10 bg-white px-5 py-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ __('loop.customer_gets') }}</p>
            <p class="mt-2 font-display text-2xl font-bold text-ink">
                {{ $campaign->points_per_step }} {{ __('loop.pts') }}
                <span class="text-ink-muted">/</span>
                {{ $business->currency }} {{ number_format($campaign->spend_step) }}
            </p>
        </div>
    @elseif ($campaign->ruleSummary($business->currency))
        <p class="mt-6 text-sm font-medium text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
    @endif

    <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-ink/10 bg-white p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.today') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $stats['today_visits'] }}</p>
        </div>
        <div class="rounded-2xl border border-ink/10 bg-white p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.sales') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $stats['total_visits'] }}</p>
        </div>
        <div class="rounded-2xl border border-ink/10 bg-white p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.spend') }}</p>
            <p class="mt-2 font-display text-xl font-semibold">{{ number_format($stats['total_spend'], 0) }}</p>
            <p class="mt-0.5 text-[10px] text-ink-muted">{{ $business->currency }}</p>
        </div>
    </div>

    <section class="mt-8">
        <div class="mb-3 flex items-end justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.recent_sales') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.campaign_sales_blurb') }}</p>
            </div>
            <a href="{{ route('transactions.index') }}" class="shrink-0 text-sm font-semibold text-mint-deep">{{ __('loop.view_all') }}</a>
        </div>

        <div class="overflow-hidden rounded-[1.5rem] border border-ink/8 bg-white/90">
            <div class="max-h-[28rem] space-y-0 overflow-y-auto overscroll-contain divide-y divide-ink/8">
                @forelse ($recentVisits as $visit)
                    <div class="flex items-center justify-between gap-4 px-4 py-3.5">
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $visit->customer->name }}</p>
                            <p class="mt-0.5 text-xs text-ink-muted">{{ $visit->created_at->format('d M · H:i') }} · +{{ $visit->points_earned }} pts</p>
                        </div>
                        <p class="shrink-0 font-display text-lg font-semibold">
                            {{ $business->currency }} {{ number_format($visit->amount_spent, 0) }}
                        </p>
                    </div>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-ink-muted">{{ __('loop.no_sales') }}</p>
                @endforelse
            </div>
            @if ($recentVisits->hasPages())
                <div class="border-t border-ink/8 px-4 py-3">{{ $recentVisits->links() }}</div>
            @endif
        </div>
    </section>
</x-app-layout>
