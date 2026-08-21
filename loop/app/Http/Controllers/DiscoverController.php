<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Membership;
use App\Support\Countries;
use App\Support\Sectors;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DiscoverController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $isCustomer = $user?->isCustomer() ?? false;
        $country = Countries::snapToEnabled($request->query('country', $user?->country ?? session('preferred_country', 'TZ')));
        $city = $request->query('city', $isCustomer ? $user?->city : null);
        $sector = $request->query('sector');
        $search = trim((string) $request->query('q', ''));
        $interests = $isCustomer ? ($user->interests ?? []) : [];

        $businesses = Business::query();
        app(\App\Services\LoopAccess::class)->constrainPromoted($businesses);
        $businesses = $businesses
            ->where('country', $country)
            ->when($sector, fn ($q) => $q->where('sector', $sector))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhereHas('shops', function ($shops) use ($like) {
                            $shops->where('is_active', true)
                                ->where(function ($shop) use ($like) {
                                    $shop->where('name', 'like', $like)
                                        ->orWhere('city', 'like', $like);
                                });
                        });
                });
            })
            ->whereHas('shops', function ($q) use ($city) {
                $q->where('is_active', true)
                    ->when($city, fn ($qq) => $qq->where('city', $city));
            })
            ->with([
                'shops' => fn ($q) => $q->where('is_active', true)->when($city, fn ($qq) => $qq->where('city', $city)),
                'campaigns' => fn ($c) => $c->active(),
                'rewards' => fn ($r) => $r->where('is_active', true)->orderBy('points_cost'),
            ])
            ->withCount(['shops' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        $membershipByBusinessId = collect();
        $frequentBusinesses = collect();

        if ($isCustomer) {
            $memberships = Membership::query()
                ->withCount('visits')
                ->where('customer_id', $user->id)
                ->whereIn('business_id', $businesses->pluck('id'))
                ->get();

            $membershipByBusinessId = $memberships
                ->groupBy('business_id')
                ->map(fn (Collection $group) => (object) [
                    'points_balance' => $group->sum('points_balance'),
                    'visits_count' => $group->sum('visits_count'),
                ]);

            $frequentBusinesses = $businesses
                ->filter(fn (Business $business) => $membershipByBusinessId->has($business->id))
                ->sortByDesc(function (Business $business) use ($membershipByBusinessId) {
                    $m = $membershipByBusinessId->get($business->id);

                    return sprintf('%08d-%08d', $m->visits_count, $m->points_balance);
                })
                ->values();
        }

        $rows = $this->buildRows($businesses, $frequentBusinesses, $sector, $interests, $city, $search);

        return view('discover.index', [
            'rows' => $rows,
            'membershipByBusinessId' => $membershipByBusinessId,
            'isCustomer' => $isCustomer,
            'sectors' => Sectors::all(),
            'sectorOptions' => Sectors::sheetOptions(),
            'countries' => Countries::enabledOptions(),
            'cities' => Countries::cities($country),
            'activeCountry' => $country,
            'activeCity' => $city,
            'activeSector' => $sector,
            'search' => $search,
            'searchMiss' => $search !== '' && $businesses->isEmpty(),
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

        return view('discover.show', [
            'business' => $business->load(['shops' => fn ($q) => $q->where('is_active', true)]),
            'campaigns' => $business->campaigns()->active()->get(),
            'rewards' => $business->rewards()->where('is_active', true)->orderBy('points_cost')->get(),
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

    /**
     * @return list<array{key: string, title: string, businesses: Collection<int, Business>}>
     */
    private function buildRows(Collection $businesses, Collection $frequentBusinesses, ?string $sector, array $interests, ?string $city, string $search = ''): array
    {
        if ($search !== '') {
            if ($businesses->isEmpty()) {
                return [];
            }

            return [[
                'key' => 'search',
                'title' => __('loop.search_results', ['q' => $search]),
                'businesses' => $businesses->values(),
            ]];
        }

        $rows = [];

        if ($frequentBusinesses->isNotEmpty()) {
            $rows[] = [
                'key' => 'frequent',
                'title' => __('loop.your_places'),
                'businesses' => $frequentBusinesses,
            ];
        }

        if ($sector) {
            $rows[] = [
                'key' => 'sector-'.$sector,
                'title' => Sectors::label($sector),
                'businesses' => $businesses->values(),
            ];

            return $rows;
        }

        $bySector = $businesses->groupBy('sector');

        $orderedKeys = collect(array_keys(Sectors::all()))
            ->sortBy(function (string $key) use ($interests, $bySector) {
                if (! $bySector->has($key)) {
                    return '9-'.str_pad((string) Sectors::rankFor($key), 3, '0', STR_PAD_LEFT).'-'.$key;
                }
                if (in_array($key, $interests, true)) {
                    return '0-'.$key;
                }

                return '1-'.str_pad((string) Sectors::rankFor($key), 3, '0', STR_PAD_LEFT).'-'.$key;
            })
            ->values();

        foreach ($orderedKeys as $key) {
            if (! $bySector->has($key) || $bySector[$key]->isEmpty()) {
                continue;
            }

            $title = Sectors::label($key);
            if ($city) {
                $title = $title.' · '.$city;
            }

            $rows[] = [
                'key' => 'sector-'.$key,
                'title' => $title,
                'businesses' => $bySector[$key]->values(),
            ];
        }

        return $rows;
    }
}
