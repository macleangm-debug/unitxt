<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoopCoreFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_can_register_with_tanzania_defaults(): void
    {
        $response = $this->post('/business/register', [
            'first_name' => 'Amina',
            'last_name' => 'Owusu',
            'country' => 'TZ',
            'phone' => '0712111222',
            'password' => 'password',
            'password_confirmation' => 'password',
            'business_name' => 'Safari Cafe',
            'sector' => 'coffee',
            'city' => 'Arusha',
        ]);

        $response->assertRedirect(route('onboarding.show'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'phone' => '712111222',
            'country_code' => '+255',
            'role' => 'owner',
        ]);
        $this->assertDatabaseHas('businesses', [
            'name' => 'Safari Cafe',
            'sector' => 'coffee',
            'country' => 'TZ',
            'currency' => 'TZS',
            'city' => 'Arusha',
        ]);
    }

    public function test_till_awards_points_from_spend_and_avoids_duplicate_customers(): void
    {
        [$owner, $business, $shop] = $this->seedBusiness();
        $staff = User::factory()->frontDesk()->create([
            'phone' => '712999001',
            'business_id' => $business->id,
            'password' => 'password',
        ]);

        Campaign::create([
            'business_id' => $business->id,
            'name' => 'Earn',
            'type' => 'earn',
            'spend_step' => 1000,
            'points_per_step' => 2,
            'starts_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->post(route('till.store'), [
                'shop_id' => $shop->id,
                'country_code' => '+255',
                'phone' => '713555666',
                'channel' => 'in_store',
                'amount_spent' => 5000,
                'first_name' => 'Kojo',
                'last_name' => 'Mensah',
            ])
            ->assertRedirect(route('till.index'));

        $this->assertDatabaseHas('users', [
            'phone' => '713555666',
            'role' => 'customer',
            'first_name' => 'Kojo',
        ]);
        $this->assertDatabaseHas('memberships', [
            'business_id' => $business->id,
            'points_balance' => 10,
        ]);

        $this->actingAs($staff)
            ->post(route('till.store'), [
                'shop_id' => $shop->id,
                'country_code' => '+255',
                'phone' => '713555666',
                'channel' => 'phone_order',
                'amount_spent' => 1000,
            ])
            ->assertRedirect(route('till.index'));

        $this->assertSame(1, User::query()->where('phone', '713555666')->count());
        $this->assertDatabaseHas('memberships', [
            'business_id' => $business->id,
            'points_balance' => 12,
        ]);

        $this->actingAs($staff)
            ->post(route('till.store'), [
                'shop_id' => $shop->id,
                'country_code' => '+255',
                'phone' => '713555666',
                'channel' => 'in_store',
                'amount_spent' => 2000,
                'pay_with_points' => 1,
                'points_to_spend' => 4,
            ])
            ->assertRedirect(route('till.index'));

        $this->assertDatabaseHas('memberships', [
            'business_id' => $business->id,
            'points_balance' => 12, // +4 earned from 2000, −4 spent
        ]);
    }

    public function test_owner_settings_and_campaign_detail(): void
    {
        [$owner, $business, $shop] = $this->seedBusiness();
        $campaign = Campaign::create([
            'business_id' => $business->id,
            'name' => 'Earn',
            'type' => 'earn',
            'spend_step' => 1000,
            'points_per_step' => 2,
            'starts_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('settings'))
            ->assertOk()
            ->assertSee('Settings');

        $this->actingAs($owner)
            ->get(route('campaigns.show', $campaign))
            ->assertOk()
            ->assertSee('Earn');
    }

    public function test_customer_can_login_with_pin(): void
    {
        User::factory()->customer()->create([
            'phone' => '715000111',
            'country_code' => '+255',
            'password' => '1234',
            'profile_completed' => true,
        ]);

        $this->post(route('customer.send'), [
            'country_code' => '+255',
            'phone' => '715000111',
        ])->assertRedirect(route('customer.pin'));

        $this->post(route('customer.pin.verify'), [
            'pin' => '1234',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_new_customer_can_self_register_with_pin(): void
    {
        $this->post(route('customer.send'), [
            'country_code' => '+255',
            'phone' => '716777888',
        ])->assertRedirect(route('customer.register'));

        $this->post(route('customer.register.store'), [
            'first_name' => 'Asha',
            'last_name' => 'Said',
            'country' => 'TZ',
            'city' => 'Dar es Salaam',
            'birth_month' => 5,
            'birth_day' => 12,
            'interests' => ['coffee', 'fashion'],
            'pin' => '2468',
            'pin_confirmation' => '2468',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'phone' => '716777888',
            'city' => 'Dar es Salaam',
            'role' => 'customer',
            'birth_month' => 5,
            'birth_day' => 12,
            'profile_completed' => 1,
        ]);
    }

    public function test_entry_pages_render(): void
    {
        $this->get('/')->assertOk();
        $this->get('/for-business')->assertOk();
        $this->get('/for-customers')->assertOk();
        $this->get('/discover')->assertOk();
        $this->get('/locale/sw')->assertRedirect();
    }

    private function seedBusiness(): array
    {
        $owner = User::factory()->owner()->create(['phone' => '712888001']);
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Test Shop Co',
            'slug' => 'test-shop-co',
            'sector' => 'retail',
            'country' => 'TZ',
            'currency' => 'TZS',
            'city' => 'Dar es Salaam',
            'onboarding_completed_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);
        $shop = Shop::create([
            'business_id' => $business->id,
            'name' => 'Main',
            'city' => 'Dar es Salaam',
            'is_active' => true,
        ]);

        return [$owner, $business, $shop];
    }
}
