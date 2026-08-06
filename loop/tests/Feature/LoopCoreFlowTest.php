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
            'shop_name' => 'Safari Cafe Main',
        ]);

        $response->assertRedirect(route('dashboard'));
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
            'points_balance' => 10, // 5000/1000*2
        ]);

        // Same phone again should not duplicate user
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
    }

    public function test_customer_can_login_with_phone_otp(): void
    {
        User::factory()->customer()->create([
            'phone' => '715000111',
            'country_code' => '+255',
        ]);

        $this->post(route('customer.send'), [
            'country_code' => '+255',
            'phone' => '715000111',
        ])->assertRedirect(route('customer.otp'));

        $this->post(route('customer.verify'), [
            'code' => '123456',
        ])->assertRedirect(route('discover'));

        $this->assertAuthenticated();
    }

    public function test_new_customer_can_self_register_after_otp(): void
    {
        $this->post(route('customer.send'), [
            'country_code' => '+255',
            'phone' => '716777888',
        ])->assertRedirect(route('customer.otp'));

        $this->post(route('customer.verify'), [
            'code' => '123456',
        ])->assertRedirect(route('customer.register'));

        $this->post(route('customer.register.store'), [
            'first_name' => 'Asha',
            'last_name' => 'Said',
            'country' => 'TZ',
            'city' => 'Dar es Salaam',
            'interests' => ['coffee', 'fashion'],
        ])->assertRedirect(route('discover'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'phone' => '716777888',
            'city' => 'Dar es Salaam',
            'role' => 'customer',
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
        ]);
        $owner->update(['business_id' => $business->id]);
        $shop = Shop::create([
            'business_id' => $business->id,
            'name' => 'Main',
            'is_active' => true,
        ]);

        return [$owner, $business, $shop];
    }
}
