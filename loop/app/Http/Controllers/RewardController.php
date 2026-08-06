<?php

namespace App\Http\Controllers;

use App\Support\OfferTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RewardController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->to(route('campaigns.index').'#offers');
    }

    public function create(Request $request): View
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();
        $earn = $business->campaigns()->whereIn('type', ['earn', 'product_push'])->latest()->first();
        $templateKey = $request->query('template');
        $templates = OfferTemplates::forSector($business->sector ?: 'other');
        $selected = $templateKey
            ? collect($templates)->firstWhere('key', $templateKey)
            : null;

        return view('rewards.create', [
            'business' => $business,
            'earnCampaign' => $earn,
            'offerTemplates' => $templates,
            'selectedTemplate' => $selected,
            'createOwn' => $request->boolean('own') || $request->has('own'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();

        if ($request->filled('template_key') && ! $request->boolean('customize')) {
            $catalog = collect(OfferTemplates::forSector($business->sector ?: 'other'))->keyBy('key');
            $template = $catalog->get($request->string('template_key')->toString());
            abort_unless($template, 422);

            $business->rewards()->create([
                'name' => $template['name'],
                'description' => $template['description'],
                'product_name' => $template['product_name'],
                'points_cost' => $template['points_cost'],
                'reward_type' => $template['reward_type'],
                'reward_value' => $template['reward_value'],
                'is_active' => true,
            ]);

            return redirect()->to(route('campaigns.index').'#offers')->with('status', __('loop.offer_created'));
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'product_name' => ['nullable', 'string', 'max:120'],
            'product_sku' => ['nullable', 'string', 'max:80'],
            'points_cost' => ['required', 'integer', 'min:1'],
            'reward_type' => ['required', 'in:percent_off,fixed_off,free_item,custom'],
            'reward_value' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'max_redemptions_per_member' => ['nullable', 'integer', 'min:1'],
        ]);

        $business->rewards()->create([
            ...$data,
            'is_active' => true,
        ]);

        return redirect()->to(route('campaigns.index').'#offers')->with('status', __('loop.offer_created'));
    }
}
