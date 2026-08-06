<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Reward;
use App\Models\Shop;
use App\Support\CampaignTemplates;
use App\Support\Countries;
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

        $step = max(1, min(4, (int) $request->query('step', 1)));

        return view('onboarding.business', [
            'business' => $business->fresh(),
            'cities' => Countries::cities($business->country),
            'templates' => CampaignTemplates::all(),
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

        $template = CampaignTemplates::all()[$data['template']] ?? null;
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

            if (! empty($template['reward'])) {
                Reward::create([
                    'business_id' => $business->id,
                    'name' => $template['reward']['name'],
                    'points_cost' => $template['reward']['points_cost'],
                    'reward_type' => $template['reward']['reward_type'],
                    'reward_value' => $template['reward']['reward_value'],
                    'is_active' => true,
                ]);
            }

            $shopIds = $business->shops()->pluck('id');
            $campaign->shops()->sync($shopIds);
        }

        $business->update(['onboarding_completed_at' => now()]);

        return redirect()->route('dashboard')->with('status', __('loop.onboarding_done'));
    }
}
