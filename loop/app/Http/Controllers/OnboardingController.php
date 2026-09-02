<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Reward;
use App\Models\Shop;
use App\Services\AffiliateService;
use App\Services\ReferralService;
use App\Support\CampaignTemplates;
use App\Support\Confirm;
use App\Support\Countries;
use App\Support\StarterLoyalty;
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

        $step = $this->gateStep($request, $business);
        $earnCampaign = $business->campaigns()->where('type', 'earn')->latest()->first();
        $starter = $earnCampaign
            ? StarterLoyalty::fromCampaign($earnCampaign, $business)
            : StarterLoyalty::propose(5000, $business);

        return view('onboarding.business', [
            'business' => $business->fresh()->load('shops'),
            'cities' => Countries::cities($business->country ?? 'TZ'),
            'earnCampaign' => $earnCampaign,
            'starter' => $starter,
            'spendChoices' => StarterLoyalty::spendChoices(),
            'step' => $step,
            'totalSteps' => 3,
            'currency' => $business->currency ?? 'TZS',
        ]);
    }

    public function logo(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();

        if ($request->hasFile('logo')) {
            $request->validate(['logo' => ['image', 'max:2048']]);
            $path = $request->file('logo')->store('business-logos', 'public');
            $business->update(['logo_path' => $path]);
        }

        return redirect()->route('onboarding.show', ['step' => $this->progressStep($business->fresh())]);
    }

    public function presence(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();

        $data = $request->validate([
            'presence' => ['required', 'in:physical,online,both'],
        ]);

        $updates = ['presence' => $data['presence']];
        if ($data['presence'] === 'online') {
            $updates['branch_count'] = 1;
        }
        $business->update($updates);

        return redirect()->route('onboarding.show', ['step' => 1]);
    }

    public function branches(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();

        $data = $request->validate([
            'branch_count' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $business->update([
            'presence' => $business->presence ?: 'physical',
            'branch_count' => $data['branch_count'],
        ]);

        return redirect()->route('onboarding.show', ['step' => 1]);
    }

    public function shop(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();

        $presence = (string) $request->input('presence', '');
        $needsAddress = in_array($presence, ['physical', 'both'], true);
        $needsLocations = in_array($presence, ['physical', 'both'], true);

        $data = $request->validate([
            'presence' => ['required', 'in:physical,online,both'],
            'city' => ['required', 'string', 'max:80'],
            'address' => [$needsAddress ? 'required' : 'nullable', 'string', 'max:255'],
            'hotline' => ['nullable', 'string', 'max:40'],
            'hotline_country_code' => ['nullable', 'string', 'max:8'],
            'locations' => [$needsLocations ? 'required' : 'nullable', 'in:just_one,more'],
            'branch_count' => ['nullable', 'integer', 'min:1', 'max:50'],
            'logo' => [$business->logo_path ? 'nullable' : 'required', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('business-logos', 'public');
            $business->update(['logo_path' => $path]);
            $business = $business->fresh();
        }

        if (blank($business->logo_path)) {
            return back()->withErrors([
                'logo' => __('loop.logo_required_body'),
            ]);
        }

        $city = $data['city'];
        $presence = $data['presence'];
        $address = $needsAddress ? ($data['address'] ?? null) : null;
        $locations = $presence === 'online' ? 'just_one' : $data['locations'];
        $branchCount = $presence === 'online' || $locations !== 'more'
            ? 1
            : max(2, min(50, (int) ($data['branch_count'] ?? 2)));

        $businessUpdates = [
            'city' => $city === 'Online' ? $business->city : $city,
            'presence' => $presence,
            'branch_count' => $branchCount,
        ];

        if (! blank($data['hotline'] ?? null)) {
            $dial = $data['hotline_country_code'] ?? Countries::dial($business->country ?? 'TZ');
            $businessUpdates['hotline'] = trim($dial.' '.Countries::normalizePhone($data['hotline']));
        }

        $business->update($businessUpdates);
        $business = $business->fresh();

        $shopName = $branchCount === 1
            ? $business->name
            : $business->name.' — '.$city;

        $shop = $business->shops()->first();
        $payload = [
            'name' => $shop?->name ?: $shopName,
            'city' => $city,
            'address' => $address,
            'logo_path' => $business->logo_path,
        ];

        if ($shop) {
            $shop->update($payload);
        } else {
            Shop::create(array_merge($payload, [
                'business_id' => $business->id,
                'code' => 'SHOP-'.Str::upper(Str::random(6)),
                'is_active' => true,
            ]));
        }

        return redirect()->route('onboarding.show', ['step' => 2]);
    }

    public function campaign(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();

        if (blank($business->logo_path) || $business->shops()->doesntExist()) {
            return redirect()->route('onboarding.show', ['step' => 1]);
        }

        $data = $request->validate([
            'typical_spend' => ['required', 'integer', 'min:1', 'max:10000000'],
        ]);

        if ($business->campaigns()->where('type', 'earn')->exists()) {
            return redirect()->route('onboarding.show', ['step' => 3]);
        }

        $starter = StarterLoyalty::propose((int) $data['typical_spend'], $business);
        $template = CampaignTemplates::localized(CampaignTemplates::MAIN_KEY);

        $campaign = Campaign::create([
            'business_id' => $business->id,
            'name' => $starter['campaign_name'],
            'type' => 'earn',
            'description' => $template['description'] ?? null,
            'spend_step' => $starter['spend_step'],
            'points_per_step' => $starter['points_per_step'],
            'bonus_points' => 0,
            'featured_product_name' => null,
            'starts_at' => now(),
            'is_active' => true,
            'template_key' => CampaignTemplates::MAIN_KEY,
        ]);

        $campaign->shops()->sync($business->shops()->pluck('id'));

        return redirect()->route('onboarding.show', ['step' => 3]);
    }

    public function offers(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();

        $earn = $business->campaigns()->where('type', 'earn')->latest()->first();
        if (! $earn) {
            return redirect()->route('onboarding.show', ['step' => 2]);
        }
        if (blank($business->logo_path) || $business->shops()->doesntExist()) {
            return redirect()->route('onboarding.show', ['step' => 1]);
        }

        $starter = StarterLoyalty::fromCampaign($earn, $business);
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        if ($business->rewards()->doesntExist()) {
            $name = trim((string) ($data['name'] ?? '')) ?: $starter['reward_name'];
            Reward::create([
                'business_id' => $business->id,
                'name' => $name,
                'description' => null,
                'product_name' => $starter['product_name'],
                'points_cost' => $starter['points_cost'],
                'reward_type' => $starter['reward_type'],
                'reward_value' => 0,
                'is_active' => true,
            ]);
        }

        $earn->shops()->sync($business->shops()->pluck('id'));
        $business->update(['onboarding_completed_at' => now()]);

        app(ReferralService::class)->qualifyForBusiness($business->fresh());
        app(AffiliateService::class)->qualifyForBusiness($business->fresh());

        return redirect()->route('till.index')->with('confirm', Confirm::make(
            __('loop.youre_live'),
            __('loop.onboarding_done'),
            __('loop.start_selling'),
            route('till.index'),
            true,
        ));
    }

    private function gateStep(Request $request, $business): int
    {
        $ready = $this->progressStep($business);
        $wanted = (int) $request->query('step', $ready);
        if ($wanted < 1) {
            $wanted = $ready;
        }

        return min(max(1, $wanted), $ready, 3);
    }

    private function progressStep($business): int
    {
        if (blank($business->logo_path) || $business->shops()->doesntExist()) {
            return 1;
        }
        if ($business->campaigns()->where('type', 'earn')->doesntExist()) {
            return 2;
        }

        return 3;
    }
}
