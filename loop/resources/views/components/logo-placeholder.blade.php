@props([
    'name' => 'logo',
    'preview' => null,
    'required' => false,
    'hint' => null,
])

<div
    class="flex flex-col items-center"
    x-data="logoPlaceholder({
        preview: @js($preview),
        required: @js((bool) $required),
    })"
>
    <button
        type="button"
        x-ref="frame"
        class="loop-logo-ph relative overflow-hidden rounded-[1.75rem] bg-ink ring-4 ring-violet/25 transition hover:ring-lime/40 focus:outline-none focus:ring-lime/50"
        @click="if (!preview) openPicker()"
        @pointerdown="onDown($event)"
        @pointermove="onMove($event)"
        @pointerup="onUp()"
        @pointercancel="onUp()"
        :aria-label="preview ? @js(__('loop.drag_to_position')) : @js(__('loop.add_logo'))"
    >
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(91,46,255,0.35),transparent_55%),radial-gradient(circle_at_80%_80%,rgba(200,255,61,0.18),transparent_45%)]"></div>
        <img
            x-show="preview"
            x-cloak
            :src="preview"
            alt=""
            class="loop-logo-ph__img absolute inset-0 h-full w-full"
            :style="imgStyle()"
            draggable="false"
        >
        <div x-show="!preview" class="relative z-[1] flex items-center justify-center">
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20">
                <svg class="h-8 w-8 text-lime" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true">
                    <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                </svg>
            </span>
        </div>
        <span
            x-show="preview"
            x-cloak
            class="absolute bottom-2.5 right-2.5 z-[2] flex h-9 w-9 items-center justify-center rounded-full bg-ink/80 text-lime ring-1 ring-white/20"
            @click.stop="openPicker()"
            @pointerdown.stop
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true">
                <path stroke-linecap="round" d="M12 5v14M5 12h14" />
            </svg>
        </span>
    </button>
    <input
        type="file"
        name="{{ $name }}"
        accept="image/*"
        class="sr-only"
        x-ref="input"
        @change="pick($event)"
        @if($required) :required="!preview" @endif
    >
    <input x-show="preview" x-cloak type="range" min="100" max="220" x-model.number="zoom" class="mt-3 w-40 accent-mint-deep" :aria-label="@js(__('loop.drag_to_position'))">
    @if ($hint)
        <p class="mt-3 text-center text-xs text-ink-muted">{{ $hint }}</p>
    @endif
    {{ $slot }}
</div>
