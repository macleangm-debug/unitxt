@php
    $confirm = $confirm ?? session('confirm');
    $confirmUrl = $confirm['url'] ?? '';
    $currentUrl = url()->current();
    $samePage = $confirmUrl !== '' && rtrim($confirmUrl, '/') === rtrim($currentUrl, '/');
@endphp

@if ($confirm)
    <template x-teleport="body">
    <div
        x-data="{ open: true }"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[80] flex items-center justify-center p-4"
        @keydown.escape.window="open=false"
    >
        <div class="absolute inset-0 bg-slate-900/55" @click="open=false"></div>
        <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-[0_24px_80px_rgba(15,23,42,0.28)]">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-900 text-lg font-bold text-white">✓</div>
            <p class="mt-5 text-center font-display text-2xl font-semibold tracking-tight text-slate-900">{{ $confirm['title'] }}</p>
            <p class="mt-2 text-center text-sm leading-relaxed text-slate-500">
                @if (! empty($confirm['body_html']))
                    {!! $confirm['body_html'] !!}
                @else
                    {{ $confirm['body'] }}
                @endif
            </p>
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
