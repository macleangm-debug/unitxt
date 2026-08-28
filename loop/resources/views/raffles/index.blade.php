<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex items-start gap-3">
                <x-back-icon :href="route('settings')" />
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.raffles') }}</p>
                    <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.raffles') }}</h1>
                    <p class="mt-1 text-ink-muted">{{ __('loop.raffles_event_blurb') }}</p>
                </div>
            </div>
            @if ($unlocked && empty($platformOff))
                <a href="{{ route('raffles.create') }}" class="loop-btn-mint shrink-0">{{ __('loop.create_raffle') }}</a>
            @endif
        </div>
    </x-slot>

    @if (! empty($platformOff))
        <section class="rounded-[2rem] border border-ink/10 bg-gradient-to-br from-ink to-ink-soft p-8 text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint">{{ __('loop.feature_paused') }}</p>
            <h2 class="mt-3 font-display text-3xl font-semibold">{{ __('loop.feature_paused_raffles_title') }}</h2>
            <p class="mt-3 max-w-xl text-sm text-white/70">{{ __('loop.feature_paused_raffles_body') }}</p>
        </section>
    @elseif (! empty($planLocked))
        <section class="rounded-[2rem] border border-ink/10 bg-gradient-to-br from-ink to-ink-soft p-8 text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint">{{ __('loop.raffle_plan_locked') }}</p>
            <h2 class="mt-3 font-display text-3xl font-semibold">{{ __('loop.raffle_plan_locked_title') }}</h2>
            <p class="mt-3 max-w-xl text-sm text-white/70">{{ __('loop.raffle_plan_locked_body') }}</p>
            <a href="{{ route('billing.show') }}" class="loop-btn-lime mt-6 inline-flex">{{ __('loop.upgrade_now') }}</a>
        </section>
    @elseif (! $unlocked)
        <section class="rounded-[2rem] border border-ink/10 bg-gradient-to-br from-ink to-ink-soft p-8 text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint">{{ __('loop.raffle_locked') }}</p>
            <h2 class="mt-3 font-display text-3xl font-semibold">{{ __('loop.raffle_locked_title') }}</h2>
            <p class="mt-3 max-w-xl text-sm text-white/70">{{ __('loop.raffle_locked_body', ['need' => $minMembers, 'have' => $memberCount]) }}</p>
            <div class="mt-6 h-2 overflow-hidden rounded-full bg-white/15">
                <div class="h-full rounded-full bg-mint" style="width: {{ min(100, round(($memberCount / max(1,$minMembers)) * 100)) }}%"></div>
            </div>
            <p class="mt-2 text-xs text-white/55">{{ number_format((int) $memberCount) }} / {{ number_format((int) $minMembers) }} {{ __('loop.members') }}</p>
        </section>
    @elseif ($reminders->isNotEmpty())
        <section class="mb-6 rounded-[1.5rem] border border-coral/25 bg-coral/10 px-5 py-4">
            <p class="font-semibold">{{ __('loop.raffle_reminders_title') }}</p>
            <ul class="mt-2 space-y-1 text-sm text-ink-muted">
                @foreach ($reminders as $r)
                    <li>{{ $r->name }} — {{ __('loop.draw_on') }} {{ $r->nextDrawDate()->format('d M Y') }}
                        @if ($r->canDrawNow())
                            <a href="{{ route('raffles.live', $r) }}" class="ml-2 font-semibold text-mint-deep">{{ __('loop.start_draw') }} →</a>
                        @else
                            <a href="{{ route('raffles.show', $r) }}" class="ml-2 font-semibold text-mint-deep">{{ __('loop.view_raffle') }} →</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($raffles->isNotEmpty())
        <div class="mt-6 space-y-4">
            @foreach ($raffles as $raffle)
                @php
                    $drawn = (int) ($raffle->drawn_count ?? 0);
                    $slotsLeft = max(0, (int) $raffle->winners_count - $drawn);
                    $canDraw = empty($platformOff) && $raffle->canDrawNow();
                @endphp
                <article class="rounded-[1.75rem] border border-ink/8 bg-white/90 p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="font-display text-2xl font-semibold">{{ $raffle->name }}</p>
                            <p class="mt-2 text-sm text-ink-muted">
                                {{ $raffle->prize_name }}
                                · {{ trans_choice('loop.winner_count_label', $raffle->winners_count, ['count' => $raffle->winners_count]) }}
                                · {{ __('loop.raffle_draw_day', ['day' => $raffle->nextDrawDate()->format('l')]) }}
                            </p>
                            <p class="mt-3 text-sm font-semibold text-ink">{{ __('loop.members_are_in', ['count' => $eligibleCount]) }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $raffle->status === 'live' ? 'bg-mint text-ink' : ($raffle->status === 'completed' ? 'bg-lime/40 text-ink' : 'bg-chalk text-ink-muted') }}">{{ __('loop.raffle_status_'.$raffle->status) }}</span>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="{{ route('raffles.show', $raffle) }}" class="loop-btn-ghost !py-2">{{ __('loop.view_raffle') }}</a>
                        @if ($canDraw)
                            <a href="{{ route('raffles.live', $raffle) }}" class="loop-btn-mint !py-2">{{ __('loop.start_draw') }}</a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @elseif (empty($platformOff) && $unlocked)
        <div class="loop-panel mt-6 p-8 text-center">
            <p class="font-display text-lg font-semibold">{{ __('loop.no_raffles_yet') }}</p>
            <a href="{{ route('raffles.create') }}" class="loop-btn-mint mt-4 inline-flex">{{ __('loop.create_raffle') }}</a>
        </div>
    @endif
</x-app-layout>
