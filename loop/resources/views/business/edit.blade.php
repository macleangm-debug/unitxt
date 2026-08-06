<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.settings') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.business_settings') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.business_settings_blurb') }}</p>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('business.update') }}" enctype="multipart/form-data" class="loop-panel mx-auto max-w-xl space-y-5 p-6 sm:p-8">
        @csrf
        @method('PATCH')

        <div class="flex items-center gap-4 rounded-2xl bg-chalk/70 p-4">
            @if ($business->logo_path)
                <img src="{{ asset('storage/'.$business->logo_path) }}" alt="" class="h-16 w-16 rounded-2xl object-cover">
            @else
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-ink font-display text-xl text-mint">{{ mb_substr($business->name, 0, 1) }}</div>
            @endif
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold">{{ __('loop.business_logo') }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.shared_logo_hint') }}</p>
                <input type="file" name="logo" accept="image/*" class="loop-input mt-2">
            </div>
        </div>

        <div>
            <label class="loop-label">{{ __('loop.business_name') }}</label>
            <input name="name" value="{{ old('name', $business->name) }}" class="loop-input" required>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.city') }}</label>
            <input name="city" value="{{ old('city', $business->city) }}" class="loop-input">
        </div>
        <div>
            <label class="loop-label">{{ __('loop.description') }}</label>
            <textarea name="description" rows="4" class="loop-input">{{ old('description', $business->description) }}</textarea>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $business->is_active))>
            {{ __('loop.business_is_active') }}
        </label>
        <button class="loop-btn-mint w-full">{{ __('loop.save_changes') }}</button>
    </form>
</x-app-layout>
