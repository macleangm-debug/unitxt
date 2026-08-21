<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Business;
use App\Models\Membership;
use App\Models\Visit;
use App\Services\PlanLimitService;
use App\Services\ReferralService;
use App\Support\Sectors;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->isAffiliate()) {
            return redirect()->route('affiliate.dashboard');
        }

        if ($user->isOwner()) {
            $business = $user->ownedBusiness;
            if ($business && ! $business->onboarding_completed_at) {
                return redirect()->route('onboarding.show');
            }
        }

        if ($user->isStaff()) {
            $business = $user->workplace();
            if (! $business) {
                return view('business.setup-missing');
            }

            $todayVisits = $business->visits()->whereDate('created_at', today())->count();
            $todaySpend = (float) $business->visits()->whereDate('created_at', today())->sum('amount_spent');
            $totalSpend = (float) $business->visits()->sum('amount_spent');

            $activeCampaigns = $business->campaigns()->active()->withCount([
                'visits as today_visits_count' => fn ($q) => $q->whereDate('created_at', today()),
            ])->latest()->take(5)->get();

            $limits = app(PlanLimitService::class);
            if ($user->isOwner()) {
                $limits->syncTrialStatus($business->fresh());
                $business = $business->fresh();
            }

            $salesTip = $user->isOwner()
                ? app(\App\Services\SalesTipService::class)->tipFor($business)
                : null;
            $pulse = $user->isOwner()
                ? app(\App\Services\OwnerPulseService::class)->for($business)
                : null;
            $access = app(\App\Services\LoopAccess::class);
            $phase = $user->isOwner() ? $access->phase($business) : null;

            return view('dashboard.business', [
                'business' => $business,
                'shopCount' => $business->shops()->count(),
                'campaignCount' => $business->campaigns()->count(),
                'memberCount' => $business->uniqueMemberCount(),
                'visitCount' => $business->visits()->count(),
                'totalSpend' => $totalSpend,
                'todayVisits' => $todayVisits,
                'todaySpend' => $todaySpend,
                'recentVisits' => $business->visits()->with(['customer', 'shop', 'recorder'])->latest()->take(8)->get(),
                'activeCampaigns' => $activeCampaigns,
                'isOwner' => $user->isOwner(),
                'showWelcome' => $request->session()->pull('show_welcome', false) || $request->boolean('welcome'),
                'referralProgress' => $user->isOwner()
                    ? app(ReferralService::class)->progress($business)
                    : null,
                'referralShareUrl' => $user->isOwner()
                    ? app(ReferralService::class)->shareUrl($business)
                    : null,
                'subscriptionBanner' => $user->isOwner() ? $business->subscriptionBanner() : null,
                'salesTip' => $salesTip,
                'pulse' => $pulse,
                'phase' => $phase,
                'paused' => $phase === \App\Services\LoopAccess::PHASE_PAUSED,
                'momentum' => $user->isOwner() ? $access->momentum($business) : null,
                'trialExpired' => $user->isOwner() && $limits->trialExpired($business),
                'trialDaysLeft' => ($user->isOwner() && $business->trial_ends_at && $business->trial_ends_at->isFuture())
                    ? (int) now()->diffInDays($business->trial_ends_at)
                    : 0,
            ]);
        }

        $memberships = Membership::query()
            ->with([
                'business.shops',
                'business.campaigns' => fn ($q) => $q->where('is_active', true)->latest(),
                'business.rewards' => fn ($q) => $q->where('is_active', true)->orderBy('points_cost'),
            ])
            ->withCount(['visits'])
            ->where('customer_id', $user->id)
            ->get()
            ->sortByDesc('visits_count')
            ->values();

        $memberships->each(function (Membership $membership) {
            $membership->business->setAttribute(
                'shops_count',
                $membership->business->shops?->count() ?? 0
            );
        });

        $redeemables = $memberships
            ->flatMap(function (Membership $membership) {
                return $membership->availableRewards()->map(fn ($reward) => [
                    'membership' => $membership,
                    'business' => $membership->business,
                    'reward' => $reward,
                ]);
            })
            ->take(8)
            ->values();

        $country = $user->country ?? session('preferred_country', 'TZ');
        $memberBusinessIds = $memberships->pluck('business_id');

        // Prefer shops where the customer can already redeem, then shops with live offers.
        $redeemableBusinessIds = $redeemables->pluck('business.id')->unique()->values();

        $topQuery = Business::query()
            ->where('is_active', true)
            ->where('country', $country)
            ->whereHas('rewards', fn ($q) => $q->where('is_active', true));

        $withShops = [
            'shops' => fn ($q) => $q->where('is_active', true),
            'rewards' => fn ($q) => $q->where('is_active', true)->orderBy('points_cost'),
            'campaigns' => fn ($q) => $q->where('is_active', true)->latest(),
        ];

        $topShops = (clone $topQuery)
            ->when(filled($user->interests), fn ($q) => $q->whereIn('sector', $user->interests))
            ->with($withShops)
            ->withCount(['memberships', 'shops'])
            ->get();

        if ($topShops->isEmpty() && filled($user->interests)) {
            $topShops = (clone $topQuery)
                ->with($withShops)
                ->withCount(['memberships', 'shops'])
                ->get();
        }

        $topShops = $topShops
            ->sortByDesc(function (Business $business) use ($redeemableBusinessIds, $memberBusinessIds) {
                $score = 0;
                if ($redeemableBusinessIds->contains($business->id)) {
                    $score += 100;
                }
                if ($memberBusinessIds->contains($business->id)) {
                    $score += 40;
                }
                $score += min(30, (int) $business->memberships_count);

                return $score;
            })
            ->take(8)
            ->values();

        $otherShops = Business::query()
            ->where('is_active', true)
            ->where('country', $country)
            ->whereNotIn('id', $topShops->pluck('id')->merge($memberBusinessIds))
            ->with([
                'shops' => fn ($q) => $q->where('is_active', true),
                'campaigns' => fn ($q) => $q->where('is_active', true)->latest(),
            ])
            ->withCount('shops')
            ->latest()
            ->take(8)
            ->get();

        $memberships = $memberships->map(function (Membership $membership) {
            $next = $membership->nextReward();
            $ready = $membership->nearestReadyReward();
            $target = $ready ?: $next;
            $membership->setAttribute('home_target_reward', $target);
            $membership->setAttribute('home_progress', $membership->progressTo($target));

            return $membership;
        });

        $stories = Article::query()
            ->visibleTo($user)
            ->orderByDesc('published_at')
            ->take(5)
            ->get();

        $recent = $memberships->flatMap(function (Membership $membership) {
            return $membership->groupedActivity(6)->map(function ($row) use ($membership) {
                $row->shop_name = $membership->business->name;

                return $row;
            });
        })->sortByDesc('created_at')->take(5)->values();

        return view('dashboard.customer', [
            'memberships' => $memberships,
            'grouped' => $memberships->groupBy(fn ($m) => $m->business->sector),
            'sectors' => Sectors::all(),
            'totalPoints' => $memberships->sum('points_balance'),
            'pointsEarned' => (int) $request->session()->pull('points_earned_flash', 0),
            'redeemables' => $redeemables,
            'featuredRedeem' => $redeemables->first(),
            'recent' => $recent,
            'topShops' => $topShops,
            'otherShops' => $otherShops,
            'discover' => $topShops,
            'showWelcome' => $request->session()->pull('show_welcome', false) || ! $user->profile_completed,
            'featuredStory' => $stories->first(),
            'moreStories' => $stories->skip(1)->values(),
        ]);
    }
}
