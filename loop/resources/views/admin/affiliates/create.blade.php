<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('loop.admin_affiliates') }}</p>
            <h1 class="mt-1">{{ __('loop.create_affiliate') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('loop.create_affiliate_blurb') }}</p>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('admin.affiliates.store') }}" class="admin-card max-w-xl space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="loop-label">{{ __('loop.first_name') }}</label>
                <input name="first_name" value="{{ old('first_name') }}" class="loop-input" required>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.last_name') }}</label>
                <input name="last_name" value="{{ old('last_name') }}" class="loop-input" required>
            </div>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.country') }}</label>
            <select name="country" class="loop-input">
                @foreach ($countries as $code => $meta)
                    <option value="{{ $code }}" @selected(old('country', 'TZ') === $code)>{{ $meta['flag'] }} {{ $meta['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.phone') }}</label>
            <input name="phone" value="{{ old('phone') }}" class="loop-input" required inputmode="tel">
        </div>
        <div>
            <label class="loop-label">{{ __('loop.city') }}</label>
            <input name="city" value="{{ old('city') }}" class="loop-input">
        </div>
        <div>
            <label class="loop-label">{{ __('loop.temp_password') }}</label>
            <input type="password" name="password" class="loop-input" autocomplete="off">
            <p class="mt-1 text-xs text-slate-500">{{ __('loop.create_affiliate_password_help') }}</p>
        </div>
        <div class="flex gap-3">
            <button class="admin-btn">{{ __('loop.save') }}</button>
            <a href="{{ route('admin.affiliates.index') }}" class="admin-btn-ghost">{{ __('loop.cancel') }}</a>
        </div>
    </form>
</x-admin-layout>
