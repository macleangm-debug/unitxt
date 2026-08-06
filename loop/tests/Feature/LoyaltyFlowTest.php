<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_can_create_shop_and_campaign(): void
    {
        $owner = User::factory()->business()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Loop Cafe',
            'slug' => 'loop-cafe',
        ]);

        $this->actingAs($owner)
            ->post(route('shops.store'), [
                'name' => 'Main Street',
                'city' => 'Accra',
            ])
            ->assertRedirect(route('shops.index'));

        $shop = Shop::first();
        $this->assertNotNull($shop);
        $this->assertSame($business->id, $shop->business_id);

        $this->actingAs($owner)
            ->post(route('campaigns.store'), [
                'name' => 'Weekend Loop',
                'points_per_visit' => 20,
                'bonus_points' => 5,
                'max_visits_per_day' => 1,
                'starts_at' => now()->toDateString(),
                'shop_ids' => [$shop->id],
            ])
            ->assertRedirect(route('campaigns.index'));

        $this->assertDatabaseHas('campaigns', [
            'business_id' => $business->id,
            'name' => 'Weekend Loop',
            'points_per_visit' => 20,
        ]);
    }

    public function test_customer_earns_points_when_checking_in(): void
    {
        $owner = User::factory()->business()->create();
        $customer = User::factory()->customer()->create();

        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Harbor Beans',
            'slug' => 'harbor-beans',
        ]);

        $shop = Shop::create([
            'business_id' => $business->id,
            'name' => 'Downtown',
            'code' => 'SHOP-TEST01',
            'city' => 'Accra',
        ]);

        $campaign = Campaign::create([
            'business_id' => $business->id,
            'name' => 'Daily Visits',
            'points_per_visit' => 10,
            'bonus_points' => 5,
            'max_visits_per_day' => 1,
            'starts_at' => now()->subDay(),
            'is_active' => true,
        ]);
        $campaign->shops()->attach($shop);

        $this->actingAs($customer)
            ->post(route('visits.store'), ['code' => 'SHOP-TEST01'])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('visits', [
            'customer_id' => $customer->id,
            'shop_id' => $shop->id,
            'points_earned' => 15,
        ]);

        $this->assertDatabaseHas('memberships', [
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'points_balance' => 15,
            'lifetime_points' => 15,
        ]);
    }

    public function test_customer_cannot_exceed_daily_visit_limit(): void
    {
        $owner = User::factory()->business()->create();
        $customer = User::factory()->customer()->create();

        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Harbor Beans',
            'slug' => 'harbor-beans-2',
        ]);

        $shop = Shop::create([
            'business_id' => $business->id,
            'name' => 'Downtown',
            'code' => 'SHOP-LIMIT1',
        ]);

        Campaign::create([
            'business_id' => $business->id,
            'name' => 'Once a Day',
            'points_per_visit' => 10,
            'bonus_points' => 0,
            'max_visits_per_day' => 1,
            'starts_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->actingAs($customer)->post(route('visits.store'), ['code' => 'SHOP-LIMIT1'])->assertRedirect();
        $this->actingAs($customer)->post(route('visits.store'), ['code' => 'SHOP-LIMIT1'])->assertSessionHasErrors('visit');
    }

    public function test_registration_supports_business_and_customer_roles(): void
    {
        $this->post('/register', [
            'name' => 'Biz Owner',
            'email' => 'owner@example.com',
            'role' => 'business',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('business.setup'));

        $this->assertDatabaseHas('users', [
            'email' => 'owner@example.com',
            'role' => 'business',
        ]);
    }
}
