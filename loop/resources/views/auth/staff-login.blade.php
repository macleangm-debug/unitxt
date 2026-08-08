<x-guest-layout
    :aside-title="__('loop.staff_aside_title')"
    :aside-body="__('loop.staff_aside_body')"
    :aside-stamp="__('loop.business')"
    :aside-point1="__('loop.auth_aside_1')"
    :aside-point2="__('loop.auth_aside_2')"
    :aside-point3="__('loop.auth_aside_3')"
>
    <form method="POST" action="{{ route('staff.login') }}" class="space-y-4">
        @csrf
        <div>
            <h1 class="font-display text-2xl font-semibold">{{ __('loop.staff_login') }}</h1>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.staff_login_blurb') }}</p>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.country_prefix') }}</label>
            <select name="country_code" class="loop-input">
                @foreach ($countries as $code => $meta)
                    <option value="{{ $meta['dial'] }}" @selected(old('country_code', \App\Support\Countries::dial(session('preferred_country', 'TZ'))) === $meta['dial'])>{{ $meta['flag'] }} {{ $meta['dial'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.phone') }}</label>
            <input name="phone" value="{{ old('phone') }}" class="loop-input" placeholder="+255 712 345 678" required autofocus>
            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
        </div>
        <div>
            <label class="loop-label">{{ __('loop.password') }}</label>
            <input type="password" name="password" class="loop-input" required>
        </div>
        <label class="flex items-center gap-2 text-sm text-ink-muted">
            <input type="checkbox" name="remember"> {{ __('loop.remember_me') }}
        </label>
        <button class="loop-btn w-full">{{ __('loop.log_in') }}</button>
        <p class="text-center text-sm text-ink-muted"><a href="{{ route('business.register') }}" class="underline">{{ __('loop.register_business') }}</a></p>
    </form>
</x-guest-layout>
