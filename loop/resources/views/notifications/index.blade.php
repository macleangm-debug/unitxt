<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.notifications') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.notifications_title') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.notifications_blurb') }}</p>
            </div>
            @if ($notifications->whereNull('read_at')->isNotEmpty())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="loop-btn-ghost !py-2.5">{{ __('loop.mark_all_read') }}</button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto max-w-2xl space-y-3">
        @forelse ($notifications as $notification)
            <form method="POST" action="{{ route('notifications.read', $notification) }}">
                @csrf
                <button type="submit" class="w-full rounded-[1.5rem] border px-5 py-4 text-left transition hover:border-ink/20 {{ $notification->isUnread() ? 'border-mint/40 bg-mint-soft/30' : 'border-ink/10 bg-white' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-display text-lg font-semibold">{{ $notification->title() }}</p>
                            <p class="mt-1 text-sm text-ink-muted">{{ $notification->body() }}</p>
                            @if ($notification->cta())
                                <p class="mt-3 text-sm font-semibold text-mint-deep">{{ $notification->cta() }} →</p>
                            @endif
                        </div>
                        @if ($notification->isUnread())
                            <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-mint-deep"></span>
                        @endif
                    </div>
                    <p class="mt-3 text-xs text-ink-muted">{{ $notification->created_at?->format('d M Y · H:i') }}</p>
                </button>
            </form>
        @empty
            <p class="rounded-[1.5rem] border border-dashed border-ink/15 px-5 py-10 text-center text-sm text-ink-muted">{{ __('loop.no_notifications') }}</p>
        @endforelse
    </div>
</x-app-layout>
