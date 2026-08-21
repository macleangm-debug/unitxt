@props([
    'slim' => false,
    'overlay' => false,
])

@php
    $shellClass = $overlay
        ? 'absolute inset-x-0 top-0 z-30 border-b border-white/10 bg-ink/25 backdrop-blur-md'
        : 'sticky top-0 z-40 border-b border-ink/5 bg-white/80 backdrop-blur-md';
    $brandClass = $overlay ? 'text-white' : 'text-ink';
    $controlClass = $overlay
        ? 'rounded-lg border border-white/20 bg-white/10 px-2 py-1.5 text-xs font-semibold text-white'
        : 'rounded-lg border border-ink/10 bg-white px-2 py-1.5 text-xs font-semibold text-ink';
    $langWrap = $overlay
        ? 'flex rounded-lg border border-white/20 bg-white/10 p-0.5 text-xs font-semibold'
        : 'flex rounded-lg border border-ink/10 bg-white p-0.5 text-xs font-semibold';
    $langActive = $overlay ? 'bg-white text-ink' : 'bg-ink text-white';
    $langIdle = $overlay ? 'text-white/70' : 'text-ink-muted';
    $burgerClass = $overlay
        ? 'inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/20 bg-white/10 text-white md:hidden'
        : 'inline-flex h-10 w-10 items-center justify-center rounded-xl border border-ink/10 bg-white text-ink md:hidden';
    $actionsHtml = isset($actions) ? (string) $actions : null;
    $here = url()->full();
@endphp

<div
    class="{{ $shellClass }}"
    x-data="{ menuOpen: false }"
    @keydown.escape.window="menuOpen = false"
    x-effect="document.documentElement.classList.toggle('overflow-hidden', menuOpen)"
>
    <div class="loop-shell flex h-14 items-center gap-4 sm:h-16 sm:gap-6">
        <a href="/" class="flex shrink-0 items-center gap-2 {{ $brandClass }}">
            <x-loop-logo class="h-8 w-8 sm:h-9 sm:w-9" />
            <span class="font-display text-lg font-semibold tracking-tight sm:text-xl">Loop</span>
        </a>

        @unless ($slim)
            @if ($actionsHtml)
                <nav class="loop-top-nav hidden items-center gap-5 md:flex lg:gap-6">
                    {!! $actionsHtml !!}
                </nav>
            @endif
        @endunless

        <div class="ml-auto flex shrink-0 items-center gap-1.5 sm:gap-2">
            <form method="POST" action="{{ route('preference.country') }}">
                @csrf
                <x-sheet-select
                    name="country"
                    :options="collect(\App\Support\Countries::enabledOptions())->mapWithKeys(fn ($meta, $code) => [$code => ($meta['flag'].' '.$code)])->all()"
                    :value="session('preferred_country', 'TZ')"
                    :autosubmit="true"
                    :placeholder="__('loop.country')"
                    trigger-class="{{ $controlClass }} flex items-center justify-between gap-1"
                />
            </form>

            <div class="{{ $langWrap }}">
                <a href="{{ route('locale', ['locale' => 'en', 'return' => $here]) }}" class="rounded-md px-2 py-1 {{ app()->getLocale() === 'en' ? $langActive : $langIdle }}">EN</a>
                <a href="{{ route('locale', ['locale' => 'sw', 'return' => $here]) }}" class="rounded-md px-2 py-1 {{ app()->getLocale() === 'sw' ? $langActive : $langIdle }}">SW</a>
            </div>

            @unless ($slim)
                @if ($actionsHtml)
                    <button
                        type="button"
                        class="{{ $burgerClass }}"
                        @click="menuOpen = !menuOpen"
                        :aria-expanded="menuOpen.toString()"
                        aria-controls="loop-mobile-nav"
                        aria-label="{{ __('loop.menu') }}"
                    >
                        <svg x-show="!menuOpen" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                        <svg x-show="menuOpen" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>
                @endif
            @endunless
        </div>
    </div>

    @unless ($slim)
        @if ($actionsHtml)
            <template x-teleport="body">
                <div>
                    <div
                        x-show="menuOpen"
                        x-cloak
                        x-transition.opacity.duration.200ms
                        class="fixed inset-0 z-[60] bg-ink/45 md:hidden"
                        @click="menuOpen = false"
                        aria-hidden="true"
                    ></div>
                    <div
                        id="loop-mobile-nav"
                        x-show="menuOpen"
                        x-cloak
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="translate-x-full"
                        x-transition:enter-end="translate-x-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="translate-x-0"
                        x-transition:leave-end="translate-x-full"
                        class="fixed inset-y-0 right-0 z-[70] flex w-[min(100%,20rem)] flex-col bg-white shadow-2xl md:hidden"
                        role="dialog"
                        aria-modal="true"
                        aria-label="{{ __('loop.menu') }}"
                    >
                    <div class="flex items-center justify-between border-b border-ink/10 px-4 py-4">
                        <div>
                            <p class="font-display text-lg font-semibold text-ink">{{ __('loop.menu') }}</p>
                            <p class="mt-0.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-violet">Loop</p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-ink text-white"
                            @click="menuOpen = false"
                            aria-label="{{ __('loop.close') }}"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                            </svg>
                        </button>
                    </div>
                    <nav class="loop-mobile-actions flex flex-1 flex-col gap-2 overflow-y-auto p-4">
                        {!! $actionsHtml !!}
                    </nav>
                    </div>
                </div>
            </template>
        @endif
    @endunless
</div>
