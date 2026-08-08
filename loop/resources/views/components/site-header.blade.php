@props([
    'slim' => false,
    'overlay' => false,
])

@php
    $shellClass = $overlay
        ? 'absolute inset-x-0 top-0 z-30 border-b border-white/10 bg-ink/25 backdrop-blur-md'
        : 'border-b border-ink/5 bg-white/80 backdrop-blur-md';
    $brandClass = $overlay ? 'text-white' : 'text-ink';
    $linkClass = $overlay
        ? 'text-sm font-semibold text-white/80 hover:text-white'
        : 'text-sm font-semibold text-ink-muted hover:text-ink';
    $controlClass = $overlay
        ? 'rounded-lg border border-white/20 bg-white/10 px-2 py-1.5 text-xs font-semibold text-white'
        : 'rounded-lg border border-ink/10 bg-white px-2 py-1.5 text-xs font-semibold text-ink';
    $langWrap = $overlay
        ? 'flex rounded-lg border border-white/20 bg-white/10 p-0.5 text-xs font-semibold'
        : 'flex rounded-lg border border-ink/10 bg-white p-0.5 text-xs font-semibold';
    $langActive = $overlay ? 'bg-white text-ink' : 'bg-ink text-white';
    $langIdle = $overlay ? 'text-white/70' : 'text-ink-muted';
@endphp

<div class="{{ $shellClass }}">
    <div class="loop-shell flex h-14 items-center gap-3 sm:h-16 sm:gap-4">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2 {{ $brandClass }}">
            <x-loop-logo class="h-8 w-8 sm:h-9 sm:w-9" />
            <span class="font-display text-lg font-semibold tracking-tight sm:text-xl">Loop</span>
        </a>

        @unless ($slim)
            @isset($actions)
                {{-- Desktop: nav beside logo. Mobile: hide to avoid cramped truncation (CTAs live in page body). --}}
                <nav class="hidden min-w-0 flex-1 items-center gap-4 md:flex">
                    {{ $actions }}
                </nav>
                <div class="flex-1 md:hidden"></div>
            @else
                <div class="flex-1"></div>
            @endisset
        @else
            <div class="flex-1"></div>
        @endunless

        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
            <form method="POST" action="{{ route('preference.country') }}">
                @csrf
                <select name="country" onchange="this.form.submit()" aria-label="{{ __('loop.country') }}" class="{{ $controlClass }}">
                    @foreach (\App\Support\Countries::OPTIONS as $code => $meta)
                        <option value="{{ $code }}" @selected(session('preferred_country', 'TZ') === $code)>{{ $meta['flag'] }} {{ $code }}</option>
                    @endforeach
                </select>
            </form>

            <div class="{{ $langWrap }}">
                <a href="{{ route('locale', 'en') }}" class="rounded-md px-2 py-1 {{ app()->getLocale() === 'en' ? $langActive : $langIdle }}">EN</a>
                <a href="{{ route('locale', 'sw') }}" class="rounded-md px-2 py-1 {{ app()->getLocale() === 'sw' ? $langActive : $langIdle }}">SW</a>
            </div>
        </div>
    </div>
</div>
