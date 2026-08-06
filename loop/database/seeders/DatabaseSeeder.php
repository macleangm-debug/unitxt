<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Reward;
use App\Models\Shop;
use App\Models\User;
use App\Services\TillService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
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
            'description' => 'Neighborhood coffee with Loop loyalty on every cup.',
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
            'is_active' => true,
        ]);

        Shop::create([
            'business_id' => $business->id,
            'name' => 'Harbor Beans Waterfront',
            'code' => 'SHOP-HBWAVE',
            'address' => 'Slipway',
            'city' => 'Dar es Salaam',
            'is_active' => true,
        ]);

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
            'birth_date' => now()->subYears(28),
            'phone_verified_at' => now(),
        ]);

        app(TillService::class)->recordSale($frontDesk, $downtown, $customer, 10000);

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
        ]);
        $fashionOwner->update(['business_id' => $fashion->id]);
        Shop::create([
            'business_id' => $fashion->id,
            'name' => 'Kanga Collective Masaki',
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
    }
}
