@props([
    'businessName' => '',
    'showNameField' => false,
    'buttonClass' => 'loop-btn-lime mt-6',
    'buttonLabel' => null,
])

@php
    $shareLink = \App\Support\PlatformUrl::route('business.register', ['scout' => auth()->id()]);
    $defaultName = filled($businessName) ? $businessName : __('loop.a_shop_you_love');
@endphp

<div x-data="{ open: false, copied: false }" {{ $attributes }}>
    <button type="button" class="{{ $buttonClass }}" @click="open = true">{{ $buttonLabel ?? __('loop.share_loop') }}</button>

    <x-loop-sheet :title="__('loop.share_loop_sheet_title')" lock-swipe="true">
            <p class="text-sm text-ink-muted">{{ __('loop.share_loop_sheet_body') }}</p>

            <form method="POST" action="{{ route('business-invites.store') }}" class="mt-5 space-y-3">
                @csrf
                <input type="hidden" name="city" value="{{ auth()->user()->city }}">
                <input type="hidden" name="country_code" value="{{ \App\Support\Countries::dial(auth()->user()->country ?? 'TZ') }}">
                @if ($showNameField)
                    <div>
                        <label class="loop-label">{{ __('loop.business') }}</label>
                        <input name="business_name" value="{{ old('business_name', $businessName) }}" class="loop-input" maxlength="120" required>
                    </div>
                @else
                    <input type="hidden" name="business_name" value="{{ $defaultName }}">
                @endif
                <button name="share_via" value="whatsapp" class="loop-btn w-full">{{ __('loop.share_on_whatsapp') }}</button>
                <button name="share_via" value="sms" class="loop-btn-ghost w-full">{{ __('loop.share_by_sms') }}</button>
            </form>

            <button
                type="button"
                class="loop-btn-ghost mt-3 w-full"
                @click="
                    navigator.clipboard.writeText(@js($shareLink));
                    copied = true;
                    setTimeout(() => copied = false, 1800);
                "
                x-text="copied ? @js(__('loop.link_copied')) : @js(__('loop.copy_link'))"
            ></button>

            <button type="button" class="mt-4 w-full py-2 text-sm font-semibold text-ink-muted" @click="open = false">{{ __('loop.close') }}</button>
    </x-loop-sheet>
</div>
