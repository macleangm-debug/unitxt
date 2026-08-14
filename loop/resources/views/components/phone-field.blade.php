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
            class="flex shrink-0 items-center border-l border-ink/10 bg-chalk/60 px-3.5 text-violet transition hover:bg-violet-soft"
            @click="$dispatch('loop-open-qr-scan')"
            title="{{ __('loop.scan_member_qr') }}"
            aria-label="{{ __('loop.scan_member_qr') }}"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" d="M4 7V5a1 1 0 0 1 1-1h2M20 7V5a1 1 0 0 0-1-1h-2M4 17v2a1 1 0 0 0 1 1h2M20 17v2a1 1 0 0 1-1 1h-2" />
                <path stroke-linecap="round" d="M7 7h4v4H7V7zm6 0h4v2h-4V7zm0 4h4v2h-4v-2zM7 13h2v4H7v-4zm3 2h8v2h-8v-2z" />
            </svg>
        </button>
    @endif
</div>
