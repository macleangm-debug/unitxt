<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\PlanLimitService;
use App\Support\CampaignTemplates;
use App\Support\Confirm;
use App\Support\FeatureFlags;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->canManageCampaigns(), 403);

        $campaigns = $business->campaigns()
            ->with('shops')
            ->withCount('visits')
            ->withSum('visits', 'amount_spent')
            ->latest()
            ->get()
            ->sortBy(fn ($campaign) => $campaign->isMain() ? 0 : 1)
            ->values();
        $rewards = $business->rewards()
            ->withCount('redemptions')
            ->withSum('redemptions', 'points_spent')
            ->latest()
            ->get();

        return view('campaigns.index', [
            'business' => $business,
            'campaigns' => $campaigns,
            'rewards' => $rewards,
            'campaignStats' => [
                'live' => $campaigns->filter(fn ($campaign) => $campaign->isCurrentlyActive())->count(),
                'sales' => (int) $campaigns->sum('visits_count'),
                'spend' => (float) $campaigns->sum('visits_sum_amount_spent'),
            ],
            'offerStats' => [
                'live' => $rewards->where('is_active', true)->count(),
                'redemptions' => (int) $rewards->sum('redemptions_count'),
                'points_spent' => (int) $rewards->sum('redemptions_sum_points_spent'),
            ],
            'tab' => in_array($request->query('tab'), ['offers'], true) ? 'offers' : 'campaigns',
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->canManageCampaigns(), 403);

        $offers = $business->rewards()->where('is_active', true)->orderBy('points_cost')->get();
        if ($offers->isEmpty()) {
            return redirect()
                ->route('rewards.create')
                ->with('confirm', Confirm::make(
                    __('loop.need_offer_first_title'),
                    __('loop.need_offer_first_body'),
                    __('loop.add_offer'),
                    route('rewards.create'),
                    false,
                ));
        }

        $templateKey = $request->query('template');
        if (in_array($templateKey, ['hundred_point_discount', 'earn_with_discount', 'faster_earn'], true)) {
            $templateKey = 'everyday_earn';
        }
        $template = $templateKey ? CampaignTemplates::localized($templateKey) : null;
        $usedTypes = $business->campaigns()->pluck('type')->unique()->values()->all();
        $limits = app(PlanLimitService::class);
        $canAddProductPush = $limits->canAddProductPush($business);

        if ($template && CampaignTemplates::isUniqueType($template['type']) && in_array($template['type'], $usedTypes, true)) {
            return redirect()
                ->route('campaigns.create')
                ->withErrors(['type' => $template['type'] === 'earn'
                    ? __('loop.only_one_main_campaign')
                    : __('loop.only_one_bonus_of_type', ['type' => __('loop.type_'.$template['type'])])]);
        }

        if ($template && $template['type'] === 'product_push' && ! $canAddProductPush) {
            return redirect()
                ->route('campaigns.create')
                ->withErrors(['plan' => $limits->productPushLimitMessage($business)]);
        }

        return view('campaigns.create', [
            'business' => $business,
            'shops' => $business->shops()->where('is_active', true)->orderBy('name')->get(),
            'offers' => $offers,
            'pickerGroups' => CampaignTemplates::picker($usedTypes, $canAddProductPush),
            'template' => $template,
            'templateKey' => $templateKey,
            'picking' => $template === null,
            'productPushCapped' => ! $canAddProductPush,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->canManageCampaigns(), 403);

        if ($business->rewards()->where('is_active', true)->doesntExist()) {
            return redirect()->route('rewards.create')->withErrors([
                'offer' => __('loop.need_offer_first_body'),
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:earn,product_push,birthday,welcome,streak'],
            'description' => ['nullable', 'string', 'max:1000'],
            'spend_step' => ['nullable', 'integer', 'min:1'],
            'points_per_step' => ['nullable', 'integer', 'min:1'],
            'bonus_points' => ['nullable', 'integer', 'min:0'],
            'featured_product_name' => ['nullable', 'string', 'max:120'],
            'streak_target' => ['nullable', 'integer', 'min:2', 'max:30'],
            'streak_period' => ['nullable', 'in:week,month'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'shop_ids' => ['nullable', 'array'],
            'shop_ids.*' => ['integer', 'exists:shops,id'],
            'template_key' => ['nullable', 'string'],
        ]);

        $type = $data['type'];
        abort_unless(FeatureFlags::allowsCampaignType($type), 403);

        if ($type === 'earn') {
            $request->validate([
                'spend_step' => ['required', 'integer', 'min:1'],
                'points_per_step' => ['required', 'integer', 'min:1'],
            ]);
            $data['spend_step'] = (int) $request->input('spend_step');
            $data['points_per_step'] = (int) $request->input('points_per_step');
        }

        if ($type === 'product_push') {
            $request->validate([
                'featured_product_name' => ['required', 'string', 'max:120'],
                'bonus_points' => ['required', 'integer', 'min:1'],
            ]);
            $data['featured_product_name'] = $request->input('featured_product_name');
            $data['bonus_points'] = (int) $request->input('bonus_points');
        }

        if (in_array($type, ['birthday', 'welcome'], true)) {
            $request->validate([
                'bonus_points' => ['required', 'integer', 'min:1'],
            ]);
            $data['bonus_points'] = (int) $request->input('bonus_points');
        }

        if ($type === 'streak') {
            $request->validate([
                'bonus_points' => ['required', 'integer', 'min:1'],
                'streak_target' => ['required', 'integer', 'min:2'],
                'streak_period' => ['required', 'in:week,month'],
            ]);
            $data['bonus_points'] = (int) $request->input('bonus_points');
            $data['streak_target'] = (int) $request->input('streak_target');
            $data['streak_period'] = $request->input('streak_period');
        }

        if (CampaignTemplates::isUniqueType($type) && $business->campaigns()->where('type', $type)->exists()) {
            return back()->withErrors([
                'type' => $type === 'earn'
                    ? __('loop.only_one_main_campaign')
                    : __('loop.only_one_bonus_of_type', ['type' => __('loop.type_'.$type)]),
            ])->withInput();
        }

        if ($type === 'product_push') {
            $limits = app(PlanLimitService::class);
            if (! $limits->canAddProductPush($business)) {
                return back()->withErrors([
                    'plan' => $limits->productPushLimitMessage($business),
                ])->withInput();
            }
        }

        $templateKey = $data['template_key'] ?? null;
        if (in_array($templateKey, ['hundred_point_discount', 'earn_with_discount', 'faster_earn'], true)) {
            $templateKey = 'everyday_earn';
        }
        $localized = $templateKey ? CampaignTemplates::localized($templateKey) : null;

        $campaign = $business->campaigns()->create([
            'name' => $data['name'],
            'type' => $type,
            'description' => $data['description'] ?? ($localized['description'] ?? null),
            'spend_step' => $type === 'earn' ? $data['spend_step'] : null,
            'points_per_step' => $type === 'earn' ? $data['points_per_step'] : null,
            'bonus_points' => $data['bonus_points'] ?? 0,
            'featured_product_name' => $type === 'product_push'
                ? ($data['featured_product_name'] ?? null)
                : null,
            'streak_target' => $type === 'streak' ? ($data['streak_target'] ?? null) : null,
            'streak_period' => $type === 'streak' ? ($data['streak_period'] ?? null) : null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => true,
            'template_key' => $templateKey,
        ]);

        $shopIds = collect($data['shop_ids'] ?? [])
            ->filter(fn ($id) => $business->shops()->whereKey($id)->exists())
            ->values()
            ->all();
        $campaign->shops()->sync($shopIds);

        return redirect()->route('campaigns.show', $campaign)->with(
            'confirm',
            Confirm::withBoldName(
                __('loop.campaign_launched_title'),
                'campaign_launched_body',
                $campaign->displayName(),
                __('loop.done'),
                route('campaigns.show', $campaign),
                true,
            )
        );
    }

    public function show(Request $request, Campaign $campaign): View
    {
        $this->authorizeOwner($request, $campaign);
        $business = $campaign->business;

        $visits = $campaign->visits();
        $totalVisits = (clone $visits)->count();
        $totalSpend = (float) (clone $visits)->sum('amount_spent');
        $pointsAwarded = (int) (clone $visits)->sum('points_earned');
        $recentVisits = $campaign->visits()->with(['customer', 'shop'])->latest()->take(10)->get();

        return view('campaigns.show', [
            'campaign' => $campaign->load(['shops']),
            'business' => $business,
            'stats' => [
                'today_visits' => $campaign->visits()->whereDate('created_at', today())->count(),
                'total_visits' => $totalVisits,
                'total_spend' => $totalSpend,
                'points_awarded' => $pointsAwarded,
                'avg_ticket' => $totalVisits > 0 ? $totalSpend / $totalVisits : 0,
            ],
            'recentVisits' => $recentVisits,
        ]);
    }

    public function edit(Request $request, Campaign $campaign): View
    {
        $this->authorizeOwner($request, $campaign);

        return view('campaigns.edit', [
            'campaign' => $campaign->load(['shops']),
            'business' => $campaign->business,
            'shops' => $campaign->business->shops()->orderBy('name')->get(),
            'template' => null,
            'templateKey' => null,
        ]);
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorizeOwner($request, $campaign);
        $business = $campaign->business;

        $type = (string) $request->input('type', $campaign->type);
        if ($type !== $campaign->type) {
            abort_unless(FeatureFlags::allowsCampaignType($type), 403);
        }
        $isEarn = $type === 'earn';
        $isBonus = in_array($type, ['birthday', 'welcome', 'streak'], true);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:earn,birthday,welcome,product_push,streak'],
            'description' => ['nullable', 'string', 'max:1000'],
            'spend_step' => [$isEarn ? 'required' : 'nullable', 'integer', 'min:1'],
            'points_per_step' => [$isEarn ? 'required' : 'nullable', 'integer', 'min:1'],
            'bonus_points' => [$isBonus || $type === 'product_push' ? 'required' : 'nullable', 'integer', 'min:0'],
            'featured_product_name' => [$type === 'product_push' ? 'required' : 'nullable', 'string', 'max:120'],
            'streak_target' => [$type === 'streak' ? 'required' : 'nullable', 'integer', 'min:2', 'max:30'],
            'streak_period' => [$type === 'streak' ? 'required' : 'nullable', 'in:week,month'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'shop_ids' => ['nullable', 'array'],
            'shop_ids.*' => ['integer', 'exists:shops,id'],
        ]);

        if ($type === 'product_push') {
            $request->validate([
                'bonus_points' => ['required', 'integer', 'min:1'],
            ]);
        }

        if ($isBonus) {
            $request->validate([
                'bonus_points' => ['required', 'integer', 'min:1'],
            ]);
        }

        $campaign->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'spend_step' => $isEarn ? ($data['spend_step'] ?? null) : null,
            'points_per_step' => $isEarn ? ($data['points_per_step'] ?? null) : null,
            'bonus_points' => $data['bonus_points'] ?? 0,
            'featured_product_name' => $data['featured_product_name'] ?? $campaign->featured_product_name,
            'streak_target' => $type === 'streak' ? ($data['streak_target'] ?? $campaign->streak_target) : $campaign->streak_target,
            'streak_period' => $type === 'streak' ? ($data['streak_period'] ?? $campaign->streak_period) : $campaign->streak_period,
            'starts_at' => $data['starts_at'],
            'ends_at' => array_key_exists('ends_at', $data) ? ($data['ends_at'] ?? null) : $campaign->ends_at,
            'is_active' => $request->boolean('is_active', $campaign->is_active),
        ]);

        if (array_key_exists('shop_ids', $data)) {
            $shopIds = collect($data['shop_ids'] ?? [])
                ->filter(fn ($id) => $business->shops()->whereKey($id)->exists())
                ->values()
                ->all();
            $campaign->shops()->sync($shopIds);
        }

        $campaign->refresh();

        return redirect()->route('campaigns.show', $campaign)->with(
            'confirm',
            Confirm::withBoldName(
                __('loop.campaign_updated_title'),
                'campaign_updated_body',
                $campaign->name,
                __('loop.done'),
                route('campaigns.show', $campaign),
                false,
            )
        );
    }

    public function toggle(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorizeOwner($request, $campaign);

        $campaign->update([
            'is_active' => ! $campaign->is_active,
        ]);
        $campaign->refresh();

        $live = $campaign->is_active;

        return redirect()->route('campaigns.show', $campaign)->with(
            'confirm',
            Confirm::withBoldName(
                $live ? __('loop.campaign_resumed_title') : __('loop.campaign_paused_title'),
                $live ? 'campaign_resumed_body' : 'campaign_paused_body',
                $campaign->name,
                __('loop.done'),
                route('campaigns.show', $campaign),
                false,
            )
        );
    }

    public function destroy(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorizeOwner($request, $campaign);
        $campaign->delete();

        return redirect()->route('campaigns.index')->with('confirm', Confirm::make(
            __('loop.campaign_deleted_title'),
            __('loop.campaign_deleted_body'),
            __('loop.back'),
            route('campaigns.index'),
            false,
        ));
    }

    private function authorizeOwner(Request $request, Campaign $campaign): void
    {
        abort_unless($request->user()->ownedBusiness?->id === $campaign->business_id, 403);
    }
}
