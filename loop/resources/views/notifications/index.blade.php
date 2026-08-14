@php
    $audienceLabel = match ($audience ?? 'member') {
        'business' => __('loop.notif_audience_business'),
        'affiliate' => __('loop.notif_audience_affiliate'),
        'admin' => __('loop.admin'),
        default => __('loop.notif_audience_member'),
    };
    $grouped = $notifications->getCollection()->groupBy(fn ($n) => $n->created_at?->toDateString() ?? 'unknown');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-violet">{{ $audienceLabel }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight">{{ __('loop.notifications_title') }}</h1>
                <p class="mt-1 text-sm text-ink-muted">
                    @if ($unreadCount > 0)
                        {{ __('loop.notifications_unread_count', ['count' => $unreadCount]) }}
                    @else
                        {{ __('loop.notifications_blurb') }}
                    @endif
                </p>
            </div>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="loop-btn-ghost !py-2.5">{{ __('loop.mark_all_read') }}</button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto max-w-2xl">
        @if (! empty($insightBanners))
            <section class="mb-8">
                <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.performance') }}</p>
                <div class="overflow-hidden rounded-[1.5rem] border border-ink/8 bg-white/80 shadow-[0_10px_30px_rgba(17,17,20,0.04)] backdrop-blur-xl">
                    @foreach ($insightBanners as $banner)
                        <a
                            href="{{ $banner['url'] }}"
                            class="group flex w-full items-start gap-4 border-b border-ink/5 px-5 py-4 text-left last:border-b-0 transition hover:bg-chalk/80"
                            @click="$store.loopNav.go(@js($banner['url']), $event, { kind: 'push' })"
                        >
                            <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-violet" aria-hidden="true"></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="font-display text-base font-semibold leading-snug text-ink sm:text-lg">{{ $banner['title'] }}</p>
                                </div>
                                <p class="mt-1 text-sm leading-relaxed text-ink-muted">{{ $banner['body'] }}</p>
                                @if (! empty($banner['cta']))
                                    <p class="mt-2 text-sm font-semibold text-violet group-hover:text-ink">{{ $banner['cta'] }} →</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @forelse ($grouped as $date => $dayItems)
            <section class="mb-8">
                <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-muted">
                    @if ($date === now()->toDateString())
                        {{ __('loop.today') }}
                    @elseif ($date === now()->subDay()->toDateString())
                        {{ __('loop.yesterday') }}
                    @else
                        {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('d M Y') }}
                    @endif
                </p>

                <div class="overflow-hidden rounded-[1.5rem] border border-ink/8 bg-white/80 shadow-[0_10px_30px_rgba(17,17,20,0.04)] backdrop-blur-xl">
                    @foreach ($dayItems as $notification)
                        @php
                            $tone = $notification->tone ?? 'mint';
                            $toneDot = match ($tone) {
                                'coral' => 'bg-coral',
                                'ink' => 'bg-ink',
                                'violet' => 'bg-violet',
                                default => 'bg-mint-deep',
                            };
                        @endphp
                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                            @csrf
                            <button
                                type="submit"
                                class="group flex w-full items-start gap-4 border-b border-ink/5 px-5 py-4 text-left last:border-b-0 transition hover:bg-chalk/80 {{ $notification->isUnread() ? 'bg-mint-soft/20' : '' }}"
                            >
                                <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $notification->isUnread() ? $toneDot : 'bg-ink/15' }}" aria-hidden="true"></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="font-display text-base font-semibold leading-snug text-ink sm:text-lg">{{ $notification->title() }}</p>
                                        <time class="shrink-0 text-[11px] tabular-nums text-ink-muted">{{ $notification->created_at?->format('H:i') }}</time>
                                    </div>
                                    <p class="mt-1 text-sm leading-relaxed text-ink-muted">{{ $notification->body() }}</p>
                                    @if ($notification->cta())
                                        <p class="mt-2 text-sm font-semibold text-violet group-hover:text-ink">{{ $notification->cta() }} →</p>
                                    @endif
                                </div>
                            </button>
                        </form>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-[1.5rem] border border-dashed border-ink/15 px-6 py-14 text-center">
                <p class="font-display text-xl font-semibold text-ink">{{ __('loop.no_notifications') }}</p>
                <p class="mt-2 text-sm text-ink-muted">{{ __('loop.notifications_empty_hint') }}</p>
            </div>
        @endforelse

        @if ($notifications->hasPages())
            <div class="mt-2">{{ $notifications->links() }}</div>
        @endif
    </div>
</x-app-layout>
