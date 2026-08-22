@php
    $browsePad = $isCustomer ?? false;
    $search = $search ?? '';
@endphp
<div @if (! $browsePad) class="pb-28 pt-6" @endif x-data="{ filtersOpen: false, q: @js($search), hayMatch(hay) { const q = this.q.trim().toLowerCase(); return !q || String(hay).includes(q); } }">
    <div @class(['loop-shell' => ! $browsePad])>
        @if (session('status'))
            <div class="mb-4 rounded-2xl border border-lime/50 bg-lime/20 px-4 py-3 text-sm font-medium text-ink">{{ session('status') }}</div>
        @endif

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
                    x-model="q"
                    value="{{ $search }}"
                    placeholder="{{ __('loop.search_shops_placeholder') }}"
                    class="loop-input"
                    autocomplete="off"
                    enterkeyhint="search"
                >
                <button type="submit" class="loop-btn">{{ __('loop.search') }}</button>
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

    @forelse ($rows as $row)
        <section @class(['loop-shell mt-8 first:mt-6' => ! $browsePad, 'mt-8 first:mt-6' => $browsePad])>
            <div class="mb-4 flex items-end justify-between gap-3">
                <h2 class="font-display text-xl font-semibold sm:text-2xl">{{ $row['title'] }}</h2>
                @if ($row['key'] === 'frequent')
                    <span class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.frequent') }}</span>
                @endif
            </div>
            <div class="loop-shop-grid">
                @foreach ($row['businesses'] as $business)
                    @php
                        $membership = $membershipByBusinessId->get($business->id);
                        $points = $membership?->points_balance;
                        $cheapest = $business->rewards?->first();
                        $hay = strtolower(trim(implode(' ', array_filter([
                            $business->name,
                            \App\Support\Sectors::label($business->sector, $business->sector_other),
                            $business->shops?->first()?->city ?: $business->city,
                            $cheapest?->name,
                        ]))));
                    @endphp
                    <div x-show="hayMatch(@js($hay))">
                    <x-discover-tile
                        :business="$business"
                        :show-points="$isCustomer && $membership !== null"
                        :points="$points"
                        :headline="$cheapest?->name"
                    />
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div @class(['loop-shell' => ! $browsePad])>
            <div class="loop-panel mt-10 p-8 text-center">
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
        </div>
    @endforelse

    <div x-show="filtersOpen" x-cloak class="fixed inset-0 z-40 sm:hidden" @keydown.escape.window="filtersOpen=false">
        <div class="absolute inset-0 bg-ink/40" @click="filtersOpen=false"></div>
        <div class="absolute inset-x-0 bottom-0 rounded-t-3xl bg-white p-5 pb-8 shadow-2xl" @click.stop>
            <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-ink/15"></div>
            <h3 class="font-display text-lg font-semibold">{{ __('loop.filters') }}</h3>
            <form method="GET" action="{{ route('discover') }}" class="mt-4 space-y-3">
                <input type="hidden" name="q" value="{{ $search }}">
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
            </form>
        </div>
    </div>
</div>
