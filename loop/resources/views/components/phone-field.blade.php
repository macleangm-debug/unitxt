@props([
    'name' => 'phone',
    'dial',
    'value' => '',
    'required' => false,
    'autofocus' => false,
    'hiddenDialName' => null,
    'xDial' => null,
    'scanable' => false,
])

<div class="mt-1 flex overflow-hidden rounded-2xl border border-ink/10 bg-white shadow-sm focus-within:border-violet focus-within:ring-1 focus-within:ring-violet">
    <span class="flex shrink-0 items-center border-r border-ink/10 bg-chalk px-3.5 text-sm font-semibold tabular-nums text-ink">
        @if ($xDial)
            <span x-text="{{ $xDial }}">{{ $dial }}</span>
        @else
            {{ $dial }}
        @endif
    </span>
    @if ($hiddenDialName)
        <input type="hidden" name="{{ $hiddenDialName }}" value="{{ $dial }}" @if($xDial) :value="{{ $xDial }}" @endif>
    @endif
    <input
        name="{{ $name }}"
        value="{{ $value }}"
        class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-base tracking-wide focus:ring-0"
        placeholder="7xxxxxxxx"
        inputmode="tel"
        autocomplete="tel-national"
        @if($required) required @endif
        @if($autofocus) autofocus @endif
        {{ $attributes->except(['class']) }}
    >
    @if ($scanable)
        <button
            type="button"
            class="flex shrink-0 items-center border-l border-ink/10 bg-chalk/70 px-2.5 text-violet transition hover:bg-violet-soft"
            @click="$dispatch('loop-open-qr-scan')"
            title="{{ __('loop.scan_member_qr') }}"
            aria-label="{{ __('loop.scan_member_qr') }}"
        >
            <svg class="h-7 w-7" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                <rect x="3" y="10" width="26" height="18" rx="5" fill="#5B2EFF"/>
                <path d="M11 10 12.5 6.9A2 2 0 0 1 14.3 5.8h3.4a2 2 0 0 1 1.8 1.1L21 10" fill="#5B2EFF"/>
                <circle cx="14.4" cy="19" r="5.1" stroke="#C8FF3D" stroke-width="2"/>
                <circle cx="17.6" cy="19" r="5.1" stroke="#ffffff" stroke-width="2"/>
                <circle cx="14.4" cy="19" r="1.5" fill="#C8FF3D"/>
            </svg>
        </button>
    @endif
</div>
