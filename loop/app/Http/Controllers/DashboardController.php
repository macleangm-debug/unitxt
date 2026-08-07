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
            ->with(['business.shops'])
            ->withCount(['visits'])
            ->where('customer_id', $user->id)
            ->get()
            ->sortByDesc('visits_count')
            ->values();

        return view('dashboard.customer', [
            'memberships' => $memberships,
            'grouped' => $memberships->groupBy(fn ($m) => $m->business->sector),
            'sectors' => Sectors::OPTIONS,
            'totalPoints' => $memberships->sum('points_balance'),
            'discover' => Business::query()
                ->where('is_active', true)
                ->where('country', $user->country ?? session('preferred_country', 'TZ'))
                ->when($user->interests, fn ($q) => $q->whereIn('sector', $user->interests))
                ->with(['shops' => fn ($q) => $q->where('is_active', true)])
                ->withCount('shops')
                ->latest()
                ->take(12)
                ->get(),
            'showWelcome' => $request->session()->pull('show_welcome', false) || ! $user->profile_completed,
        ]);
    }
}
