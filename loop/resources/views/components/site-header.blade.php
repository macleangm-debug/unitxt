@props([
    'slim' => false,
])

<div class="border-b border-ink/5 bg-white/80 backdrop-blur-md">
    <div class="loop-shell flex items-center gap-3 py-3.5 sm:gap-4">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
            <x-loop-logo class="h-9 w-9 sm:h-10 sm:w-10" />
            <span class="font-display text-xl font-semibold tracking-tight sm:text-2xl">Loop</span>
        </a>

        @unless ($slim)
            @isset($actions)
                <nav class="flex min-w-0 flex-1 items-center gap-3 overflow-x-auto whitespace-nowrap [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:gap-4">
                    {{ $actions }}
                </nav>
            @else
                <div class="flex-1"></div>
            @endisset
        @else
            <div class="flex-1"></div>
        @endunless

        <div class="flex shrink-0 items-center gap-2">
            <form method="POST" action="{{ route('preference.country') }}">
                @csrf
                <select name="country" onchange="this.form.submit()" aria-label="{{ __('loop.country') }}" class="rounded-xl border border-ink/10 bg-white px-2.5 py-1.5 text-xs font-semibold text-ink">
                    @foreach (\App\Support\Countries::OPTIONS as $code => $meta)
                        <option value="{{ $code }}" @selected(session('preferred_country', 'TZ') === $code)>{{ $meta['flag'] }} {{ $code }}</option>
                    @endforeach
                </select>
            </form>

            <div class="flex rounded-xl border border-ink/10 bg-white p-0.5 text-xs font-semibold">
                <a href="{{ route('locale', 'en') }}" class="rounded-lg px-2.5 py-1.5 {{ app()->getLocale() === 'en' ? 'bg-ink text-white' : 'text-ink-muted' }}">EN</a>
                <a href="{{ route('locale', 'sw') }}" class="rounded-lg px-2.5 py-1.5 {{ app()->getLocale() === 'sw' ? 'bg-ink text-white' : 'text-ink-muted' }}">SW</a>
            </div>
        </div>
    </div>
</div>
