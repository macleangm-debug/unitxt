@php
    $cardLogo = $business->logoUrl();
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="hidden lg:flex items-start gap-3">
            <x-back-icon :href="route('settings')" />
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.content_studio') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.studio_make_something') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.studio_make_something_blurb') }}</p>
            </div>
        </div>
    </x-slot>

    @if (app(\App\Services\LoopAccess::class)->isPaused($business))
        <div class="mb-5 rounded-[1.5rem] border border-coral/30 bg-coral/10 px-5 py-4">
            <p class="font-semibold">{{ __('loop.loop_paused_studio') }}</p>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.loop_paused_safe') }}</p>
            <a href="{{ route('billing.show') }}" class="mt-3 inline-flex text-sm font-semibold text-violet">{{ __('loop.reactivate_loop') }} →</a>
        </div>
    @endif

    <div
        class="loop-studio"
        x-data="contentStudio({
            topic: @js($initialTopic),
            copyKey: @js($initialCopyKey),
            lang: @js($locale === 'sw' ? 'sw' : 'en'),
            copiesByTopic: @js($copiesByTopic),
            designs: @js($designs),
            design: 'mint_card',
            look: 'plain',
            businessName: @js($business->name),
            hotline: @js($business->hotline),
            logoUrl: @js($cardLogo),
            emptyHints: @js($emptyHints),
            saveLabel: @js(__('loop.save_image')),
            shareLabel: @js(__('loop.share_creative')),
            copiedLabel: @js(__('loop.copied')),
        })"
    >
        <input type="file" accept="image/*" class="hidden" x-ref="photoInput" @change="onPhoto($event)">

        {{-- Mobile: poster first, choices live in the sheet --}}
        <div class="lg:hidden">
            <div class="mb-3 flex items-center gap-3">
                <x-back-icon :href="route('settings')" class="!h-9 !w-9" />
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.content_studio') }}</p>
                    <h1 class="truncate font-display text-lg font-semibold leading-tight">{{ __('loop.studio_make_something') }}</h1>
                </div>
            </div>
            <div class="mx-auto max-w-[20.5rem]">
                @include('content-studio.partials.card')
            </div>
        </div>

        {{-- Desktop split --}}
        <div class="hidden lg:grid lg:grid-cols-[0.92fr_1.08fr] lg:gap-10 lg:items-start">
            <div class="space-y-6">
                @include('content-studio.partials.controls')
            </div>
            <div class="sticky top-6">
                @include('content-studio.partials.card')
                <div class="mt-5 flex gap-3">
                    <button type="button" class="loop-btn-mint flex-1" @click="shareCard()">{{ __('loop.share_creative') }}</button>
                    <button type="button" class="loop-btn-ghost flex-1" @click="saveImage()" :disabled="saving">
                        <span x-show="!saving">{{ __('loop.save_image') }}</span>
                        <span x-show="saving" x-cloak>{{ __('loop.saving') }}</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile actions sit under the poster so they never cover the hotline --}}
        <div class="mx-auto mt-4 max-w-[20.5rem] lg:hidden">
            <div class="grid grid-cols-2 gap-3">
                <button type="button" class="loop-btn-ghost" @click="editOpen = true">{{ __('loop.studio_edit') }}</button>
                <button type="button" class="loop-btn-mint" @click="shareCard()">{{ __('loop.share_creative') }}</button>
            </div>
        </div>

        <x-loop-sheet model="editOpen" :title="__('loop.studio_edit')" lock-swipe="true">
                @include('content-studio.partials.sheet')
        </x-loop-sheet>
    </div>
</x-app-layout>
