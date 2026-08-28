<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Membership;
use App\Models\User;
use App\Support\Sectors;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DiscoverCatalog
{
    public const PER_PAGE = 24;

    public const HOME_LIMIT = 6;

    /**
     * @param  array{country: string, city?: ?string, sector?: ?string, category?: ?string, q?: string, status?: ?string}  $filters
     */
    public function paginate(array $filters, ?User $user): LengthAwarePaginator
    {
        $query = $this->baseQuery($filters, $user);
        $this->applyRanking($query, $filters, $user);

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * @param  array{country: string, city?: ?string, exclude?: list<int>}  $filters
     * @return Collection<int, Business>
     */
    public function take(array $filters, int $limit = self::HOME_LIMIT): Collection
    {
        $query = $this->baseQuery($filters, null);
        $exclude = $filters['exclude'] ?? [];
        if ($exclude !== []) {
            $query->whereNotIn('id', $exclude);
        }
        if (($filters['order'] ?? null) === 'popular') {
            $query->orderByDesc('memberships_count')->orderBy('name');
        } elseif (($filters['order'] ?? null) === 'new') {
            $query->latest('id');
        } else {
            $query->orderByDesc('memberships_count')->orderBy('name');
        }

        return $query->take($limit)->get();
    }

    /**
     * @return Collection<int, Business>
     */
    public function memberPlaces(User $user, int $limit = self::HOME_LIMIT): Collection
    {
        $ids = Membership::query()
            ->where('customer_id', $user->id)
            ->withCount('visits')
            ->orderByDesc('visits_count')
            ->orderByDesc('points_balance')
            ->limit($limit)
            ->pluck('business_id');

        if ($ids->isEmpty()) {
            return collect();
        }

        $query = Business::query();
        app(LoopAccess::class)->constrainPromoted($query);

        return $query
            ->whereIn('id', $ids)
            ->with($this->eager())
            ->withCount(['shops' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->sortBy(fn (Business $business) => array_search($business->id, $ids->all(), true))
            ->values();
    }

    /**
     * @param  array{country: string, city?: ?string, sector?: ?string, category?: ?string, q?: string, status?: ?string}  $filters
     */
    public function baseQuery(array $filters, ?User $user): Builder
    {
        $country = $filters['country'];
        $city = $filters['city'] ?? null;
        $sector = $filters['sector'] ?? null;
        $category = $filters['category'] ?? null;
        $search = trim((string) ($filters['q'] ?? ''));
        $status = $filters['status'] ?? null;

        $query = Business::query();
        app(LoopAccess::class)->constrainPromoted($query);

        $query
            ->where('country', $country)
            ->when($sector, fn ($q) => $q->where('sector', $sector))
            ->when($category && ! $sector, function ($q) use ($category) {
                $keys = Sectors::keysInCategory($category);
                if ($keys !== []) {
                    $q->whereIn('sector', $keys);
                }
            })
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $sectorKeys = Sectors::keysMatching($search);
                $q->where(function ($inner) use ($like, $sectorKeys) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('city', 'like', $like)
                        ->orWhereHas('shops', function ($shops) use ($like) {
                            $shops->where('is_active', true)
                                ->where(function ($shop) use ($like) {
                                    $shop->where('name', 'like', $like)
                                        ->orWhere('city', 'like', $like);
                                });
                        });
                    if ($sectorKeys !== []) {
                        $inner->orWhereIn('sector', $sectorKeys);
                    }
                });
            })
            ->whereHas('shops', function ($q) use ($city) {
                $q->where('is_active', true)
                    ->when($city, fn ($qq) => $qq->where('city', $city));
            })
            ->with($this->eager())
            ->withCount([
                'shops' => fn ($q) => $q->where('is_active', true),
                'memberships',
            ]);

        if ($user?->isCustomer() && in_array($status, ['ready', 'almost', 'offers'], true)) {
            $ids = $this->statusBusinessIds($user, $status);
            if ($ids === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $ids);
            }
        }

        return $query;
    }

    /**
     * @param  array{q?: string}  $filters
     */
    private function applyRanking(Builder $query, array $filters, ?User $user): void
    {
        $search = trim((string) ($filters['q'] ?? ''));
        $cases = ['CASE'];
        $bindings = [];
        $rank = 0;

        if ($user?->isCustomer()) {
            $signals = $this->memberSignals($user);
            if ($signals['member'] !== []) {
                $cases[] = 'WHEN id IN ('.$this->placeholders($signals['member']).') THEN '.$rank;
                $bindings = array_merge($bindings, $signals['member']);
                $rank++;
            }
            if ($signals['ready'] !== []) {
                $cases[] = 'WHEN id IN ('.$this->placeholders($signals['ready']).') THEN '.$rank;
                $bindings = array_merge($bindings, $signals['ready']);
                $rank++;
            }
            if ($signals['almost'] !== []) {
                $cases[] = 'WHEN id IN ('.$this->placeholders($signals['almost']).') THEN '.$rank;
                $bindings = array_merge($bindings, $signals['almost']);
                $rank++;
            }
        }

        $cases[] = 'WHEN EXISTS (SELECT 1 FROM campaigns WHERE campaigns.business_id = businesses.id AND campaigns.is_active = 1) THEN '.$rank;
        $rank++;

        if ($search !== '') {
            $cases[] = 'WHEN name LIKE ? THEN '.$rank;
            $bindings[] = addcslashes($search, '%_\\').'%';
            $rank++;
            $cases[] = 'WHEN name LIKE ? THEN '.$rank;
            $bindings[] = '%'.addcslashes($search, '%_\\').'%';
            $rank++;
        }

        if ($user?->isCustomer() && filled($user->interests)) {
            $cases[] = 'WHEN sector IN ('.$this->placeholders($user->interests).') THEN '.$rank;
            $bindings = array_merge($bindings, $user->interests);
            $rank++;
        }

        if ($user?->isCustomer() && filled($user->city)) {
            $cases[] = 'WHEN city = ? THEN '.$rank;
            $bindings[] = $user->city;
        }

        $query->orderByRaw(implode(' ', $cases).' ELSE 20 END', $bindings)
            ->orderByDesc('memberships_count')
            ->orderBy('name');
    }

    /**
     * @return array{member: list<int>, ready: list<int>, almost: list<int>, offers: list<int>}
     */
    public function memberSignals(User $user): array
    {
        $memberships = Membership::query()
            ->with([
                'business.rewards' => fn ($q) => $q->where('is_active', true)->orderBy('points_cost'),
                'business.campaigns' => fn ($q) => $q->where('is_active', true),
            ])
            ->where('customer_id', $user->id)
            ->limit(200)
            ->get();

        $member = [];
        $ready = [];
        $almost = [];
        $offers = [];

        foreach ($memberships as $membership) {
            $member[] = (int) $membership->business_id;
            if ($membership->availableRewards()->isNotEmpty()) {
                $ready[] = (int) $membership->business_id;
            }
            $next = $membership->nextReward();
            if ($next && ($membership->progressTo($next)['percent'] ?? 0) >= 70) {
                $almost[] = (int) $membership->business_id;
            }
            if ($membership->business?->campaigns?->isNotEmpty()) {
                $offers[] = (int) $membership->business_id;
            }
        }

        return [
            'member' => array_values(array_unique($member)),
            'ready' => array_values(array_unique($ready)),
            'almost' => array_values(array_unique($almost)),
            'offers' => array_values(array_unique($offers)),
        ];
    }

    /**
     * @return list<int>
     */
    private function statusBusinessIds(User $user, string $status): array
    {
        $signals = $this->memberSignals($user);

        return match ($status) {
            'ready' => $signals['ready'],
            'almost' => $signals['almost'],
            'offers' => $signals['offers'],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function eager(): array
    {
        return [
            'shops' => fn ($q) => $q->where('is_active', true),
            'campaigns' => fn ($c) => $c->active(),
            'rewards' => fn ($r) => $r->where('is_active', true)->orderBy('points_cost'),
        ];
    }

    /**
     * @param  list<int|string>  $ids
     */
    private function placeholders(array $ids): string
    {
        return implode(',', array_fill(0, count($ids), '?'));
    }
}
