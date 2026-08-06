<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Reward;
use App\Models\Shop;
use App\Models\User;
use App\Services\VisitService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::factory()->create([
            'name' => 'Amina Owusu',
            'email' => 'business@loop.test',
            'role' => User::ROLE_BUSINESS,
            'password' => Hash::make('password'),
        ]);

        $customer = User::factory()->create([
            'name' => 'Kojo Mensah',
            'email' => 'customer@loop.test',
            'role' => User::ROLE_CUSTOMER,
            'password' => Hash::make('password'),
        ]);

        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Harbor Beans',
            'slug' => 'harbor-beans',
            'category' => 'Cafe',
            'description' => 'Neighborhood coffee with a loyalty loop that rewards every visit.',
        ]);

        $downtown = Shop::create([
            'business_id' => $business->id,
            'name' => 'Harbor Beans Downtown',
            'code' => 'SHOP-HBDOWN',
            'address' => '12 Market Street',
            'city' => 'Accra',
            'is_active' => true,
        ]);

        $waterfront = Shop::create([
            'business_id' => $business->id,
            'name' => 'Harbor Beans Waterfront',
            'code' => 'SHOP-HBWAVE',
            'address' => '4 Pier Walk',
            'city' => 'Accra',
            'is_active' => true,
        ]);

        $campaign = Campaign::create([
            'business_id' => $business->id,
            'name' => 'Morning Regulars',
            'description' => 'Earn points on every shop visit this season.',
            'points_per_visit' => 15,
            'bonus_points' => 5,
            'max_visits_per_day' => 2,
            'starts_at' => now()->subDays(7),
            'ends_at' => now()->addMonths(2),
            'is_active' => true,
        ]);

        $campaign->shops()->sync([$downtown->id, $waterfront->id]);

        Reward::create([
            'business_id' => $business->id,
            'name' => 'Free pastry',
            'description' => 'Redeem for any pastry under GHS 30.',
            'points_cost' => 60,
            'stock' => 50,
        ]);

        Reward::create([
            'business_id' => $business->id,
            'name' => 'Large coffee upgrade',
            'description' => 'Upgrade any drink to large.',
            'points_cost' => 30,
            'stock' => null,
        ]);

        app(VisitService::class)->checkIn($customer, $downtown);
    }
}
