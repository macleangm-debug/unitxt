<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Membership;
use App\Services\DiscoverCatalog;
use App\Support\Countries;
use App\Support\FeatureFlags;
use App\Support\Sectors;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DiscoverController extends Controller
{
    public const PROFILE_PREVIEW = 10;

    public function __invoke(Request $request, DiscoverCatalog $catalog): View
    {
        $user = $request->user();
        $isCustomer = $user?->isCustomer() ?? false;
        $country = Countries::snapToEnabled($request->query('country', $user?->country ?? session('preferred_country', 'TZ')));
        $city = $request->query('city', $isCustomer ? $user?->city : null);
        $sector = $request->query('sector');
        $category = $request->query('category');
        $status = $request->query('status');
        $search = trim((string) $request->query('q', ''));
        $interests = $isCustomer ? ($user->interests ?? []) : [];

        $filters = [
            'country' => $country,
            'city' => $city,
            'sector' => $sector ?: null,
            'category' => $category ?: null,
            'q' => $search,
            'status' => $isCustomer ? $status : null,
        ];

        $results = $catalog->paginate($filters, $user);
        $frequentBusinesses = $isCustomer && $search === '' && ! $sector && ! $category && ! $status
            ? $catalog->memberPlaces($user, DiscoverCatalog::HOME_LIMIT)
            : collect();

        $membershipByBusinessId = collect();
        if ($isCustomer) {
            $ids = $results->getCollection()->pluck('id')
                ->merge($frequentBusinesses->pluck('id'))
                ->unique()
                ->values();
            if ($ids->isNotEmpty()) {
                $memberships = Membership::query()
                    ->withCount('visits')
                    ->where('customer_id', $user->id)
                    ->whereIn('business_id', $ids)
                    ->get();
                $membershipByBusinessId = $memberships
                    ->groupBy('business_id')
                    ->map(fn (Collection $group) => (object) [
                        'points_balance' => $group->sum('points_balance'),
                        'visits_count' => $group->sum('visits_count'),
                    ]);
            }
        }

        return view('discover.index', [
            'results' => $results,
            'frequentBusinesses' => $frequentBusinesses,
            'membershipByBusinessId' => $membershipByBusinessId,
            'isCustomer' => $isCustomer,
            'sectors' => Sectors::all(),
            'sectorOptions' => Sectors::sheetOptions(),
            'featuredSectors' => Sectors::featured(),
            'countries' => Countries::enabledOptions(),
            'cities' => Countries::cities($country),
            'activeCountry' => $country,
            'activeCity' => $city,
            'activeSector' => $sector,
            'activeCategory' => $category,
            'activeStatus' => $status,
            'search' => $search,
            'searchMiss' => $search !== '' && $results->total() === 0,
            'interests' => $interests,
        ]);
    }

    public function show(Business $business): View
    {
        abort_unless($business->is_active, 404);

        $access = app(\App\Services\LoopAccess::class);
        $loopPaused = $access->isPaused($business);

        $user = request()->user();
        $isCustomer = $user?->isCustomer() ?? false;
        $memberships = collect();
        $totalPoints = null;
        $wantedBack = false;

        if ($isCustomer) {
            $memberships = Membership::query()
                ->where('customer_id', $user->id)
                ->where('business_id', $business->id)
                ->with('shop')
                ->get()
                ->keyBy('shop_id');
            $totalPoints = $memberships->sum('points_balance');
            $wantedBack = $loopPaused && $access->wantsLoopBack($business, $user->id);
        }

        $related = Business::query();
        $access->constrainPromoted($related);
        $related = $related
            ->where('id', '!=', $business->id)
            ->where('country', $business->country)
            ->where(function ($q) use ($business) {
                $q->where('sector', $business->sector)
                    ->orWhere('city', $business->city);
            })
            ->with(['shops' => fn ($q) => $q->where('is_active', true), 'campaigns' => fn ($c) => $c->active()])
            ->withCount(['shops' => fn ($q) => $q->where('is_active', true)])
            ->latest()
            ->take(8)
            ->get();

        $campaigns = $this->previewList($business->campaigns()->active()->latest('id'));
        $rewards = $this->previewList($business->rewards()->where('is_active', true)->orderBy('points_cost'));
        $raffles = FeatureFlags::enabled('raffles')
            ? $this->previewList(
                $business->raffles()
                    ->where('is_active', true)
                    ->whereIn('status', ['scheduled', 'live'])
                    ->orderBy('draw_at')
            )
            : ['items' => collect(), 'has_more' => false];
        $liveGames = \App\Support\GameSettings::engineOn() && \App\Support\GameSettings::tablesReady()
            ? $business->games()->live()->orderBy('id')->get()
            : collect();

        return view('discover.show', [
            'business' => $business->load(['shops' => fn ($q) => $q->where('is_active', true)]),
            'campaigns' => $campaigns['items'],
            'campaignsHasMore' => $campaigns['has_more'],
            'rewards' => $rewards['items'],
            'rewardsHasMore' => $rewards['has_more'],
            'raffles' => $raffles['items'],
            'rafflesHasMore' => $raffles['has_more'],
            'liveGames' => $liveGames,
            'related' => $related,
            'sectors' => Sectors::all(),
            'sectorOptions' => Sectors::sheetOptions(),
            'isCustomer' => $isCustomer,
            'memberships' => $memberships,
            'totalPoints' => $totalPoints,
            'loopPaused' => $loopPaused,
            'wantedBack' => $wantedBack,
        ]);
    }

    public function catalog(Request $request, Business $business, string $kind): View|RedirectResponse
    {
        abort_unless($business->is_active, 404);
        abort_unless(in_array($kind, ['campaigns', 'offers', 'raffles'], true), 404);
        if ($kind === 'raffles') {
            abort_unless(FeatureFlags::enabled('raffles'), 404);
        }

        $access = app(\App\Services\LoopAccess::class);
        if ($access->isPaused($business)) {
            return redirect()->route('discover.show', $business);
        }

        $user = $request->user();
        $isCustomer = $user?->isCustomer() ?? false;

        $items = match ($kind) {
            'campaigns' => $business->campaigns()->active()->latest('id')->paginate(24),
            'offers' => $business->rewards()->where('is_active', true)->orderBy('points_cost')->paginate(24),
            default => $business->raffles()
                ->where('is_active', true)
                ->whereIn('status', ['scheduled', 'live'])
                ->orderBy('draw_at')
                ->paginate(24),
        };

        $title = match ($kind) {
            'campaigns' => __('loop.all_campaigns'),
            'offers' => __('loop.all_offers'),
            default => __('loop.all_raffles'),
        };

        return view('discover.catalog', [
            'business' => $business->load(['shops' => fn ($q) => $q->where('is_active', true)]),
            'kind' => $kind,
            'title' => $title,
            'items' => $items,
            'isCustomer' => $isCustomer,
        ]);
    }

    /**
     * @return array{items: Collection, has_more: bool}
     */
    private function previewList($query): array
    {
        $rows = $query->take(self::PROFILE_PREVIEW + 1)->get();

        return [
            'items' => $rows->take(self::PROFILE_PREVIEW)->values(),
            'has_more' => $rows->count() > self::PROFILE_PREVIEW,
        ];
    }
}
