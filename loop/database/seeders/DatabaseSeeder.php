<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Plan;
use App\Models\Reward;
use App\Models\Shop;
use App\Models\User;
use App\Services\TillService;
use App\Support\Plans;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Plans::catalog() as $key => $plan) {
            Plan::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $plan['name'],
                    'tagline' => $plan['tagline'],
                    'price_monthly' => $plan['price_monthly'],
                    'currency' => $plan['currency'],
                    'max_shops' => $plan['max_shops'],
                    'max_members' => $plan['max_members'],
                    'max_monthly_visits' => $plan['max_monthly_visits'] ?? null,
                    'is_public' => true,
                    'sort_order' => $plan['sort_order'],
                    'features' => $plan['features'],
                ]
            );
        }

        \App\Models\PlatformSetting::putValue(\App\Support\BillingSettings::KEY, \App\Support\BillingSettings::defaults());
        \App\Models\PlatformSetting::putValue(\App\Support\ReferralProgram::KEY, \App\Support\ReferralProgram::defaults());
        \App\Models\PlatformSetting::putValue(\App\Support\GrowthSettings::KEY, \App\Support\GrowthSettings::defaults());
        \App\Models\PlatformSetting::putValue(\App\Support\FeatureFlags::KEY, \App\Support\FeatureFlags::defaults());
        \App\Models\PlatformSetting::putValue(\App\Support\AffiliateProgram::KEY, \App\Support\AffiliateProgram::defaults());

        User::factory()->admin()->create([
            'first_name' => 'Loop',
            'last_name' => 'Admin',
            'phone' => '710000000',
            'email' => 'admin@loop.test',
            'password' => Hash::make('password'),
        ]);

        $owner = User::factory()->owner()->create([
            'first_name' => 'Amina',
            'last_name' => 'Owusu',
            'phone' => '712000001',
            'email' => 'business@loop.test',
            'password' => Hash::make('password'),
        ]);

        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Harbor Beans',
            'slug' => 'harbor-beans',
            'sector' => 'coffee',
            'country' => 'TZ',
            'currency' => 'TZS',
            'city' => 'Dar es Salaam',
            'hotline' => '+255 712 000 001',
            'description' => 'Neighborhood coffee with Loop loyalty on every cup.',
            'plan_key' => Plans::GROWTH,
            'billing_status' => 'active',
            'trial_ends_at' => now()->subDay(),
            'referral_code' => 'HARBOR01',
        ]);

        $owner->update(['business_id' => $business->id]);

        $frontDesk = User::factory()->frontDesk()->create([
            'first_name' => 'Neema',
            'last_name' => 'Juma',
            'phone' => '712000002',
            'password' => Hash::make('password'),
            'business_id' => $business->id,
        ]);

        $downtown = Shop::create([
            'business_id' => $business->id,
            'name' => 'Harbor Beans Downtown',
            'code' => 'SHOP-HBDOWN',
            'address' => 'Samora Avenue',
            'city' => 'Dar es Salaam',
            'phone' => '+255 712 000 001',
            'is_active' => true,
        ]);

        Shop::create([
            'business_id' => $business->id,
            'name' => 'Harbor Beans Waterfront',
            'code' => 'SHOP-HBWAVE',
            'address' => 'Slipway',
            'city' => 'Dar es Salaam',
            'phone' => '+255 712 000 002',
            'is_active' => true,
        ]);

        $frontDesk->update(['shop_id' => $downtown->id]);

        $campaign = Campaign::create([
            'business_id' => $business->id,
            'name' => 'Everyday earn',
            'type' => 'earn',
            'description' => 'Every TZS 1,000 = 2 points',
            'spend_step' => 1000,
            'points_per_step' => 2,
            'bonus_points' => 0,
            'starts_at' => now()->subDays(7),
            'ends_at' => now()->addMonths(3),
            'is_active' => true,
            'template_key' => 'everyday_earn',
        ]);
        $campaign->shops()->sync([$downtown->id]);

        Campaign::create([
            'business_id' => $business->id,
            'name' => 'Birthday treat',
            'type' => 'birthday',
            'bonus_points' => 50,
            'starts_at' => now()->subDays(7),
            'is_active' => true,
            'template_key' => 'birthday_treat',
        ]);

        Reward::create([
            'business_id' => $business->id,
            'name' => '5% off anything',
            'description' => 'Applied at the till when you have 100 points.',
            'points_cost' => 100,
            'reward_type' => 'percent_off',
            'reward_value' => 5,
            'is_active' => true,
        ]);

        $customer = User::factory()->customer()->create([
            'first_name' => 'Kojo',
            'last_name' => 'Mensah',
            'phone' => '713000001',
            'country' => 'TZ',
            'city' => 'Dar es Salaam',
            'interests' => ['coffee', 'fashion'],
            'birth_month' => (int) now()->format('n'),
            'birth_day' => (int) now()->format('j'),
            'password' => Hash::make('1234'),
            'phone_verified_at' => now(),
            'profile_completed' => true,
        ]);

        app(TillService::class)->recordSale($frontDesk, $downtown, $customer, 10000);

        $business->update(['onboarding_completed_at' => now()]);

        // Fashion demo business for sector grouping
        $fashionOwner = User::factory()->owner()->create([
            'first_name' => 'Fatma',
            'last_name' => 'Ali',
            'phone' => '714000001',
            'password' => Hash::make('password'),
        ]);
        $fashion = Business::create([
            'owner_id' => $fashionOwner->id,
            'name' => 'Kanga Collective',
            'slug' => 'kanga-collective',
            'sector' => 'fashion',
            'country' => 'TZ',
            'currency' => 'TZS',
            'city' => 'Dar es Salaam',
            'hotline' => '+255 714 000 001',
        ]);
        $fashionOwner->update(['business_id' => $fashion->id]);
        Shop::create([
            'business_id' => $fashion->id,
            'name' => 'Kanga Collective Masaki',
            'city' => 'Dar es Salaam',
            'is_active' => true,
        ]);
        Campaign::create([
            'business_id' => $fashion->id,
            'name' => 'Style points',
            'type' => 'earn',
            'spend_step' => 5000,
            'points_per_step' => 5,
            'starts_at' => now()->subDay(),
            'is_active' => true,
        ]);

        // Restaurant demo for discover carousels
        $restoOwner = User::factory()->owner()->create([
            'first_name' => 'Joseph',
            'last_name' => 'Mwangi',
            'phone' => '715000001',
            'password' => Hash::make('password'),
        ]);
        $resto = Business::create([
            'owner_id' => $restoOwner->id,
            'name' => 'Coast Kitchen',
            'slug' => 'coast-kitchen',
            'sector' => 'restaurants',
            'country' => 'TZ',
            'currency' => 'TZS',
            'city' => 'Dar es Salaam',
            'hotline' => '+255 715 000 001',
            'description' => 'Coastal plates with Loop points on every table.',
            'onboarding_completed_at' => now(),
        ]);
        $restoOwner->update(['business_id' => $resto->id]);
        Shop::create([
            'business_id' => $resto->id,
            'name' => 'Coast Kitchen Oyster Bay',
            'city' => 'Dar es Salaam',
            'is_active' => true,
        ]);
        Campaign::create([
            'business_id' => $resto->id,
            'name' => 'Table earn',
            'type' => 'earn',
            'spend_step' => 2000,
            'points_per_step' => 3,
            'starts_at' => now()->subDay(),
            'is_active' => true,
        ]);
    }
}
