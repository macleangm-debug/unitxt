@props([
    'name' => 'phone',
    'dial',
    'value' => '',
    'required' => false,
    'autofocus' => false,
    'hiddenDialName' => null,
    'xDial' => null,
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
</div>
