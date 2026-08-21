<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="loop-no-skeleton">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Loop — {{ __('loop.check_status') }}</title>
    @include('partials.head-boot')
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

        @if (! empty($lookedUp))
            @if (! $affiliate)
                <div class="loop-status-card mt-8">
                    <div class="loop-status-card__hero loop-status-card__hero--rejected p-6 sm:p-7">
                        <div class="loop-status-card__icon">!</div>
                        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.status') }}</p>
                        <p class="mt-2 font-display text-2xl font-semibold">{{ __('loop.affiliate_not_found') }}</p>
                        <p class="mt-2 text-sm text-ink-muted">{{ $lookupPhone }}</p>
                    </div>
                    <div class="space-y-3 px-6 pb-6 sm:px-7 sm:pb-7">
                        <a href="{{ route('affiliates.apply') }}" class="loop-btn-mint inline-flex w-full justify-center">{{ __('loop.become_affiliate') }}</a>
                        <a href="{{ route('affiliates.status') }}" class="loop-btn-ghost inline-flex w-full justify-center">{{ __('loop.affiliate_lookup_another') }}</a>
                    </div>
                </div>
            @else
                @php
                    $statusKey = $affiliate->status;
                    $statusIcon = match ($statusKey) {
                        'approved', 'active' => '✓',
                        'rejected', 'suspended' => '!',
                        default => '…',
                    };
                    $nextCopy = match ($statusKey) {
                        'pending' => __('loop.affiliate_status_pending_next'),
                        'approved' => __('loop.affiliate_approved_activate'),
                        'rejected' => $affiliate->decision_note ?: __('loop.affiliate_was_rejected'),
                        'active' => __('loop.affiliate_already_active'),
                        'suspended' => __('loop.affiliate_status_suspended'),
                        default => __('loop.affiliate_status_pending_next'),
                    };
                @endphp
                <div class="loop-status-card mt-8">
                    <div class="loop-status-card__hero loop-status-card__hero--{{ $statusKey }} p-6 sm:p-7">
                        <div class="loop-status-card__icon">{{ $statusIcon }}</div>
                        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.status') }}</p>
                        <p class="mt-2 font-display text-2xl font-semibold">{{ __('loop.affiliate_status_'.$statusKey) }}</p>
                        <p class="mt-2 text-sm text-ink-muted">{{ $affiliate->name }} · {{ $lookupPhone }}</p>
                    </div>
                    <div class="px-6 pb-6 sm:px-7 sm:pb-7">
                        <div class="loop-confirm-steps !mt-0">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.affiliate_status_check_how') }}</p>
                            <p class="mt-2 text-sm font-medium leading-relaxed text-ink">{{ $nextCopy }}</p>
                            @if ($statusKey === 'pending')
                                <ol class="mt-3 space-y-2">
                                    <li class="flex items-start gap-3 text-sm text-ink">
                                        <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-ink text-[11px] font-bold text-white">1</span>
                                        <span>{{ __('loop.affiliate_applied_step_1') }}</span>
                                    </li>
                                    <li class="flex items-start gap-3 text-sm text-ink">
                                        <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-ink text-[11px] font-bold text-white">2</span>
                                        <span>{{ __('loop.affiliate_applied_step_2') }}</span>
                                    </li>
                                    <li class="flex items-start gap-3 text-sm text-ink">
                                        <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-ink text-[11px] font-bold text-white">3</span>
                                        <span>{{ __('loop.affiliate_applied_step_3') }}</span>
                                    </li>
                                </ol>
                            @endif
                        </div>
                        <div class="mt-5 space-y-3">
                            @if ($affiliate->canActivate())
                                <a href="{{ route('affiliate.activate', ['country_code' => $affiliate->country_code, 'phone' => $affiliate->phone]) }}" class="loop-btn-mint inline-flex w-full justify-center">{{ __('loop.activate_account') }}</a>
                            @elseif ($affiliate->isActive())
                                <a href="{{ route('affiliate.login') }}" class="loop-btn-mint inline-flex w-full justify-center">{{ __('loop.affiliate_login') }}</a>
                            @endif
                            <a href="{{ route('affiliates.status') }}" class="loop-btn-ghost inline-flex w-full justify-center">{{ __('loop.affiliate_lookup_another') }}</a>
                        </div>
                    </div>
                </div>
            @endif
        @else
            <div class="mt-8 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)]">
                <form method="POST" action="{{ route('affiliates.status.lookup') }}" class="space-y-4" data-loop-no-skeleton>
                    @csrf
                    <div>
                        <label class="loop-label">{{ __('loop.phone') }}</label>
                        <div class="mt-1 flex overflow-hidden rounded-2xl border border-ink/10 bg-white shadow-sm focus-within:border-violet focus-within:ring-1 focus-within:ring-violet">
                            <select name="country_code" class="shrink-0 border-0 border-r border-ink/10 bg-chalk py-3 pl-3 pr-8 text-sm font-semibold focus:ring-0" required>
                                @foreach ($countries as $code => $meta)
                                    <option value="{{ $meta['dial'] }}" @selected(old('country_code', \App\Support\Countries::dial($preferredCountry)) === $meta['dial'])>{{ $meta['flag'] }} {{ $meta['dial'] }}</option>
                                @endforeach
                            </select>
                            <input name="phone" value="{{ old('phone') }}" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-base tracking-wide focus:ring-0" required placeholder="7xxxxxxxx" inputmode="numeric" pattern="[0-9]*">
                        </div>
                    </div>
                    <p class="text-xs text-ink-muted">{{ __('loop.affiliate_status_lookup_hint') }}</p>
                    <button class="loop-btn-mint w-full">{{ __('loop.look_up') }}</button>
                </form>
            </div>
        @endif
    </div>
</main>
<x-site-footer />
@include('partials.confirm-modal')
<x-page-skeleton variant="public" />
</body>
</html>
