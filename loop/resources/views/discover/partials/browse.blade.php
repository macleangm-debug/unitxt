@php
    $browsePad = $isCustomer ?? false;
    $search = $search ?? '';
    $featuredSectors = $featuredSectors ?? [];
    $chipBase = array_filter([
        'country' => $activeCountry ?? null,
        'city' => $activeCity ?? null,
        'q' => $search !== '' ? $search : null,
    ]);
@endphp
<div @if (! $browsePad) class="pb-28 pt-6" @endif x-data="{ filtersOpen: false }">
    <div @class(['loop-shell' => ! $browsePad])>

        <div class="flex items-end justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-violet">Loop</p>
                <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight">{{ __('loop.browse_campaigns') }}</h1>
                <p class="mt-2 text-sm text-ink-muted">
                    {{ $activeCity ?: __('loop.all_cities') }} · {{ $countries[$activeCountry]['name'] ?? $activeCountry }}
                    @unless ($isCustomer)
                        · {{ __('loop.sign_in_for_points') }}
                    @endunless
                </p>
            </div>
            <button type="button" @click="filtersOpen = true" class="loop-btn-ghost !px-3 !py-2 text-sm sm:hidden">{{ __('loop.filters') }}</button>
        </div>

        <form id="discover-filters" method="GET" action="{{ route('discover') }}" class="mt-5 space-y-3">
            <div class="loop-discover-search">
                <label class="sr-only" for="discover-q">{{ __('loop.search_shops') }}</label>
                <input
                    id="discover-q"
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="{{ __('loop.search_shops_placeholder') }}"
                    class="loop-input"
                    autocomplete="off"
                    enterkeyhint="search"
                >
                <button type="submit" class="loop-btn">{{ __('loop.search') }}</button>
            </div>

            <div class="loop-discover-chips">
                <a
                    href="{{ route('discover', array_filter(['country' => $activeCountry, 'city' => $activeCity, 'q' => $search !== '' ? $search : null])) }}"
                    class="loop-chip {{ empty($activeSector) && empty($activeCategory) && empty($activeStatus) ? 'is-on' : '' }}"
                >{{ __('loop.all') }}</a>
                @if ($isCustomer)
                    <a href="{{ route('discover', $chipBase + ['status' => 'ready']) }}" class="loop-chip {{ ($activeStatus ?? '') === 'ready' ? 'is-on' : '' }}">{{ __('loop.filter_reward_ready') }}</a>
                    <a href="{{ route('discover', $chipBase + ['status' => 'almost']) }}" class="loop-chip {{ ($activeStatus ?? '') === 'almost' ? 'is-on' : '' }}">{{ __('loop.filter_almost_there') }}</a>
                    <a href="{{ route('discover', $chipBase + ['status' => 'offers']) }}" class="loop-chip {{ ($activeStatus ?? '') === 'offers' ? 'is-on' : '' }}">{{ __('loop.filter_offers') }}</a>
                @endif
                @foreach ($featuredSectors as $row)
                    <a
                        href="{{ route('discover', $chipBase + ['sector' => $row['key']]) }}"
                        class="loop-chip {{ ($activeSector ?? '') === $row['key'] ? 'is-on' : '' }}"
                    >{{ $row['short'] ?: $row['label'] }}</a>
                @endforeach
                <button type="button" class="loop-chip" @click="filtersOpen = true">{{ __('loop.more') }}</button>
            </div>

            <div class="hidden gap-3 sm:grid sm:grid-cols-3">
            <x-sheet-select
                name="country"
                :label="__('loop.country')"
                :options="collect($countries)->mapWithKeys(fn ($meta, $code) => [$code => ($meta['flag'].' '.$meta['name'])])->all()"
                :value="$activeCountry"
                :autosubmit="true"
            />
            <x-city-sheet-select
                name="city"
                :label="__('loop.city')"
                :value="$activeCity ?? ''"
                :cities="$cities"
                :country="$activeCountry"
                :allow-empty="true"
                :autosubmit="true"
            />
            <x-sheet-select
                name="sector"
                :label="__('loop.sector')"
                :options="$sectorOptions ?? collect(['' => __('loop.all')])->union($sectors)->all()"
                :value="$activeSector ?? ''"
                :autosubmit="true"
            />
            </div>

            <x-loop-sheet :title="__('loop.filters')" model="filtersOpen" lock-swipe="true">
                <div class="space-y-3">
                    <x-sheet-select
                        name="country"
                        :label="__('loop.country')"
                        :options="collect($countries)->mapWithKeys(fn ($meta, $code) => [$code => ($meta['flag'].' '.$meta['name'])])->all()"
                        :value="$activeCountry"
                    />
                    <x-city-sheet-select
                        name="city"
                        :label="__('loop.city')"
                        :value="$activeCity ?? ''"
                        :cities="$cities"
                        :country="$activeCountry"
                        :allow-empty="true"
                    />
                    <x-sheet-select
                        name="sector"
                        :label="__('loop.sector')"
                        :options="$sectorOptions ?? collect(['' => __('loop.all')])->union($sectors)->all()"
                        :value="$activeSector ?? ''"
                    />
                    <button class="loop-btn w-full">{{ __('loop.apply') }}</button>
                </div>
            </x-loop-sheet>
        </form>
        @if ($isCustomer)
            <p class="mt-3 text-sm text-ink-muted">
                {{ __('loop.not_listed_invite') }}
                <span class="inline-block">
                    <x-invite-business
                        :business-name="$search"
                        :show-name-field="true"
                        button-class="font-semibold text-violet hover:text-ink"
                        :button-label="__('loop.invite_missing_shop')"
                    />
                </span>
            </p>
        @endif
    </div>

    @if (($frequentBusinesses ?? collect())->isNotEmpty())
        <section @class(['loop-shell mt-8' => ! $browsePad, 'mt-8' => $browsePad])>
            <div class="mb-4 flex items-end justify-between gap-3">
                <h2 class="font-display text-xl font-semibold sm:text-2xl">{{ __('loop.your_places') }}</h2>
                <span class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.frequent') }}</span>
            </div>
            <div class="loop-carousel items-stretch" x-data="loopParallaxCarousel()">
                @foreach ($frequentBusinesses as $business)
                    @php $membership = $membershipByBusinessId->get($business->id); @endphp
                    <x-discover-tile
                        :business="$business"
                        :show-points="$isCustomer && $membership !== null"
                        :points="$membership?->points_balance"
                        :carousel="true"
                        data-loop-card
                    />
                @endforeach
            </div>
        </section>
    @endif

    <section @class(['loop-shell mt-8' => ! $browsePad, 'mt-8' => $browsePad])>
        @if ($search !== '')
            <h2 class="mb-4 font-display text-xl font-semibold sm:text-2xl">{{ __('loop.search_results', ['q' => $search]) }}</h2>
        @elseif (($activeSector ?? null))
            <h2 class="mb-4 font-display text-xl font-semibold sm:text-2xl">{{ \App\Support\Sectors::label($activeSector) }}</h2>
        @else
            <h2 class="mb-4 font-display text-xl font-semibold sm:text-2xl">{{ __('loop.discover') }}</h2>
        @endif

        @if ($results->isNotEmpty())
            <div class="space-y-2">
                @foreach ($results as $business)
                    @php $membership = $membershipByBusinessId->get($business->id); @endphp
                    <x-discover-row
                        :business="$business"
                        :points="$membership?->points_balance"
                        :is-member="$isCustomer && $membership !== null"
                    />
                @endforeach
            </div>
            <div class="mt-6">
                {{ $results->links() }}
            </div>
        @else
            <div class="loop-panel mt-4 p-8 text-center">
                @if ($searchMiss ?? false)
                    <p class="font-display text-lg font-semibold text-ink">{{ __('loop.no_shops_search', ['q' => $search]) }}</p>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.no_shops_search_body') }}</p>
                    @if ($isCustomer)
                        <div class="mt-5">
                            <x-invite-business
                                :business-name="$search"
                                :show-name-field="true"
                                button-class="loop-btn"
                                :button-label="__('loop.invite_missing_shop')"
                            />
                        </div>
                    @else
                        <a href="{{ route('customer.login') }}" class="loop-btn mt-5 inline-flex">{{ __('loop.sign_in_to_invite') }}</a>
                    @endif
                @else
                    <p class="font-display text-lg font-semibold text-ink">{{ __('loop.nothing_nearby_title') }}</p>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.nothing_nearby_body') }}</p>
                    @if ($isCustomer)
                        <div class="mt-5">
                            <x-invite-business
                                :show-name-field="true"
                                button-class="loop-btn"
                                :button-label="__('loop.invite_missing_shop')"
                            />
                        </div>
                    @endif
                @endif
            </div>
        @endif
    </section>
</div>
