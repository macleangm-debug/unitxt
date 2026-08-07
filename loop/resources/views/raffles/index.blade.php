<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">Loop</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.raffles') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.raffles_blurb') }}</p>
            </div>
            @if ($unlocked)
                <a href="{{ route('raffles.create') }}" class="loop-btn-mint">{{ __('loop.create_raffle') }}</a>
            @endif
        </div>
    </x-slot>

    @if (! $unlocked)
        <section class="rounded-[2rem] border border-ink/10 bg-gradient-to-br from-ink to-ink-soft p-8 text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint">{{ __('loop.raffle_locked') }}</p>
            <h2 class="mt-3 font-display text-3xl font-semibold">{{ __('loop.raffle_locked_title') }}</h2>
            <p class="mt-3 max-w-xl text-sm text-white/70">{{ __('loop.raffle_locked_body', ['need' => $minMembers, 'have' => $memberCount]) }}</p>
            <div class="mt-6 h-2 overflow-hidden rounded-full bg-white/15">
                <div class="h-full rounded-full bg-mint" style="width: {{ min(100, round(($memberCount / max(1,$minMembers)) * 100)) }}%"></div>
            </div>
            <p class="mt-2 text-xs text-white/55">{{ $memberCount }} / {{ $minMembers }} {{ __('loop.members') }}</p>
        </section>
    @else
        @if ($reminders->isNotEmpty())
            <section class="mb-6 rounded-[1.5rem] border border-coral/25 bg-coral/10 px-5 py-4">
                <p class="font-semibold">{{ __('loop.raffle_reminders_title') }}</p>
                <ul class="mt-2 space-y-1 text-sm text-ink-muted">
                    @foreach ($reminders as $r)
                        <li>{{ $r->name }} — {{ __('loop.draw_on') }} {{ $r->draw_at->format('d M Y') }}
                            <a href="{{ route('raffles.live', $r) }}" class="ml-2 font-semibold text-mint-deep">{{ __('loop.go_live') }} →</a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <div class="space-y-3">
            @forelse ($raffles as $raffle)
                <a href="{{ route('raffles.show', $raffle) }}" class="flex flex-wrap items-center justify-between gap-4 rounded-[1.5rem] border border-ink/8 bg-white/90 px-5 py-4 transition hover:-translate-y-0.5">
                    <div>
                        <p class="font-display text-lg font-semibold">{{ $raffle->name }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ $raffle->prize_name }} · {{ __('loop.winners_count', ['count' => $raffle->winners_count]) }} · {{ __('loop.freq_'.$raffle->frequency) }}</p>
                    </div>
                    <div class="text-right">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $raffle->status === 'live' ? 'bg-mint text-ink' : 'bg-chalk text-ink-muted' }}">{{ __('loop.raffle_status_'.$raffle->status) }}</span>
                        <p class="mt-2 text-xs text-ink-muted">{{ $raffle->draw_at->format('d M Y') }}</p>
                    </div>
                </a>
            @empty
                <div class="loop-panel p-8 text-center">
                    <p class="font-display text-lg font-semibold">{{ __('loop.no_raffles_yet') }}</p>
                    <a href="{{ route('raffles.create') }}" class="loop-btn-mint mt-4 inline-flex">{{ __('loop.create_raffle') }}</a>
                </div>
            @endforelse
        </div>
    @endif
</x-app-layout>
