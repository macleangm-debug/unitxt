@php
    $editingDefault = $errors->any() ? 'true' : 'false';
@endphp

<div x-data="{ editing: {{ $editingDefault }} }" {{ $attributes }}>
    <div class="mb-4 flex flex-wrap items-center justify-end gap-2">
        <button type="button" class="loop-btn-ghost !py-2" x-show="!editing" @click="editing = true">{{ __('loop.edit') }}</button>
        <button type="button" class="loop-btn-ghost !py-2" x-cloak x-show="editing" @click="editing = false">{{ __('loop.cancel') }}</button>
    </div>
    <fieldset :disabled="!editing" class="min-w-0 space-y-4">
        {{ $slot }}
    </fieldset>
    <p class="mt-3 text-xs text-ink-muted" x-show="!editing">{{ __('loop.settings_locked_hint') }}</p>
</div>
