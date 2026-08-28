<x-app-layout>
    <x-slot name="header">
        <h1 class="loop-page-title font-display text-3xl font-semibold">{{ __('loop.nav_more') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.more_blurb') }}</p>
    </x-slot>

    <div class="space-y-8">
        @foreach ($groups as $group)
            <section>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ $group['title'] }}</p>
                <div class="mt-3 divide-y divide-ink/8 overflow-hidden rounded-[1.5rem] border border-ink/8 bg-white/90">
                    @foreach ($group['items'] as $item)
                        <a href="{{ $item['href'] }}" class="loop-more-row" @click="$store.loopNav.go(@js($item['href']), $event, { kind: 'push' })">
                            <span class="loop-more-row__icon">
                                <x-loop-icon :name="$item['icon']" class="h-5 w-5" />
                            </span>
                            <span class="min-w-0 flex-1 font-semibold">{{ $item['label'] }}</span>
                            <span class="text-ink-muted" aria-hidden="true">→</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach

        <section>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.language') }}</p>
            <div class="mt-3 flex gap-2">
                <a href="{{ route('locale', ['locale' => 'en', 'return' => url()->full()]) }}" class="loop-btn-ghost flex-1 {{ app()->getLocale() === 'en' ? '!bg-ink text-white' : '' }}" @click.prevent="window.dispatchEvent(new Event('loop:locale-changing')); window.location.href = @js(url('/locale/en')) + '?return=' + encodeURIComponent(window.location.href)">EN</a>
                <a href="{{ route('locale', ['locale' => 'sw', 'return' => url()->full()]) }}" class="loop-btn-ghost flex-1 {{ app()->getLocale() === 'sw' ? '!bg-ink text-white' : '' }}" @click.prevent="window.dispatchEvent(new Event('loop:locale-changing')); window.location.href = @js(url('/locale/sw')) + '?return=' + encodeURIComponent(window.location.href)">SW</a>
            </div>
        </section>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="loop-btn-ghost w-full">{{ __('loop.log_out') }}</button>
        </form>
    </div>
</x-app-layout>
