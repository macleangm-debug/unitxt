<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Reward;
use App\Models\Shop;
use App\Support\CampaignTemplates;
use App\Support\Confirm;
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
        $branchTotal = max(1, (int) ($business->branch_count ?: 1));
        $shopsDone = $business->shops()->count();
        $branchIndex = max(1, min($branchTotal, (int) $request->query('branch', $shopsDone + 1)));

        // Logo is compulsory — never skip step 1 without one.
        if ($step > 1 && blank($business->logo_path)) {
            return redirect()->route('onboarding.show', ['step' => 1])
                ->withErrors(['logo' => __('loop.logo_required_body')]);
        }

        // Step 3: walk through each branch until count is met.
        if ($step === 3 && $shopsDone >= $branchTotal) {
            return redirect()->route('onboarding.show', ['step' => 4]);
        }

        // Step 4 = campaign first. Step 5 = offers (what they redeem).
        if ($step === 5 && $business->campaigns()->doesntExist()) {
            return redirect()->route('onboarding.show', ['step' => 4]);
        }

        return view('onboarding.business', [
            'business' => $business->fresh(),
            'cities' => Countries::cities($business->country ?? 'TZ'),
            'areasByCity' => collect(Countries::cities($business->country ?? 'TZ'))
                ->mapWithKeys(fn ($city) => [$city => Countries::areas($city)])
                ->all(),
            'groupedTemplates' => CampaignTemplates::grouped([
                // Retention / bonus campaigns come after the first earn + offer.
                'visit_streak', 'monthly_streak', 'birthday_treat', 'welcome_bonus',
            ]),
            'offerTemplates' => OfferTemplates::forSector($business->sector ?: 'other'),
            'earnCampaign' => $business->campaigns()->whereIn('type', ['earn', 'product_push'])->latest()->first(),
            'existingOffers' => $business->rewards()->latest()->get(),
            'step' => $step,
            'branchIndex' => $branchIndex,
            'branchTotal' => $branchTotal,
            'dial' => Countries::dial($business->country ?? 'TZ'),
            'logoJustSaved' => (bool) $request->session()->pull('logo_just_saved', false),
            'isOnline' => $business->isOnline(),
            'currency' => $business->currency ?? 'TZS',
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

        if (blank($business->logo_path)) {
            return redirect()->route('onboarding.show', ['step' => 1])
                ->withErrors(['logo' => __('loop.logo_required_body')]);
        }

        $data = $request->validate([
            'presence' => ['required', 'in:physical,online'],
            'branch_count' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $branchCount = $data['presence'] === 'online' ? 1 : $data['branch_count'];

        $business->update([
            'presence' => $data['presence'],
            'branch_count' => $branchCount,
        ]);

        return redirect()->route('onboarding.show', ['step' => 3, 'branch' => 1]);
    }

    public function shop(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();

        if (blank($business->logo_path)) {
            return redirect()->route('onboarding.show', ['step' => 1])
                ->withErrors(['logo' => __('loop.logo_required_body')]);
        }

        $branchTotal = max(1, (int) ($business->branch_count ?: 1));
        $shopsDone = $business->shops()->count();

        if ($shopsDone >= $branchTotal) {
            return redirect()->route('onboarding.show', ['step' => 4]);
        }

        $isOnline = $business->isOnline();

        $data = $request->validate([
            'city' => [$isOnline ? 'nullable' : 'required', 'string', 'max:80'],
            'address' => [$isOnline ? 'nullable' : 'required', 'string', 'max:255'],
            'hotline' => ['nullable', 'string', 'max:40'],
        ]);

        $city = $data['city'] ?? ($business->city ?: 'Online');
        if ($isOnline) {
            $city = $city !== '' ? $city : 'Online';
        }

        $shopName = $branchTotal === 1
            ? $business->name
            : $business->name.' — '.$city;

        // Avoid duplicate city names when adding multiple in same city.
        if ($branchTotal > 1 && $business->shops()->where('name', $shopName)->exists()) {
            $shopName = $business->name.' — '.$city.' '.($shopsDone + 1);
        }

        Shop::create([
            'business_id' => $business->id,
            'name' => $shopName,
            'city' => $city,
            'address' => $isOnline ? ($data['address'] ?? null) : $data['address'],
            'code' => 'SHOP-'.Str::upper(Str::random(6)),
            'is_active' => true,
            'logo_path' => $business->logo_path,
        ]);

        if (! $business->city && $city !== 'Online') {
            $business->update(['city' => $city]);
        }

        // Hotline once (first branch), dial fixed to business country.
        if ($shopsDone === 0 && ! blank($data['hotline'] ?? null)) {
            $dial = Countries::dial($business->country ?? 'TZ');
            $business->update([
                'hotline' => trim($dial.' '.Countries::normalizePhone($data['hotline'])),
            ]);
        }

        $nextIndex = $shopsDone + 2;
        if ($shopsDone + 1 < $branchTotal) {
            return redirect()->route('onboarding.show', ['step' => 3, 'branch' => $nextIndex]);
        }

        return redirect()->route('onboarding.show', ['step' => 4]);
    }

    public function campaign(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();

        $data = $request->validate([
            'template' => ['required', 'string'],
            'spend_step' => ['nullable', 'integer', 'min:100'],
            'points_per_step' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'bonus_points' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'featured_product_name' => ['nullable', 'string', 'max:120'],
        ]);

        $template = CampaignTemplates::localized($data['template']);
        abort_unless($template, 422);

        if ($template['type'] === 'product_push') {
            $request->validate([
                'featured_product_name' => ['required', 'string', 'max:120'],
                'bonus_points' => ['required', 'integer', 'min:1', 'max:10000'],
            ]);
            $data['featured_product_name'] = $request->input('featured_product_name');
            $data['bonus_points'] = (int) $request->input('bonus_points');
        }

        // Prefer submitted rates; fall back to template defaults for earn / product push.
        $spendStep = (int) ($data['spend_step'] ?? $template['spend_step'] ?? 1000);
        $pointsPerStep = (int) ($data['points_per_step'] ?? $template['points_per_step'] ?? 2);

        if ($business->campaigns()->doesntExist()) {
            $campaign = Campaign::create([
                'business_id' => $business->id,
                'name' => $template['name'],
                'type' => $template['type'],
                'description' => $template['description'],
                'spend_step' => $spendStep,
                'points_per_step' => $pointsPerStep,
                'bonus_points' => $data['bonus_points'] ?? ($template['bonus_points'] ?? 0),
                'featured_product_name' => $data['featured_product_name'] ?? null,
                'streak_target' => $template['streak_target'] ?? null,
                'streak_period' => $template['streak_period'] ?? null,
                'starts_at' => now(),
                'is_active' => true,
                'template_key' => $data['template'],
            ]);

            $shopIds = $business->shops()->pluck('id');
            $campaign->shops()->sync($shopIds);
        }

        return redirect()->route('onboarding.show', ['step' => 5])->with('confirm', Confirm::make(
            __('loop.first_campaign_done_title'),
            __('loop.first_campaign_done_body'),
            __('loop.next_to_offers'),
            route('onboarding.show', ['step' => 5]),
            true,
        ));
    }

    public function offers(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();

        if ($business->campaigns()->doesntExist()) {
            return redirect()->route('onboarding.show', ['step' => 4]);
        }

        $data = $request->validate([
            'offers' => ['required', 'array', 'min:1'],
            'offers.*' => ['string'],
        ]);

        $selected = $data['offers'];
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

        if ($business->rewards()->doesntExist()) {
            return back()
                ->withErrors(['offers' => __('loop.need_offer_first_body')])
                ->with('confirm', Confirm::make(
                    __('loop.need_offer_first_title'),
                    __('loop.need_offer_first_body'),
                    __('loop.pick_offers'),
                    route('onboarding.show', ['step' => 5]),
                    false,
                ));
        }

        $campaign = $business->campaigns()->latest()->first();
        if ($campaign) {
            $campaign->rewards()->sync($business->rewards()->pluck('id'));
            $campaign->shops()->sync($business->shops()->pluck('id'));
        }

        $business->update(['onboarding_completed_at' => now()]);

        app(\App\Services\ReferralService::class)->qualifyForBusiness($business->fresh());
        app(\App\Services\AffiliateService::class)->qualifyForBusiness($business->fresh());

        return redirect()->route('dashboard')->with('all_set', true);
    }
}
