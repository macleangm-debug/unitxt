@php
    $confirm = $confirm ?? \App\Support\Confirm::resolve(session('confirm'), session('status'), $errors ?? null);
    $confirmUrl = is_array($confirm) ? ($confirm['url'] ?? '') : '';
    $currentUrl = url()->current();
    $samePage = $confirmUrl !== '' && rtrim($confirmUrl, '/') === rtrim($currentUrl, '/');
    $ctaIsDone = strcasecmp((string) (is_array($confirm) ? ($confirm['cta'] ?? '') : ''), (string) __('loop.done')) === 0;
    $mustContinue = is_array($confirm) && ! empty($confirm['must_continue']);
    $tillInline = is_array($confirm) && request()->routeIs('till.index') && ! empty($confirm['loop_moment']);
    $isError = is_array($confirm) && ($confirm['kind'] ?? '') === 'error';
    $delayMs = is_array($confirm) ? (int) ($confirm['delay_ms'] ?? 0) : 0;
@endphp

@if ($confirm && ! $tillInline)
    <template x-teleport="body">
    <div
        x-data="{
            open: {{ $delayMs > 0 ? 'false' : 'true' }},
            delayMs: {{ $delayMs }},
            init() {
                if (this.delayMs <= 0) {
                    return;
                }
                const reveal = () => { this.open = true; };
                window.addEventListener('loop:confirm-ready', reveal, { once: true });
                setTimeout(reveal, this.delayMs);
            }
        }"
        x-show="open"
        x-cloak
        x-transition:enter="loop-sheet-enter-active"
        x-transition:enter-start="loop-sheet-enter-from"
        x-transition:enter-end="loop-sheet-enter-to"
        x-transition:leave="loop-sheet-leave-active"
        x-transition:leave-start="loop-sheet-leave-from"
        x-transition:leave-end="loop-sheet-leave-to"
        class="fixed inset-0 z-[80] flex items-center justify-center p-4"
        @if (! $mustContinue)
            @keydown.escape.window="open=false"
        @endif
    >
        <div class="absolute inset-0 bg-ink/60 backdrop-blur-sm" @if (! $mustContinue) @click="open=false" @endif></div>
        <div class="relative w-full max-w-md overflow-hidden rounded-[2rem] border border-ink/10 bg-white p-8 text-center shadow-[0_40px_100px_rgba(17,17,20,0.35)] sm:p-10">
            @if (!empty($confirm['celebrate']))
                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    @foreach (range(1,36) as $i)
                        <span class="absolute opacity-90"
                              style="left: {{ rand(2,96) }}%; top: -12%; width: {{ rand(6,12) }}px; height: {{ rand(8,16) }}px; border-radius: {{ $i % 3 === 0 ? '999px' : '2px' }}; background: {{ ['#2E7D32','#5B2EFF','#111114','#C8FF3D','#FF4F70'][$i % 5] }}; animation: loop-confetti {{ 1.6 + ($i % 5) * 0.18 }}s ease-in {{ $i * 0.04 }}s infinite;"></span>
                    @endforeach
                </div>
                <style>
                    @keyframes loop-confetti {
                        0% { transform: translate3d(0,-10%,0) rotate(0deg); opacity: 0; }
                        12% { opacity: 1; }
                        100% { transform: translate3d({{ rand(-40,40) }}px, 120vh, 0) rotate({{ rand(180,720) }}deg); opacity: 0; }
                    }
                </style>
            @endif
            <div class="relative">
                @if ($isError)
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-coral/15 text-3xl font-bold text-coral ring-1 ring-coral/30">!</div>
                @elseif (!empty($confirm['celebrate']))
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-mint-deep text-3xl font-bold text-white shadow-[0_12px_40px_rgba(27,94,32,0.35)]">✓</div>
                @else
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-mint-soft text-3xl font-bold text-mint-deep ring-1 ring-mint/30">✓</div>
                @endif
                <p class="mt-6 font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">{{ $confirm['title'] }}</p>
                @if (! empty($confirm['loop_moment']))
                    @php $m = $confirm['loop_moment']; @endphp
                    <p class="mt-2 text-base font-medium text-ink-muted">{{ $m['name'] }}</p>
                    @if (! empty($m['amount']))
                        <p class="mt-1 text-sm text-ink-muted">{{ $m['amount'] }}</p>
                    @endif
                    @if ((int) $m['earned'] > 0)
                        <p class="mt-4 font-display text-2xl font-semibold text-mint-deep">+{{ $m['earned'] }} {{ __('loop.pts') }}</p>
                    @endif
                    <p class="mt-3 font-display text-4xl font-semibold tabular-nums text-ink">
                        @if ((int) $m['from'] !== (int) $m['to'])
                            <x-count-up :value="$m['to']" :from="$m['from']" :earned="$m['earned']" />
                        @else
                            {{ number_format((int) $m['to']) }}
                        @endif
                    </p>
                    @if (! empty($m['unlock']))
                        <div class="mt-5 rounded-[1.25rem] bg-violet px-4 py-3 text-white">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-lime">{{ __('loop.offer_unlocked') }}</p>
                            <p class="mt-1 font-display text-xl font-semibold">{{ $m['unlock'] }}</p>
                            <p class="mt-1 text-sm text-white/75">
                                {{ ($m['unlock_when'] ?? '') === 'tomorrow' ? __('loop.available_tomorrow') : __('loop.use_it_now') }}
                            </p>
                        </div>
                    @endif
                    @if (! empty($confirm['game_play']))
                        <div class="mt-5 rounded-[1.25rem] bg-violet px-4 py-3 text-white">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-lime">{{ $confirm['game_play']['game'] }}</p>
                            <p class="mt-1 font-display text-xl font-semibold">{{ $confirm['game_play']['title'] }}</p>
                            <a href="{{ $confirm['game_play']['url'] }}" class="loop-btn-lime mt-3 inline-flex">{{ __('loop.game_let_them_play') }}</a>
                        </div>
                    @endif
                @elseif (! empty($confirm['body_html']) || filled($confirm['body'] ?? ''))
                    <p class="mt-3 text-base font-medium leading-relaxed text-ink-muted sm:text-lg">
                        @if (! empty($confirm['body_html']))
                            {!! $confirm['body_html'] !!}
                        @else
                            {{ $confirm['body'] }}
                        @endif
                    </p>
                @endif
                @if (! empty($confirm['steps']))
                    <div class="loop-confirm-steps">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ $confirm['how_title'] ?? __('loop.affiliate_status_check_how') }}</p>
                        <ol class="mt-3 space-y-2.5">
                            @foreach ($confirm['steps'] as $i => $step)
                                <li class="flex items-start gap-3 text-sm font-medium text-ink">
                                    <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-ink text-[11px] font-bold text-white">{{ $i + 1 }}</span>
                                    <span>{{ $step }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif
                @if ($samePage || ! empty($confirm['dismiss']))
                    <button type="button" class="loop-btn mt-8 inline-flex w-full justify-center text-base" @click="open=false">{{ $confirm['cta'] }}</button>
                @else
                    <a href="{{ $confirmUrl }}" class="loop-btn mt-8 inline-flex w-full justify-center text-base">{{ $confirm['cta'] }}</a>
                @endif
                @if (! empty($confirm['undo_url']))
                    <form method="POST" action="{{ $confirm['undo_url'] }}" class="mt-3">
                        @csrf
                        <button type="submit" class="w-full text-sm font-semibold text-ink-muted hover:text-ink">{{ __('loop.undo') }}</button>
                    </form>
                @endif
                @unless ($mustContinue || ($samePage && $ctaIsDone))
                    <button type="button" class="mt-4 text-sm font-semibold text-ink-muted hover:text-ink" @click="open=false">{{ __('loop.done') }}</button>
                @endunless
            </div>
        </div>
    </div>
    </template>
@endif
