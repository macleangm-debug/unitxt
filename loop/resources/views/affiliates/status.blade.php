<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Loop — {{ __('loop.check_status') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<x-site-header>
    <x-slot:actions>
        <a href="{{ route('affiliate.login') }}" class="loop-btn !py-2 text-sm">{{ __('loop.log_in') }}</a>
    </x-slot:actions>
</x-site-header>

<main class="loop-shell py-10">
    <div class="mx-auto max-w-md">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.affiliates') }}</p>
        <h1 class="mt-2 font-display text-3xl font-semibold">{{ __('loop.check_status') }}</h1>
        <p class="mt-2 text-ink-muted">{{ __('loop.affiliate_status_blurb') }}</p>

        <div class="mt-8 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)]">
            @if (!empty($lookedUp))
                @if (! $affiliate)
                    <p class="font-display text-xl font-semibold">{{ __('loop.affiliate_not_found') }}</p>
                    <p class="mt-2 text-sm text-ink-muted">{{ $lookupPhone }}</p>
                    <a href="{{ route('affiliates.apply') }}" class="loop-btn-mint mt-6 inline-flex w-full justify-center">{{ __('loop.become_affiliate') }}</a>
                    <a href="{{ route('affiliates.status') }}" class="mt-3 block text-center text-sm font-semibold text-ink-muted hover:text-ink">{{ __('loop.try_again') }}</a>
                @else
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.status') }}</p>
                    <p class="mt-2 font-display text-2xl font-semibold">{{ __('loop.affiliate_status_'.$affiliate->status) }}</p>
                    <p class="mt-2 text-sm text-ink-muted">{{ $affiliate->name }} · {{ $lookupPhone }}</p>
                    @if ($affiliate->canActivate())
                        <p class="mt-4 text-sm">{{ __('loop.affiliate_approved_activate') }}</p>
                        <a href="{{ route('affiliate.activate', ['country_code' => $affiliate->country_code, 'phone' => $affiliate->phone]) }}" class="loop-btn-mint mt-4 inline-flex w-full justify-center">{{ __('loop.activate_account') }}</a>
                    @elseif ($affiliate->isActive())
                        <a href="{{ route('affiliate.login') }}" class="loop-btn-mint mt-4 inline-flex w-full justify-center">{{ __('loop.affiliate_login') }}</a>
                    @elseif ($affiliate->status === 'rejected' && $affiliate->decision_note)
                        <p class="mt-4 text-sm text-coral">{{ $affiliate->decision_note }}</p>
                    @endif
                    <a href="{{ route('affiliates.status') }}" class="mt-4 block text-center text-sm font-semibold text-ink-muted hover:text-ink">{{ __('loop.try_again') }}</a>
                @endif
            @else
                <form method="POST" action="{{ route('affiliates.status.lookup') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="loop-label">{{ __('loop.country_code') }}</label>
                        <select name="country_code" class="loop-input" required>
                            @foreach ($countries as $code => $meta)
                                <option value="{{ $meta['dial'] }}" @selected(old('country_code', \App\Support\Countries::dial($preferredCountry)) === $meta['dial'])>{{ $meta['flag'] }} {{ $meta['dial'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.phone') }}</label>
                        <input name="phone" value="{{ old('phone') }}" class="loop-input" required placeholder="+255 712 345 678">
                    </div>
                    <button class="loop-btn-mint w-full">{{ __('loop.look_up') }}</button>
                </form>
            @endif
        </div>
    </div>
</main>
<x-site-footer />
@include('partials.confirm-modal')
</body>
</html>
