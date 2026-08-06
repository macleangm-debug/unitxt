<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Membership;
use App\Models\Shop;
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
        $country = $request->query('country', $user?->country ?? session('preferred_country', 'TZ'));
        $city = $request->query('city', $isCustomer ? $user?->city : null);
        $sector = $request->query('sector');
        $interests = $isCustomer ? ($user->interests ?? []) : [];

        $shops = Shop::query()
            ->with([
                'business' => fn ($q) => $q->with([
                    'campaigns' => fn ($c) => $c->active(),
                    'rewards' => fn ($r) => $r->where('is_active', true),
                ]),
            ])
            ->where('is_active', true)
            ->whereHas('business', function ($q) use ($country, $sector) {
                $q->where('is_active', true)
                    ->where('country', $country)
                    ->when($sector, fn ($qq) => $qq->where('sector', $sector));
            })
            ->when($city, fn ($q) => $q->where('city', $city))
            ->orderBy('name')
            ->get();

        $membershipByShopId = collect();
        $frequentShops = collect();

        if ($isCustomer) {
            $membershipByShopId = Membership::query()
                ->withCount('visits')
                ->where('customer_id', $user->id)
                ->get()
                ->keyBy('shop_id');

            $frequentShops = $shops
                ->filter(fn (Shop $shop) => $membershipByShopId->has($shop->id))
                ->sortByDesc(function (Shop $shop) use ($membershipByShopId) {
                    $membership = $membershipByShopId->get($shop->id);

                    return sprintf('%08d-%08d', $membership->visits_count, $membership->points_balance);
                })
                ->values();
        }

        $rows = $this->buildRows($shops, $frequentShops, $sector, $interests, $city);

        return view('discover.index', [
            'rows' => $rows,
            'membershipByShopId' => $membershipByShopId,
            'isCustomer' => $isCustomer,
            'sectors' => Sectors::OPTIONS,
            'countries' => Countries::OPTIONS,
            'cities' => Countries::cities($country),
            'activeCountry' => $country,
            'activeCity' => $city,
            'activeSector' => $sector,
            'interests' => $interests,
        ]);
    }

    public function show(Business $business): View
    {
        abort_unless($business->is_active, 404);

        $user = request()->user();
        $isCustomer = $user?->isCustomer() ?? false;
        $memberships = collect();

        if ($isCustomer) {
            $memberships = Membership::query()
                ->where('customer_id', $user->id)
                ->where('business_id', $business->id)
                ->with('shop')
                ->get()
                ->keyBy('shop_id');
        }

        $related = Business::query()
            ->where('is_active', true)
            ->where('id', '!=', $business->id)
            ->where('country', $business->country)
            ->where(function ($q) use ($business) {
                $q->where('sector', $business->sector)
                    ->orWhere('city', $business->city);
            })
            ->with(['shops' => fn ($q) => $q->where('is_active', true)])
            ->withCount('shops')
            ->latest()
            ->take(6)
            ->get();

        return view('discover.show', [
            'business' => $business->load(['shops' => fn ($q) => $q->where('is_active', true)]),
            'campaigns' => $business->campaigns()->active()->get(),
            'rewards' => $business->rewards()->where('is_active', true)->orderBy('points_cost')->get(),
            'related' => $related,
            'sectors' => Sectors::OPTIONS,
            'isCustomer' => $isCustomer,
            'memberships' => $memberships,
        ]);
    }

    /**
     * @return list<array{key: string, title: string, shops: Collection<int, Shop>}>
     */
    private function buildRows(Collection $shops, Collection $frequentShops, ?string $sector, array $interests, ?string $city): array
    {
        $rows = [];

        if ($frequentShops->isNotEmpty()) {
            $rows[] = [
                'key' => 'frequent',
                'title' => __('loop.your_places'),
                'shops' => $frequentShops,
            ];
        }

        if ($sector) {
            $rows[] = [
                'key' => 'sector-'.$sector,
                'title' => Sectors::label($sector),
                'shops' => $shops,
            ];

            return $rows;
        }

        $bySector = $shops->groupBy(fn (Shop $shop) => $shop->business->sector);

        $orderedKeys = collect(array_keys(Sectors::OPTIONS))
            ->sortBy(function (string $key) use ($interests, $bySector) {
                if (! $bySector->has($key)) {
                    return '9-'.$key;
                }
                if (in_array($key, $interests, true)) {
                    return '0-'.$key;
                }

                return '1-'.$key;
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
                'shops' => $bySector[$key]->values(),
            ];
        }

        return $rows;
    }
}
