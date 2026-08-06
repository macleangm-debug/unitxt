<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Reward;
use App\Support\CampaignTemplates;
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
            'templates' => CampaignTemplates::all(),
        ]);
    }

    public function create(Request $request): View
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->canManageCampaigns(), 403);

        $templateKey = $request->query('template');
        $template = CampaignTemplates::all()[$templateKey] ?? null;

        return view('campaigns.create', [
            'business' => $business,
            'shops' => $business->shops()->where('is_active', true)->orderBy('name')->get(),
            'templates' => CampaignTemplates::all(),
            'template' => $template,
            'templateKey' => $templateKey,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->canManageCampaigns(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:earn,birthday,welcome,product_push,streak'],
            'description' => ['nullable', 'string', 'max:1000'],
            'spend_step' => ['nullable', 'integer', 'min:1'],
            'points_per_step' => ['nullable', 'integer', 'min:1'],
            'bonus_points' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'shop_ids' => ['nullable', 'array'],
            'shop_ids.*' => ['integer', 'exists:shops,id'],
            'template_key' => ['nullable', 'string'],
            'create_reward' => ['nullable', 'boolean'],
            'reward_name' => ['nullable', 'string', 'max:120'],
            'reward_points_cost' => ['nullable', 'integer', 'min:1'],
            'reward_type' => ['nullable', 'in:percent_off,fixed_off,free_item,custom'],
            'reward_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($data['type'] === 'earn') {
            $request->validate([
                'spend_step' => ['required', 'integer', 'min:1'],
                'points_per_step' => ['required', 'integer', 'min:1'],
            ]);
        }

        $campaign = $business->campaigns()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'spend_step' => $data['spend_step'] ?? null,
            'points_per_step' => $data['points_per_step'] ?? null,
            'bonus_points' => $data['bonus_points'] ?? 0,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => true,
            'template_key' => $data['template_key'] ?? null,
        ]);

        $shopIds = collect($data['shop_ids'] ?? [])
            ->filter(fn ($id) => $business->shops()->whereKey($id)->exists())
            ->values()
            ->all();
        $campaign->shops()->sync($shopIds);

        if ($request->boolean('create_reward') && ! empty($data['reward_name'])) {
            Reward::create([
                'business_id' => $business->id,
                'name' => $data['reward_name'],
                'points_cost' => $data['reward_points_cost'] ?? 100,
                'reward_type' => $data['reward_type'] ?? 'percent_off',
                'reward_value' => $data['reward_value'] ?? 5,
                'is_active' => true,
            ]);
        }

        return redirect()->route('campaigns.index')->with('status', 'Campaign launched.');
    }

    public function show(Request $request, Campaign $campaign): View
    {
        $this->authorizeOwner($request, $campaign);
        $business = $campaign->business;

        $visits = $campaign->visits();
        $totalVisits = (clone $visits)->count();
        $totalSpend = (float) (clone $visits)->sum('amount_spent');
        $pointsAwarded = (int) (clone $visits)->sum('points_earned');

        return view('campaigns.show', [
            'campaign' => $campaign->load('shops'),
            'business' => $business,
            'stats' => [
                'today_visits' => $campaign->visits()->whereDate('created_at', today())->count(),
                'total_visits' => $totalVisits,
                'total_spend' => $totalSpend,
                'points_awarded' => $pointsAwarded,
                'avg_ticket' => $totalVisits > 0 ? $totalSpend / $totalVisits : 0,
            ],
            'recentVisits' => $campaign->visits()->with(['customer', 'shop'])->latest()->take(10)->get(),
        ]);
    }

    public function edit(Request $request, Campaign $campaign): View
    {
        $this->authorizeOwner($request, $campaign);

        return view('campaigns.edit', [
            'campaign' => $campaign->load('shops'),
            'business' => $campaign->business,
            'shops' => $campaign->business->shops()->orderBy('name')->get(),
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
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => $request->boolean('is_active', $campaign->is_active),
        ]);

        $shopIds = collect($data['shop_ids'] ?? [])
            ->filter(fn ($id) => $business->shops()->whereKey($id)->exists())
            ->values()
            ->all();
        $campaign->shops()->sync($shopIds);

        return redirect()->route('campaigns.index')->with('status', 'Campaign updated.');
    }

    public function destroy(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorizeOwner($request, $campaign);
        $campaign->delete();

        return redirect()->route('campaigns.index')->with('status', 'Campaign deleted.');
    }

    private function authorizeOwner(Request $request, Campaign $campaign): void
    {
        abort_unless($request->user()->ownedBusiness?->id === $campaign->business_id, 403);
    }
}
