@php
    $confirm = $confirm ?? \App\Support\Confirm::resolve(session('confirm'), session('status'), $errors ?? null);
    $confirmUrl = is_array($confirm) ? ($confirm['url'] ?? '') : '';
    $currentUrl = url()->current();
    $samePage = $confirmUrl !== '' && rtrim($confirmUrl, '/') === rtrim($currentUrl, '/');
    $isError = is_array($confirm) && ($confirm['kind'] ?? '') === 'error';
@endphp

@if ($confirm)
    <template x-teleport="body">
    <div
        x-data="{ open: true }"
        x-show="open"
        x-cloak
        x-transition:enter="loop-sheet-enter-active"
        x-transition:enter-start="loop-sheet-enter-from"
        x-transition:enter-end="loop-sheet-enter-to"
        x-transition:leave="loop-sheet-leave-active"
        x-transition:leave-start="loop-sheet-leave-from"
        x-transition:leave-end="loop-sheet-leave-to"
        class="fixed inset-0 z-[80] flex items-center justify-center p-4"
        @keydown.escape.window="open=false"
    >
        <div class="loop-picker-backdrop" @click="open=false"></div>
        <div class="relative w-full max-w-md rounded-[1.75rem] border border-ink/10 bg-white p-8 shadow-[0_24px_80px_rgba(17,17,20,0.18)]">
            @if ($isError)
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-lg font-bold text-rose-600">!</div>
            @else
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-900 text-lg font-bold text-white">✓</div>
            @endif
            <p class="mt-5 text-center font-display text-2xl font-semibold tracking-tight text-slate-900">{{ $confirm['title'] }}</p>
            @if (! empty($confirm['body_html']) || filled($confirm['body'] ?? ''))
                <p class="mt-2 text-center text-sm leading-relaxed text-slate-500">
                    @if (! empty($confirm['body_html']))
                        {!! $confirm['body_html'] !!}
                    @else
                        {{ $confirm['body'] }}
                    @endif
                </p>
            @endif
            @if (! empty($confirm['steps']))
                <ol class="mt-5 space-y-2.5">
                    @foreach ($confirm['steps'] as $i => $step)
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[11px] font-bold text-slate-700">{{ $i + 1 }}</span>
                            <span>{{ $step }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif
            @if ($samePage || ! empty($confirm['dismiss']))
                <button type="button" class="admin-btn mt-7 w-full" @click="open=false">{{ $confirm['cta'] }}</button>
            @else
                <a href="{{ $confirmUrl }}" class="admin-btn mt-7 inline-flex w-full justify-center">{{ $confirm['cta'] }}</a>
            @endif
        </div>
    </div>
    </template>
@endif
