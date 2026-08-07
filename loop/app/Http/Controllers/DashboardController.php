<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Membership;
use App\Models\Visit;
use App\Services\PlanLimitService;
use App\Services\ReferralService;
use App\Support\Plans;
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

            $activeCampaigns = $business->campaigns()->active()->withCount([
                'visits as today_visits_count' => fn ($q) => $q->whereDate('created_at', today()),
            ])->latest()->take(5)->get();

            $limits = app(PlanLimitService::class);
            if ($user->isOwner()) {
                $limits->syncTrialStatus($business->fresh());
                $business = $business->fresh();
            }

            $insights = $user->isOwner()
                ? app(\App\Services\BusinessInsightService::class)->heroBanners($business)
                : [];

            return view('dashboard.business', [
                'business' => $business,
                'shopCount' => $business->shops()->count(),
                'campaignCount' => $business->campaigns()->count(),
                'memberCount' => $business->uniqueMemberCount(),
                'visitCount' => $business->visits()->count(),
                'todayVisits' => $todayVisits,
                'todaySpend' => $todaySpend,
                'recentVisits' => $business->visits()->with(['customer', 'shop', 'recorder'])->latest()->take(8)->get(),
                'activeCampaigns' => $activeCampaigns,
                'isOwner' => $user->isOwner(),
                'heroBanners' => $insights,
                'showWelcome' => $request->session()->pull('show_welcome', false) || $request->boolean('welcome'),
                'referralProgress' => $user->isOwner()
                    ? app(ReferralService::class)->progress($business)
                    : null,
                'referralShareUrl' => $user->isOwner()
                    ? app(ReferralService::class)->shareUrl($business)
                    : null,
                'needsUpgrade' => $user->isOwner() && (
                    $limits->trialExpired($business) || $business->billing_status === 'past_due'
                    || ($business->billing_status === 'trialing' && ! Plans::isPaidPlan($business->plan_key))
                ),
                'trialExpired' => $user->isOwner() && $limits->trialExpired($business),
                'trialDaysLeft' => ($user->isOwner() && $business->trial_ends_at && $business->trial_ends_at->isFuture())
                    ? (int) now()->diffInDays($business->trial_ends_at)
                    : 0,
            ]);
        }

        $memberships = Membership::query()
            ->with(['business.shops', 'business.rewards' => fn ($q) => $q->where('is_active', true)->orderBy('points_cost')])
            ->withCount(['visits'])
            ->where('customer_id', $user->id)
            ->get()
            ->sortByDesc('visits_count')
            ->values();

        $redeemables = $memberships
            ->flatMap(function (Membership $membership) {
                return $membership->business->rewards
                    ->filter(fn ($reward) => $reward->points_cost <= $membership->points_balance)
                    ->map(fn ($reward) => [
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

        $topShops = Business::query()
            ->where('is_active', true)
            ->where('country', $country)
            ->whereHas('rewards', fn ($q) => $q->where('is_active', true))
            ->when($user->interests, fn ($q) => $q->whereIn('sector', $user->interests))
            ->with([
                'shops' => fn ($q) => $q->where('is_active', true),
                'rewards' => fn ($q) => $q->where('is_active', true)->orderBy('points_cost'),
            ])
            ->withCount(['memberships', 'shops'])
            ->get()
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
            ->with(['shops' => fn ($q) => $q->where('is_active', true)])
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

        return view('dashboard.customer', [
            'memberships' => $memberships,
            'grouped' => $memberships->groupBy(fn ($m) => $m->business->sector),
            'sectors' => Sectors::all(),
            'totalPoints' => $memberships->sum('points_balance'),
            'redeemables' => $redeemables,
            'featuredRedeem' => $redeemables->count() === 1 ? $redeemables->first() : null,
            'topShops' => $topShops,
            'otherShops' => $otherShops,
            'discover' => $topShops,
            'showWelcome' => $request->session()->pull('show_welcome', false) || ! $user->profile_completed,
        ]);
    }
}
