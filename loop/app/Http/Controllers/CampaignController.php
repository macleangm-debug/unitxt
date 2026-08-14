<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Support\CampaignTemplates;
use App\Support\Confirm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->canManageCampaigns(), 403);

        return view('campaigns.index', [
            'business' => $business,
            'campaigns' => $business->campaigns()->with('shops')->latest()->get(),
            'rewards' => $business->rewards()->latest()->get(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->canManageCampaigns(), 403);

        $offers = $business->rewards()->where('is_active', true)->orderBy('points_cost')->get()
            ->filter(fn ($r) => $r->isAvailable())
            ->values();
        if ($offers->isEmpty()) {
            \App\Support\DefaultOffer::ensure($business);
            $offers = $business->rewards()->where('is_active', true)->orderBy('points_cost')->get()
                ->filter(fn ($r) => $r->isAvailable())
                ->values();
        }
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

        $usedKeys = $business->campaigns()->whereNotNull('template_key')->pluck('template_key')->all();
        $usedKeys = array_map(
            fn ($k) => in_array($k, ['hundred_point_discount', 'earn_with_discount'], true) ? 'everyday_earn' : $k,
            $usedKeys
        );

        $templateKey = $request->query('template');
        if (in_array($templateKey, ['hundred_point_discount', 'earn_with_discount'], true)) {
            $templateKey = 'everyday_earn';
        }
        $template = $templateKey ? CampaignTemplates::localized($templateKey) : null;
        $own = $request->boolean('own');

        return view('campaigns.create', [
            'business' => $business,
            'shops' => $business->shops()->where('is_active', true)->orderBy('name')->get(),
            'offers' => $offers,
            'groupedTemplates' => CampaignTemplates::grouped($usedKeys),
            'template' => $template,
            'templateKey' => $templateKey,
            'createOwn' => $own || ($templateKey === null && $request->has('own')),
            'picking' => ! $template && ! $own && ! $request->has('own'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->canManageCampaigns(), 403);

        if (! $business->hasRedeemableOffer()) {
            \App\Support\DefaultOffer::ensure($business);
        }
        if (! $business->fresh()->hasRedeemableOffer()) {
            return redirect()->route('rewards.create')->withErrors([
                'offer' => __('loop.need_offer_first_body'),
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:earn,product_push'],
            'description' => ['nullable', 'string', 'max:1000'],
            'spend_step' => ['required', 'integer', 'min:1'],
            'points_per_step' => ['required', 'integer', 'min:1'],
            'bonus_points' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'shop_ids' => ['nullable', 'array'],
            'shop_ids.*' => ['integer', 'exists:shops,id'],
            'template_key' => ['nullable', 'string'],
            'enable_welcome' => ['nullable', 'boolean'],
            'welcome_points' => ['nullable', 'integer', 'min:1'],
            'enable_birthday' => ['nullable', 'boolean'],
            'birthday_points' => ['nullable', 'integer', 'min:1'],
            'enable_streak' => ['nullable', 'boolean'],
            'streak_target' => ['nullable', 'integer', 'min:2', 'max:30'],
            'streak_period' => ['nullable', 'in:week,month'],
            'streak_points' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($request->boolean('enable_streak')) {
            $request->validate([
                'streak_target' => ['required', 'integer', 'min:2'],
                'streak_period' => ['required', 'in:week,month'],
                'streak_points' => ['required', 'integer', 'min:1'],
            ]);
        }

        $templateKey = $data['template_key'] ?? null;
        if (in_array($templateKey, ['hundred_point_discount', 'earn_with_discount'], true)) {
            $templateKey = 'everyday_earn';
        }
        $localized = $templateKey ? CampaignTemplates::localized($templateKey) : null;

        $campaign = $business->campaigns()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'description' => $data['description'] ?? ($localized['description'] ?? null),
            'spend_step' => $data['spend_step'],
            'points_per_step' => $data['points_per_step'],
            'bonus_points' => $data['bonus_points'] ?? 0,
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

        if ($request->boolean('enable_welcome')) {
            $business->campaigns()->create([
                'name' => __('loop.type_welcome'),
                'type' => 'welcome',
                'bonus_points' => $data['welcome_points'] ?? 20,
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'] ?? null,
                'is_active' => true,
                'template_key' => 'welcome_bonus',
            ]);
        }

        if ($request->boolean('enable_birthday')) {
            $business->campaigns()->create([
                'name' => __('loop.type_birthday'),
                'type' => 'birthday',
                'bonus_points' => $data['birthday_points'] ?? 50,
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'] ?? null,
                'is_active' => true,
                'template_key' => 'birthday_treat',
            ]);
        }

        if ($request->boolean('enable_streak')) {
            $business->campaigns()->create([
                'name' => __('loop.type_streak'),
                'type' => 'streak',
                'bonus_points' => $data['streak_points'] ?? 30,
                'streak_target' => $data['streak_target'] ?? 3,
                'streak_period' => $data['streak_period'] ?? 'week',
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'] ?? null,
                'is_active' => true,
                'template_key' => ($data['streak_period'] ?? 'week') === 'month' ? 'monthly_streak' : 'visit_streak',
            ]);
        }

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
        $recentVisits = $campaign->visits()
            ->with(['customer', 'shop'])
            ->latest()
            ->paginate(15, ['*'], 'sales_page')
            ->withQueryString();


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

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:earn,birthday,welcome,product_push,streak'],
            'description' => ['nullable', 'string', 'max:1000'],
            'spend_step' => ['nullable', 'integer', 'min:1'],
            'points_per_step' => ['nullable', 'integer', 'min:1'],
            'bonus_points' => ['nullable', 'integer', 'min:0'],
            'featured_product_name' => ['nullable', 'string', 'max:120'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'shop_ids' => ['nullable', 'array'],
            'shop_ids.*' => ['integer', 'exists:shops,id'],
        ]);

        $campaign->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'spend_step' => $data['spend_step'] ?? null,
            'points_per_step' => $data['points_per_step'] ?? null,
            'bonus_points' => $data['bonus_points'] ?? 0,
            'featured_product_name' => $data['featured_product_name'] ?? $campaign->featured_product_name,
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
