@php
    $isAdmin = request()->boolean('admin');
@endphp
<x-guest-layout
    :aside-title="$isAdmin ? __('loop.admin_aside_title') : __('loop.staff_aside_title')"
    :aside-body="$isAdmin ? __('loop.admin_aside_body') : __('loop.staff_aside_body')"
    :aside-stamp="$isAdmin ? __('loop.admin') : __('loop.business')"
    :aside-point1="__('loop.auth_aside_1')"
    :aside-point2="__('loop.auth_aside_2')"
    :aside-point3="__('loop.auth_aside_3')"
>
    <form method="POST" action="{{ route('staff.login') }}" class="space-y-4" autocomplete="off" data-lpignore="true" data-1p-ignore="true" x-data="{
        country: @js(old('country_code', \App\Support\Countries::dial(session('preferred_country', 'TZ')))),
        dials: @js(collect($countries)->mapWithKeys(fn ($m, $c) => [$m['dial'] => $m['dial']])->all()),
    }">
        @csrf
        @if ($isAdmin)
            <input type="hidden" name="admin" value="1">
        @endif
        <div>
            <h1 class="font-display text-2xl font-semibold">{{ $isAdmin ? __('loop.admin_login') : __('loop.staff_login') }}</h1>
            <p class="mt-1 text-sm text-ink-muted">{{ $isAdmin ? __('loop.admin_login_blurb') : __('loop.staff_login_blurb') }}</p>
        </div>

        @if ($isAdmin)
            <div class="rounded-2xl border border-ink/10 bg-chalk/70 px-4 py-3 text-sm text-ink">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.demo_credentials') }}</p>
                <p class="mt-2 font-medium">{{ __('loop.phone') }}: <span class="font-mono">+255 710 000 000</span></p>
                <p class="mt-1 font-medium">{{ __('loop.password') }}: <span class="font-mono">password</span></p>
            </div>
        @endif

        <div>
            <label class="loop-label">{{ __('loop.phone') }}</label>
            <div class="mt-1 flex overflow-hidden rounded-2xl border border-ink/10 bg-white shadow-sm focus-within:border-violet focus-within:ring-1 focus-within:ring-violet">
                <select name="country_code" x-model="country" class="shrink-0 border-0 border-r border-ink/10 bg-chalk py-3 pl-3 pr-8 text-sm font-semibold focus:ring-0">
                    @foreach ($countries as $code => $meta)
                        <option value="{{ $meta['dial'] }}" @selected(old('country_code', \App\Support\Countries::dial(session('preferred_country', 'TZ'))) === $meta['dial'])>{{ $meta['flag'] }} {{ $meta['dial'] }}</option>
                    @endforeach
                </select>
                <input name="phone" value="{{ old('phone', $isAdmin ? '710000000' : '') }}" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-base tracking-wide focus:ring-0" placeholder="7xxxxxxxx" required autofocus inputmode="numeric" pattern="[0-9]*" autocomplete="off" data-lpignore="true" data-1p-ignore="true">
            </div>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.password') }}</label>
            <input type="text" name="password" class="loop-input loop-secret" required value="{{ old('password') }}" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true" data-form-type="other" spellcheck="false" autocorrect="off">
        </div>
        <label class="flex items-center gap-2 text-sm text-ink-muted">
            <input type="checkbox" name="remember"> {{ __('loop.remember_me') }}
        </label>
        <button class="loop-btn w-full">{{ __('loop.log_in') }}</button>
        @unless ($isAdmin)
            <p class="text-center text-sm text-ink-muted"><a href="{{ route('business.register') }}" class="underline">{{ __('loop.register_business') }}</a></p>
        @endunless
        @if ($isAdmin)
            <p class="text-center text-sm text-ink-muted"><a href="{{ route('staff.login') }}" class="underline">{{ __('loop.staff_login') }}</a></p>
        @endif
    </form>
</x-guest-layout>
