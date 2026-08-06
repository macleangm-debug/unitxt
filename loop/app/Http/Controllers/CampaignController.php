<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->business()->firstOrFail();

        return view('campaigns.index', [
            'business' => $business,
            'campaigns' => $business->campaigns()->with('shops')->latest()->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $business = $request->user()->business()->firstOrFail();

        return view('campaigns.create', [
            'business' => $business,
            'shops' => $business->shops()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $business = $request->user()->business()->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'points_per_visit' => ['required', 'integer', 'min:1', 'max:10000'],
            'bonus_points' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'max_visits_per_day' => ['required', 'integer', 'min:1', 'max:20'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'shop_ids' => ['nullable', 'array'],
            'shop_ids.*' => ['integer', 'exists:shops,id'],
        ]);

        $campaign = $business->campaigns()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'points_per_visit' => $data['points_per_visit'],
            'bonus_points' => $data['bonus_points'] ?? 0,
            'max_visits_per_day' => $data['max_visits_per_day'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => true,
        ]);

        $shopIds = collect($data['shop_ids'] ?? [])
            ->filter(fn ($id) => $business->shops()->whereKey($id)->exists())
            ->values()
            ->all();

        $campaign->shops()->sync($shopIds);

        return redirect()->route('campaigns.index')->with('status', 'Campaign launched.');
    }

    public function edit(Request $request, Campaign $campaign): View
    {
        $this->authorizeCampaign($request, $campaign);
        $business = $campaign->business;

        return view('campaigns.edit', [
            'campaign' => $campaign->load('shops'),
            'business' => $business,
            'shops' => $business->shops()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorizeCampaign($request, $campaign);
        $business = $campaign->business;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'points_per_visit' => ['required', 'integer', 'min:1', 'max:10000'],
            'bonus_points' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'max_visits_per_day' => ['required', 'integer', 'min:1', 'max:20'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'shop_ids' => ['nullable', 'array'],
            'shop_ids.*' => ['integer', 'exists:shops,id'],
        ]);

        $campaign->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'points_per_visit' => $data['points_per_visit'],
            'bonus_points' => $data['bonus_points'] ?? 0,
            'max_visits_per_day' => $data['max_visits_per_day'],
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
        $this->authorizeCampaign($request, $campaign);
        $campaign->delete();

        return redirect()->route('campaigns.index')->with('status', 'Campaign deleted.');
    }

    private function authorizeCampaign(Request $request, Campaign $campaign): void
    {
        abort_unless($request->user()->business?->id === $campaign->business_id, 403);
    }
}
