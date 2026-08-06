<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Shop;
use App\Support\Countries;
use App\Support\Sectors;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscoverController extends Controller
{
    public function __invoke(Request $request): View
    {
        $country = $request->query('country', $request->user()?->country ?? 'TZ');
        $city = $request->query('city', $request->user()?->city);
        $sector = $request->query('sector');
        $interests = $request->user()?->interests ?? [];

        if ($sector) {
            // explicit filter wins
        } elseif ($interests && ! $request->has('sector')) {
            // soft preference: still show all, but order interests first via grouping
        }

        $shops = Shop::query()
            ->with(['business' => fn ($q) => $q->with(['campaigns' => fn ($c) => $c->active(), 'rewards' => fn ($r) => $r->where('is_active', true)])])
            ->where('is_active', true)
            ->whereHas('business', function ($q) use ($country, $sector) {
                $q->where('is_active', true)
                    ->where('country', $country)
                    ->when($sector, fn ($qq) => $qq->where('sector', $sector));
            })
            ->when($city, fn ($q) => $q->where('city', $city))
            ->orderBy('city')
            ->orderBy('name')
            ->get();

        // Prefer interest sectors first when no explicit sector filter
        if (! $sector && $interests) {
            $shops = $shops->sortBy(function (Shop $shop) use ($interests) {
                $sectorKey = $shop->business->sector;
                $interestRank = in_array($sectorKey, $interests, true) ? 0 : 1;

                return sprintf('%d-%s-%s', $interestRank, $shop->city, $shop->name);
            })->values();
        }

        $groupedByCity = $shops->groupBy(fn (Shop $shop) => $shop->city ?: __('Other cities'));

        return view('discover.index', [
            'groupedByCity' => $groupedByCity,
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
        ]);
    }
}
