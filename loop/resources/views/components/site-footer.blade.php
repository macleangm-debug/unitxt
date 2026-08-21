<footer class="border-t border-ink/10 bg-ink text-white">
    <div class="loop-shell grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-4">
        <div class="sm:col-span-2 lg:col-span-1">
            <div class="flex items-center gap-2.5">
                <x-loop-logo class="h-10 w-10" />
                <span class="font-display text-2xl font-semibold">Loop</span>
            </div>
            <p class="mt-4 max-w-xs text-sm leading-relaxed text-white/65">{{ __('loop.tagline') }} {{ __('loop.hero_body') }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-white/45">{{ __('loop.footer_product') }}</p>
            <ul class="mt-4 space-y-2.5 text-sm text-white/75">
                <li><a href="{{ route('landing.business') }}" class="hover:text-white">{{ __('loop.business') }}</a></li>
                <li><a href="{{ route('landing.customer') }}" class="hover:text-white">{{ __('loop.customer') }}</a></li>
                <li><a href="{{ route('stories.index') }}" class="hover:text-white">{{ __('loop.stories') }}</a></li>
                @if (\App\Support\AffiliateProgram::isEnabled() && \App\Support\MarketingSettings::settings()['show_affiliate_cta'])
                <li><a href="{{ route('affiliates.landing') }}" class="hover:text-white">{{ __('loop.affiliates') }}</a></li>
                @endif
                <li><a href="{{ route('landing.business') }}#pricing" class="hover:text-white">{{ __('loop.footer_pricing') }}</a></li>
                <li><a href="{{ route('discover') }}" class="hover:text-white">{{ __('loop.browse_campaigns') }}</a></li>
                <li><a href="/#how" class="hover:text-white">{{ __('loop.footer_how') }}</a></li>
            </ul>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-white/45">{{ __('loop.footer_company') }}</p>
            <ul class="mt-4 space-y-2.5 text-sm text-white/75">
                <li><a href="/" class="hover:text-white">{{ __('loop.footer_about') }}</a></li>
                <li><a href="mailto:hello@loop.africa" class="hover:text-white">{{ __('loop.footer_contact') }}</a></li>
                <li><a href="mailto:support@loop.africa" class="hover:text-white">{{ __('loop.footer_support') }}</a></li>
            </ul>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-white/45">{{ __('loop.footer_legal') }}</p>
            <ul class="mt-4 space-y-2.5 text-sm text-white/75">
                <li><a href="#" class="hover:text-white">{{ __('loop.footer_privacy') }}</a></li>
                <li><a href="#" class="hover:text-white">{{ __('loop.footer_terms') }}</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="loop-shell flex flex-wrap items-center justify-between gap-3 py-5 text-xs text-white/45">
            <span>© {{ date('Y') }} Loop</span>
            <div class="flex gap-2">
                @foreach (\App\Support\Countries::enabledOptions() as $meta)
                    <span title="{{ $meta['name'] }}">{{ $meta['flag'] }}</span>
                @endforeach
            </div>
        </div>
    </div>
</footer>
