@php
    $user = auth()->user();
    $tabs = \App\Support\MobileNav::tabs($user);
    $count = max(1, count($tabs));
@endphp

@if ($tabs !== [])
    <nav class="loop-bottom-nav md:hidden" aria-label="Loop">
        <div class="loop-bottom-nav__row" style="grid-template-columns: repeat({{ $count }}, minmax(0, 1fr))">
            @foreach ($tabs as $tab)
                <a
                    href="{{ $tab['href'] }}"
                    @click="$store.loopNav.go(@js($tab['href']), $event, { kind: 'tab' })"
                    @class(['loop-bottom-nav__item', 'is-active' => $tab['active']])
                    @if ($tab['active']) aria-current="page" @endif
                >
                    <span class="loop-bottom-nav__icon-wrap">
                        <x-loop-icon :name="$tab['icon']" class="loop-bottom-nav__icon" />
                        @if (($tab['badge'] ?? 0) > 0)
                            <span class="loop-bottom-nav__badge">{{ $tab['badge'] > 9 ? '9+' : $tab['badge'] }}</span>
                        @endif
                    </span>
                    <span class="loop-bottom-nav__label">{{ $tab['label'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>
@endif
