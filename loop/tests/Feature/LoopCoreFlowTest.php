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

    public function test_discover_hides_points_for_guests_and_shows_for_customers(): void
    {
        [$owner, $business, $shop] = $this->seedBusiness();
        Campaign::create([
            'business_id' => $business->id,
            'name' => 'Earn',
            'type' => 'earn',
            'spend_step' => 1000,
            'points_per_step' => 2,
            'starts_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $staff = User::factory()->frontDesk()->create([
            'phone' => '712999002',
            'business_id' => $business->id,
            'password' => 'password',
        ]);
        $customer = User::factory()->customer()->create([
            'phone' => '713999001',
            'country' => 'TZ',
            'city' => 'Dar es Salaam',
            'password' => '1234',
            'profile_completed' => true,
        ]);

        app(\App\Services\TillService::class)->recordSale($staff, $shop, $customer, 5000);

        $this->get(route('discover'))
            ->assertOk()
            ->assertSee($business->name)
            ->assertDontSee(__('loop.your_places'))
            ->assertDontSee('Harbor Beans Downtown');

        $this->actingAs($customer)
            ->get(route('discover'))
            ->assertOk()
            ->assertSee(__('loop.your_places'))
            ->assertSee('10 pts');
    }

    public function test_onboarding_campaign_then_offers_completes_setup(): void
    {
        $owner = User::factory()->owner()->create(['phone' => '712777001']);
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Coastal Bites',
            'slug' => 'coastal-bites',
            'sector' => 'restaurants',
            'country' => 'TZ',
            'currency' => 'TZS',
            'city' => 'Dar es Salaam',
            'branch_count' => 1,
            'logo_path' => 'business-logos/demo.png',
            'onboarding_completed_at' => null,
        ]);
        $owner->update(['business_id' => $business->id]);
        Shop::create([
            'business_id' => $business->id,
            'name' => 'Main',
            'city' => 'Dar es Salaam',
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('onboarding.show', ['step' => 4]))
            ->assertOk()
            ->assertSee(__('loop.pick_campaign_earn_only'))
            ->assertDontSee('earn_with_discount');

        $this->actingAs($owner)
            ->post(route('onboarding.campaign'), ['template' => 'everyday_earn'])
            ->assertRedirect(route('onboarding.show', ['step' => 5]));

        $this->assertDatabaseHas('campaigns', [
            'business_id' => $business->id,
            'template_key' => 'everyday_earn',
            'type' => 'earn',
        ]);
        $this->assertDatabaseMissing('rewards', ['business_id' => $business->id]);

        $this->actingAs($owner)
            ->get(route('onboarding.show', ['step' => 5]))
            ->assertOk()
            ->assertSee(__('loop.pick_offers'))
            ->assertSee(__('loop.offer_templates.free_meal_500.name'));

        $this->actingAs($owner)
            ->post(route('onboarding.offers'), [
                'offers' => ['percent_5_100', 'free_meal_500'],
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($business->fresh()->onboarding_completed_at);
        $this->assertSame(2, $business->rewards()->count());
    }

    public function test_campaign_templates_are_earn_only_without_bundled_offers(): void
    {
        $this->assertArrayNotHasKey('earn_with_discount', \App\Support\CampaignTemplates::all());
        $this->assertArrayHasKey('faster_earn', \App\Support\CampaignTemplates::all());

        $offers = \App\Support\OfferTemplates::forSector('coffee');
        $keys = collect($offers)->pluck('key')->all();
        $this->assertContains('free_coffee_100', $keys);
        $this->assertContains('percent_5_100', $keys);
    }

    public function test_admin_panel_and_business_referral_reward(): void
    {
        foreach (\App\Support\Plans::catalog() as $key => $plan) {
            \App\Models\Plan::query()->create([
                'key' => $key,
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
            ]);
        }

        $admin = User::factory()->admin()->create([
            'phone' => '710000111',
            'password' => 'password',
        ]);

        [$owner, $business] = array_slice($this->seedBusiness(), 0, 2);
        $business->update(['referral_code' => 'INVITE88', 'plan_key' => 'starter', 'billing_status' => 'active']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('loop.admin_dashboard'));

        auth()->logout();

        $this->post(route('business.register'), [
            'first_name' => 'Referred',
            'last_name' => 'Owner',
            'country' => 'TZ',
            'phone' => '0712888999',
            'password' => 'password',
            'password_confirmation' => 'password',
            'business_name' => 'New Cafe Co',
            'sector' => 'coffee',
            'referral_code' => 'INVITE88',
        ])->assertRedirect(route('onboarding.show'));

        $referred = \App\Models\Business::query()->where('name', 'New Cafe Co')->first();
        $this->assertNotNull($referred);
        $this->assertSame($business->id, $referred->referred_by_business_id);
        $this->assertSame(1, $referred->referral_credit_months);
        $this->assertTrue(
            $referred->trial_ends_at->greaterThan(now()->addDays(\App\Support\Plans::trialDays() + 20))
        );
        $this->assertDatabaseHas('business_referrals', [
            'referrer_business_id' => $business->id,
            'referred_business_id' => $referred->id,
            'status' => 'pending',
        ]);

        $referred->update(['onboarding_completed_at' => now()]);
        app(\App\Services\ReferralService::class)->qualifyForBusiness($referred->fresh());

        $this->assertDatabaseHas('business_referrals', [
            'referred_business_id' => $referred->id,
            'status' => 'rewarded',
        ]);
        $this->assertSame(1, $business->fresh()->referral_credit_months);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('loop.referral_dash_title', ['goal' => 3]));

        $this->actingAs($owner)
            ->get(route('settings.referrals'))
            ->assertOk()
            ->assertSee('INVITE88');

        $this->actingAs($admin)
            ->get(route('admin.referrals.program'))
            ->assertOk()
            ->assertSee(__('loop.admin_referral_program'));

        $this->actingAs($admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee(__('loop.customers_by_sector'));

        $this->actingAs($admin)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee(__('loop.billing_trial_settings'));

        $this->actingAs($admin)
            ->put(route('admin.settings.billing'), [
                'trial_days' => 10,
                'free_max_shops' => 1,
                'free_max_members' => 40,
                'free_max_monthly_visits' => 30,
                'block_till_when_trial_ends' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(30, \App\Support\BillingSettings::settings()['free_max_monthly_visits']);
    }

    public function test_expired_trial_blocks_till_on_free_plan(): void
    {
        foreach (\App\Support\Plans::catalog() as $key => $plan) {
            \App\Models\Plan::query()->create([
                'key' => $key,
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
            ]);
        }
        \App\Models\PlatformSetting::putValue(\App\Support\BillingSettings::KEY, \App\Support\BillingSettings::defaults());

        [$owner, $business, $shop] = $this->seedBusiness();
        $business->update([
            'plan_key' => 'free',
            'billing_status' => 'trialing',
            'trial_ends_at' => now()->subDay(),
        ]);

        $staff = User::factory()->frontDesk()->create([
            'phone' => '712999009',
            'business_id' => $business->id,
            'password' => 'password',
        ]);
        $customer = User::factory()->customer()->create([
            'phone' => '713888001',
            'password' => '1234',
            'profile_completed' => true,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Services\TillService::class)->recordSale($staff, $shop, $customer, 2000);
    }

    public function test_free_plan_blocks_second_shop(): void
    {
        foreach (\App\Support\Plans::catalog() as $key => $plan) {
            \App\Models\Plan::query()->create([
                'key' => $key,
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
            ]);
        }

        [$owner, $business] = array_slice($this->seedBusiness(), 0, 2);
        $business->update(['plan_key' => 'free', 'billing_status' => 'free']);

        $this->actingAs($owner)
            ->post(route('shops.store'), [
                'name' => 'Second Branch',
                'city' => 'Dar es Salaam',
                'address' => 'Another Street 12',
            ])
            ->assertRedirect(route('shops.index'))
            ->assertSessionHasErrors('plan');

        $this->assertSame(1, $business->shops()->count());
    }

    public function test_pricing_page_renders(): void
    {
        foreach (\App\Support\Plans::catalog() as $key => $plan) {
            \App\Models\Plan::query()->create([
                'key' => $key,
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
            ]);
        }

        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee('Starter')
            ->assertSee('25,000');
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
