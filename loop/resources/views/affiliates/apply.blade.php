<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Loop — {{ __('loop.become_affiliate') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<x-site-header>
    <x-slot:actions>
        <a href="{{ route('affiliates.landing') }}" class="loop-btn-ghost !py-2 text-sm">{{ __('loop.back') }}</a>
    </x-slot:actions>
</x-site-header>

<main class="loop-shell py-10">
    <div class="mx-auto max-w-xl">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.affiliates') }}</p>
        <h1 class="mt-2 font-display text-3xl font-semibold">{{ __('loop.affiliate_apply_title') }}</h1>
        <p class="mt-2 text-ink-muted">{{ __('loop.affiliate_apply_body') }}</p>

        <form method="POST" action="{{ route('affiliates.apply.store') }}" class="mt-8 space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8">
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
                <select name="country" class="loop-input" required>
                    @foreach ($countries as $code => $meta)
                        <option value="{{ $code }}" @selected(old('country', $preferredCountry) === $code)>{{ $meta['flag'] }} {{ $meta['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="loop-label">{{ __('loop.phone') }}</label>
                <input name="phone" value="{{ old('phone') }}" class="loop-input" required placeholder="712000000">
                <x-input-error :messages="$errors->get('phone')" class="mt-1" />
            </div>

            <div>
                <label class="loop-label">{{ __('loop.email') }}</label>
                <input type="email" name="email" value="{{ old('email') }}" class="loop-input">
            </div>

            <div class="rounded-2xl bg-chalk/70 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.identification') }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ __('loop.identification_help') }}</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="loop-label">{{ __('loop.id_type') }}</label>
                        <select name="id_type" class="loop-input" required>
                            @foreach ($idTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('id_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.id_number') }}</label>
                        <input name="id_number" value="{{ old('id_number') }}" class="loop-input" required>
                    </div>
                </div>
            </div>

            <div>
                <label class="loop-label">{{ __('loop.city') }}</label>
                <input name="city" value="{{ old('city') }}" class="loop-input" required>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.address') }}</label>
                <input name="address" value="{{ old('address') }}" class="loop-input">
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="loop-label">{{ __('loop.payout_phone') }}</label>
                    <input name="payout_phone" value="{{ old('payout_phone') }}" class="loop-input">
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.bank_name') }}</label>
                    <input name="bank_name" value="{{ old('bank_name') }}" class="loop-input">
                </div>
            </div>

            <p class="text-xs text-ink-muted">{{ __('loop.affiliate_review_note') }}</p>
            <button class="loop-btn-mint w-full">{{ __('loop.submit_application') }}</button>
        </form>
    </div>
</main>
<x-site-footer />
@include('partials.confirm-modal')
</body>
</html>
