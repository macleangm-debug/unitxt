@php
    $editingDefault = $errors->any() ? 'true' : 'false';
    $hasSummary = isset($summary);
@endphp

<div x-data="{ editing: {{ $editingDefault }} }" {{ $attributes->class('admin-lock') }}>
    <div class="admin-lock__bar">
        <button type="button" class="admin-btn-ghost" x-show="!editing" @click="editing = true">{{ __('loop.edit') }}</button>
        <button type="button" class="admin-btn-ghost" x-cloak x-show="editing" @click="editing = false">{{ __('loop.cancel') }}</button>
    </div>
    @if ($hasSummary)
        <div x-show="!editing">
            {{ $summary }}
        </div>
        <fieldset x-cloak x-show="editing" class="min-w-0 space-y-4">
            {{ $slot }}
        </fieldset>
    @else
        <fieldset :disabled="!editing" class="min-w-0 space-y-4" :class="{ 'admin-lock__readonly': !editing }">
            {{ $slot }}
        </fieldset>
        <p class="mt-3 text-xs text-slate-500" x-show="!editing">{{ __('loop.settings_locked_hint') }}</p>
    @endif
</div>
