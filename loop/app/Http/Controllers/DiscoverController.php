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
    private const PER_PAGE = 24;

    private const ROW_LIMIT = 24;

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $isCustomer = $user?->isCustomer() ?? false;
        $country = $request->query('country', $user?->country ?? session('preferred_country', 'TZ'));
        $city = $request->query('city', $isCustomer ? $user?->city : null);
        $sector = $request->query('sector');
        $interests = $isCustomer ? ($user->interests ?? []) : [];

        $paginator = null;
        $membershipByBusinessId = collect();
        $frequentBusinesses = collect();

        if ($sector) {
            $paginator = $this->businessQuery($country, $city, $sector)
                ->paginate(self::PER_PAGE)
                ->withQueryString();
            $businesses = collect($paginator->items());
            $rows = [[
                'key' => 'sector-'.$sector,
                'title' => Sectors::label($sector).($city ? ' · '.$city : ''),
                'businesses' => $businesses,
                'total' => $paginator->total(),
                'see_all_url' => null,
            ]];
        } else {
            $rows = $this->buildSectorRows($country, $city, $interests);
            $businesses = collect($rows)->flatMap(fn (array $row) => $row['businesses'])->unique('id')->values();
        }

        if ($isCustomer) {
            $membershipByBusinessId = $this->membershipSummary($user->id, $businesses->pluck('id'));
            $frequentBusinesses = $this->frequentBusinesses($user->id, $country, $city);
            if ($frequentBusinesses->isNotEmpty() && ! $sector) {
                array_unshift($rows, [
                    'key' => 'frequent',
                    'title' => __('loop.your_places'),
                    'businesses' => $frequentBusinesses,
                    'total' => $frequentBusinesses->count(),
                    'see_all_url' => null,
                ]);
                $membershipByBusinessId = $membershipByBusinessId->union(
                    $this->membershipSummary($user->id, $frequentBusinesses->pluck('id'))
                );
            }
        }

        return view('discover.index', [
            'rows' => $rows,
            'paginator' => $paginator,
            'membershipByBusinessId' => $membershipByBusinessId,
            'isCustomer' => $isCustomer,
            'sectors' => Sectors::all(),
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
        abort_unless(app(\App\Services\PlanLimitService::class)->visibleOnDiscover($business), 404);

        $user = request()->user();
        $isCustomer = $user?->isCustomer() ?? false;
        $memberships = collect();
        $totalPoints = null;

        if ($isCustomer) {
            $memberships = Membership::query()
                ->where('customer_id', $user->id)
                ->where('business_id', $business->id)
                ->with('shop')
                ->get()
                ->keyBy('shop_id');
            $totalPoints = $memberships->sum('points_balance');
        }

        $related = Business::query()
            ->where('is_active', true)
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
            'isCustomer' => $isCustomer,
            'memberships' => $memberships,
            'totalPoints' => $totalPoints,
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Business>
     */
    private function businessQuery(string $country, ?string $city, ?string $sector = null)
    {
        $billing = \App\Support\BillingSettings::settings();
        $graceDays = (int) $billing['grace_days'];
        $hideUnpaid = (bool) $billing['hide_from_discover_when_unpaid'];

        return Business::query()
            ->where('is_active', true)
            ->where('country', $country)
            ->when($sector, fn ($q) => $q->where('sector', $sector))
            ->where('billing_status', '!=', 'suspended')
            ->when($hideUnpaid, function ($q) use ($graceDays) {
                $q->where(function ($inner) use ($graceDays) {
                    $inner->whereIn('billing_status', ['active', 'trialing', 'free'])
                        ->orWhere(function ($past) use ($graceDays) {
                            $past->where('billing_status', 'past_due')
                                ->where(function ($grace) use ($graceDays) {
                                    $grace->whereNull('past_due_at')
                                        ->orWhere('past_due_at', '>', now()->subDays($graceDays));
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
                'rewards' => fn ($r) => $r->where('is_active', true),
            ])
            ->withCount(['shops' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name');
    }

    /**
     * @param  list<string>  $interests
     * @return list<array{key: string, title: string, businesses: Collection<int, Business>, total: int, see_all_url: ?string}>
     */
    private function buildSectorRows(string $country, ?string $city, array $interests): array
    {
        $present = $this->businessQuery($country, $city)
            ->reorder()
            ->select('sector')
            ->selectRaw('count(*) as aggregate')
            ->groupBy('sector')
            ->pluck('aggregate', 'sector');

        $orderedKeys = collect(array_keys(Sectors::all()))
            ->filter(fn (string $key) => (int) ($present[$key] ?? 0) > 0)
            ->sortBy(function (string $key) use ($interests) {
                if (in_array($key, $interests, true)) {
                    return '0-'.$key;
                }

                return '1-'.$key;
            })
            ->values();

        $rows = [];
        foreach ($orderedKeys as $key) {
            $total = (int) $present[$key];
            $businesses = $this->businessQuery($country, $city, $key)->limit(self::ROW_LIMIT)->get();
            $title = Sectors::label($key);
            if ($city) {
                $title = $title.' · '.$city;
            }

            $rows[] = [
                'key' => 'sector-'.$key,
                'title' => $title,
                'businesses' => $businesses,
                'total' => $total,
                'see_all_url' => $total > self::ROW_LIMIT
                    ? route('discover', array_filter([
                        'country' => $country,
                        'city' => $city,
                        'sector' => $key,
                    ]))
                    : null,
            ];
        }

        return $rows;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int|string>  $businessIds
     * @return Collection<int|string, object>
     */
    private function membershipSummary(int $customerId, Collection $businessIds): Collection
    {
        if ($businessIds->isEmpty()) {
            return collect();
        }

        return Membership::query()
            ->withCount('visits')
            ->where('customer_id', $customerId)
            ->whereIn('business_id', $businessIds)
            ->get()
            ->groupBy('business_id')
            ->map(fn (Collection $group) => (object) [
                'points_balance' => $group->sum('points_balance'),
                'visits_count' => $group->sum('visits_count'),
            ]);
    }

    /**
     * @return Collection<int, Business>
     */
    private function frequentBusinesses(int $customerId, string $country, ?string $city): Collection
    {
        $rankedIds = Membership::query()
            ->withCount('visits')
            ->where('customer_id', $customerId)
            ->get()
            ->groupBy('business_id')
            ->map(fn (Collection $group) => (object) [
                'visits_count' => $group->sum('visits_count'),
                'points_balance' => $group->sum('points_balance'),
            ])
            ->sortByDesc(fn ($m) => sprintf('%08d-%08d', $m->visits_count, $m->points_balance))
            ->keys()
            ->take(self::ROW_LIMIT)
            ->values();

        if ($rankedIds->isEmpty()) {
            return collect();
        }

        $businesses = $this->businessQuery($country, $city)
            ->whereIn('id', $rankedIds)
            ->get()
            ->keyBy('id');

        return $rankedIds
            ->map(fn ($id) => $businesses->get($id))
            ->filter()
            ->values();
    }
}
