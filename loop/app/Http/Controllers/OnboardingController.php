<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Reward;
use App\Models\Shop;
use App\Support\CampaignTemplates;
use App\Support\Countries;
use App\Support\OfferTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business, 403);

        if ($business->onboarding_completed_at) {
            return redirect()->route('dashboard');
        }

        $step = max(1, min(5, (int) $request->query('step', 1)));
        $earn = $business->campaigns()->whereIn('type', ['earn', 'product_push'])->latest()->first();

        return view('onboarding.business', [
            'business' => $business->fresh(),
            'cities' => Countries::cities($business->country),
            'groupedTemplates' => CampaignTemplates::grouped(),
            'offerTemplates' => OfferTemplates::forSector($business->sector ?: 'other'),
            'earnCampaign' => $earn,
            'existingOffers' => $business->rewards()->latest()->get(),
            'step' => $step,
            'logoJustSaved' => (bool) $request->session()->pull('logo_just_saved', false),
        ]);
    }

    public function logo(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();

        $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);

        $path = $request->file('logo')->store('business-logos', 'public');
        $business->update(['logo_path' => $path]);

        return redirect()
            ->route('onboarding.show', ['step' => 2])
            ->with('logo_just_saved', true);
    }

    public function branches(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();

        $data = $request->validate([
            'branch_count' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $business->update(['branch_count' => $data['branch_count']]);

        return redirect()->route('onboarding.show', ['step' => 3]);
    }

    public function shop(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();

        $data = $request->validate([
            'shop_name' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        if ($business->shops()->doesntExist()) {
            Shop::create([
                'business_id' => $business->id,
                'name' => $data['shop_name'],
                'city' => $data['city'],
                'address' => $data['address'] ?? null,
                'code' => 'SHOP-'.Str::upper(Str::random(6)),
                'is_active' => true,
                'logo_path' => $business->logo_path,
            ]);

            if (! $business->city) {
                $business->update(['city' => $data['city']]);
            }
        }

        return redirect()->route('onboarding.show', ['step' => 4]);
    }

    public function campaign(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();

        $data = $request->validate([
            'template' => ['required', 'string'],
        ]);

        $template = CampaignTemplates::localized($data['template']);
        abort_unless($template, 422);

        if ($business->campaigns()->doesntExist()) {
            $campaign = Campaign::create([
                'business_id' => $business->id,
                'name' => $template['name'],
                'type' => $template['type'],
                'description' => $template['description'],
                'spend_step' => $template['spend_step'],
                'points_per_step' => $template['points_per_step'],
                'bonus_points' => $template['bonus_points'],
                'starts_at' => now(),
                'is_active' => true,
                'template_key' => $data['template'],
            ]);

            $shopIds = $business->shops()->pluck('id');
            $campaign->shops()->sync($shopIds);
        }

        return redirect()->route('onboarding.show', ['step' => 5]);
    }

    public function offers(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();

        if (! $request->boolean('skip')) {
            $data = $request->validate([
                'offers' => ['nullable', 'array'],
                'offers.*' => ['string'],
            ]);

            $selected = $data['offers'] ?? [];
            $catalog = collect(OfferTemplates::forSector($business->sector ?: 'other'))->keyBy('key');

            foreach ($selected as $key) {
                $template = $catalog->get($key);
                if (! $template) {
                    continue;
                }

                $already = $business->rewards()
                    ->where('points_cost', $template['points_cost'])
                    ->where('reward_type', $template['reward_type'])
                    ->where('name', $template['name'])
                    ->exists();

                if ($already) {
                    continue;
                }

                Reward::create([
                    'business_id' => $business->id,
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'product_name' => $template['product_name'],
                    'points_cost' => $template['points_cost'],
                    'reward_type' => $template['reward_type'],
                    'reward_value' => $template['reward_value'],
                    'is_active' => true,
                ]);
            }
        }

        $business->update(['onboarding_completed_at' => now()]);

        app(\App\Services\ReferralService::class)->qualifyForBusiness($business->fresh());

        return redirect()->route('dashboard')->with('all_set', true);
    }
}
