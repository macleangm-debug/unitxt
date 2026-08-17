<?php

namespace App\Http\Controllers;

use App\Models\Reward;
use App\Support\Confirm;
use App\Support\OfferTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RewardController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('campaigns.index', ['tab' => 'offers']);
    }

    public function create(Request $request): View
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();
        $earn = $business->campaigns()->where('type', 'earn')->latest()->first();
        $type = $request->query('type');
        $templateKey = $request->query('template');
        $starters = OfferTemplates::typeStarters();
        $selectedType = $type ? OfferTemplates::typeStarter($type) : null;
        $templates = OfferTemplates::forSector($business->sector ?: 'other');
        $selected = $templateKey
            ? collect($templates)->firstWhere('key', $templateKey)
            : null;

        // Type-first: once a type is chosen, open the form (optionally prefilled from a sector idea).
        $showForm = $selectedType !== null || $selected !== null || $request->boolean('own');

        return view('rewards.create', [
            'business' => $business,
            'earnCampaign' => $earn,
            'typeStarters' => $starters,
            'selectedType' => $selectedType,
            'offerTemplates' => $templates,
            'selectedTemplate' => $selected,
            'createOwn' => $request->boolean('own') || $request->has('own') || $selectedType !== null,
            'showForm' => $showForm,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();

        if ($request->filled('template_key') && ! $request->boolean('customize') && ! $request->filled('name')) {
            $catalog = collect(OfferTemplates::forSector($business->sector ?: 'other'))->keyBy('key');
            $template = $catalog->get($request->string('template_key')->toString());
            abort_unless($template, 422);

            $reward = $business->rewards()->create([
                'name' => $template['name'],
                'description' => $template['description'],
                'product_name' => $template['product_name'],
                'points_cost' => $template['points_cost'],
                'reward_type' => $template['reward_type'],
                'reward_value' => $template['reward_value'],
                'is_active' => true,
            ]);

            return redirect()->route('rewards.show', $reward)->with('confirm', Confirm::make(
                __('loop.offer_created_title'),
                __('loop.offer_created_body_named', ['name' => $reward->name]),
                __('loop.view_stats'),
                route('rewards.show', $reward),
            ));
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'product_name' => ['nullable', 'string', 'max:120'],
            'points_cost' => ['required', 'integer', 'min:1'],
            'reward_type' => ['required', 'in:percent_off,fixed_off,free_item,custom'],
            'reward_value' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'max_redemptions_per_member' => ['nullable', 'integer', 'min:1'],
        ]);

        $reward = $business->rewards()->create([
            ...$data,
            'product_sku' => null,
            'is_active' => true,
        ]);

        return redirect()->route('rewards.show', $reward)->with('confirm', Confirm::make(
            __('loop.offer_created_title'),
            __('loop.offer_created_body_named', ['name' => $reward->name]),
            __('loop.view_stats'),
            route('rewards.show', $reward),
        ));
    }

    public function show(Request $request, Reward $reward): View
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $reward->business_id === $business->id, 403);

        $redemptions = $reward->redemptions()->with(['customer', 'visit.shop', 'shop'])->latest();
        $totalRedemptions = (clone $redemptions)->count();
        $pointsSpent = (int) (clone $redemptions)->sum('points_spent');
        $recent = (clone $redemptions)->take(12)->get();
        $thisMonth = $reward->redemptions()->where('created_at', '>=', now()->startOfMonth())->count();

        return view('rewards.show', [
            'business' => $business,
            'reward' => $reward,
            'stats' => [
                'total_redemptions' => $totalRedemptions,
                'this_month' => $thisMonth,
                'points_spent' => $pointsSpent,
                'stock' => $reward->stock,
            ],
            'recentRedemptions' => $recent,
        ]);
    }

    public function edit(Request $request, Reward $reward): View
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();
        abort_unless($reward->business_id === $business->id, 403);

        return view('rewards.edit', [
            'business' => $business,
            'reward' => $reward,
        ]);
    }

    public function update(Request $request, Reward $reward): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();
        abort_unless($reward->business_id === $business->id, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'product_name' => ['nullable', 'string', 'max:120'],
            'points_cost' => ['required', 'integer', 'min:1'],
            'reward_type' => ['required', 'in:percent_off,fixed_off,free_item,custom'],
            'reward_value' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'max_redemptions_per_member' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $reward->update([
            ...$data,
            'is_active' => $request->boolean('is_active', $reward->is_active),
        ]);

        return redirect()->route('rewards.show', $reward)->with('confirm', Confirm::make(
            __('loop.offer_updated_title'),
            __('loop.offer_updated_body', ['name' => $reward->name]),
            __('loop.done'),
            route('rewards.show', $reward),
            false,
        ));
    }

    public function toggle(Request $request, Reward $reward): RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $reward->business_id === $business->id, 403);

        $reward->update([
            'is_active' => ! $reward->is_active,
        ]);
        $reward->refresh();

        $live = $reward->is_active;

        return redirect()->route('rewards.show', $reward)->with(
            'confirm',
            Confirm::withBoldName(
                $live ? __('loop.offer_resumed_title') : __('loop.offer_paused_title'),
                $live ? 'offer_resumed_body' : 'offer_paused_body',
                $reward->name,
                __('loop.done'),
                route('rewards.show', $reward),
                false,
            )
        );
    }
}
