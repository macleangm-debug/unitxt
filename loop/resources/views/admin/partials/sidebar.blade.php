@php
    $nav = [
        ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => __('loop.admin_overview')],
        ['route' => 'admin.insights.customers', 'match' => 'admin.insights.customers', 'label' => __('loop.admin_customers_nav')],
        ['route' => 'admin.reports.index', 'match' => 'admin.reports.*', 'label' => __('loop.admin_reports')],
        ['route' => 'admin.businesses.index', 'match' => 'admin.businesses.*', 'label' => __('loop.admin_businesses')],
        ['route' => 'admin.articles.index', 'match' => 'admin.articles.*', 'label' => __('loop.admin_articles')],
        ['route' => 'admin.affiliates.index', 'match' => 'admin.affiliates.*', 'label' => __('loop.admin_affiliates')],
        ['route' => 'admin.referrals.index', 'match' => 'admin.referrals.*', 'label' => __('loop.admin_referrals')],
        ['route' => 'admin.settings', 'match' => ['admin.settings*', 'admin.plans.*'], 'label' => __('loop.admin_settings_hub')],
        ['route' => 'admin.integrations.index', 'match' => 'admin.integrations.*', 'label' => __('loop.integrations_hub')],
        ['route' => 'admin.errors.index', 'match' => 'admin.errors.*', 'label' => __('loop.admin_errors')],
    ];
    $settingsOpen = request()->routeIs('admin.settings*') || request()->routeIs('admin.plans.*');
    $settingsItems = [
        'overview' => __('loop.settings_tab_overview'),
        'packages' => __('loop.settings_tab_packages'),
        'billing' => __('loop.settings_tab_billing'),
        'growth' => __('loop.settings_tab_growth'),
        'marketing' => __('loop.settings_tab_marketing'),
        'platform' => __('loop.settings_tab_platform'),
        'sectors' => __('loop.settings_tab_sectors'),
        'countries' => __('loop.settings_tab_countries'),
        'language' => __('loop.settings_tab_language'),
        'visibility' => __('loop.settings_tab_visibility'),
        'referrals' => __('loop.settings_tab_referrals'),
        'affiliates' => __('loop.settings_tab_affiliates'),
        'notifications' => __('loop.settings_tab_notifications'),
        'product' => __('loop.settings_tab_product'),
        'health' => __('loop.settings_tab_health'),
    ];
    $settingsTab = request('tab', 'overview');
@endphp

<div
    class="admin-nav-backdrop"
    x-show="navOpen"
    x-cloak
    @click="navOpen = false"
></div>
<aside class="admin-sidebar" :class="{ 'is-open': navOpen }">
    <div class="admin-brand">
        <x-loop-logo class="h-8 w-8" />
        <div>
            <p class="admin-brand__name">Loop</p>
            <p class="admin-brand__role">{{ __('loop.admin_console') }}</p>
        </div>
        <button type="button" class="admin-icon-btn ml-auto lg:hidden" @click="navOpen = false">×</button>
    </div>
    <nav class="admin-nav" aria-label="{{ __('loop.admin') }}">
        @foreach ($nav as $item)
            @php
                $matches = (array) $item['match'];
                $active = collect($matches)->contains(fn ($pattern) => request()->routeIs($pattern));
            @endphp
            <a href="{{ route($item['route']) }}" class="admin-nav__link {{ $active ? 'is-active' : '' }}" @click="navOpen = false">
                <span>{{ $item['label'] }}</span>
                @if ($item['route'] === 'admin.errors.index' && ($openExceptionHits ?? 0) > 0)
                    <span class="admin-nav__badge">{{ $openExceptionHits }}</span>
                @elseif ($item['route'] === 'admin.affiliates.index' && ($pendingAffiliateApps ?? 0) > 0)
                    <span class="admin-nav__badge">{{ $pendingAffiliateApps }}</span>
                @endif
            </a>
            @if ($item['route'] === 'admin.settings' && $settingsOpen)
                <div class="admin-nav__sub">
                    @foreach ($settingsItems as $key => $label)
                        <a
                            href="{{ route('admin.settings', ['tab' => $key]) }}"
                            class="admin-nav__sublink {{ $settingsTab === $key ? 'is-active' : '' }}"
                            @click="navOpen = false"
                        >{{ $label }}</a>
                    @endforeach
                </div>
            @endif
        @endforeach
    </nav>
</aside>
