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
            ->post(route('till.lookup'), [
                'shop_id' => $shop->id,
                'country_code' => '+255',
                'phone' => '713555666',
                'channel' => 'in_store',
            ])
            ->assertRedirect(route('till.ticket'));

        $this->actingAs($staff)
            ->post(route('till.register-customer'), [
                'first_name' => 'Kojo',
                'last_name' => 'Mensah',
            ])
            ->assertRedirect(route('till.ticket'));

        $this->assertDatabaseHas('users', [
            'phone' => '713555666',
            'role' => 'customer',
            'first_name' => 'Kojo',
        ]);

        $this->actingAs($staff)
            ->post(route('till.store'), [
                'shop_id' => $shop->id,
                'country_code' => '+255',
                'phone' => '713555666',
                'channel' => 'in_store',
                'amount_spent' => 5000,
            ])
            ->assertRedirect(route('till.index'));

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

        $business->update([
            'allow_pay_with_points' => true,
            'pay_spend_step' => 1000,
            'pay_points_per_step' => 2,
            'pay_points_max_percent' => 100,
        ]);
        $staff->unsetRelation('business');

        $this->actingAs($staff->fresh())
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

    public function test_standalone_redeem_does_not_require_a_sale(): void
    {
        [$owner, $business, $shop] = $this->seedBusiness();
        $staff = User::factory()->frontDesk()->create([
            'phone' => '712999088',
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
        $customer = User::factory()->customer()->create([
            'phone' => '713555777',
            'country' => 'TZ',
            'city' => 'Dar es Salaam',
            'password' => '1234',
            'profile_completed' => true,
        ]);
        $reward = \App\Models\Reward::create([
            'business_id' => $business->id,
            'name' => '5% off',
            'points_cost' => 4,
            'reward_type' => 'percent_off',
            'reward_value' => 5,
            'is_active' => true,
        ]);

        app(\App\Services\TillService::class)->recordSale($staff, $shop, $customer, 2000);

        $this->actingAs($staff)
            ->post(route('till.lookup'), [
                'shop_id' => $shop->id,
                'country_code' => '+255',
                'phone' => '713555777',
                'channel' => 'in_store',
            ])
            ->assertRedirect(route('till.ticket'));

        $this->actingAs($staff)
            ->post(route('till.redeem'), [
                'shop_id' => $shop->id,
                'country_code' => '+255',
                'phone' => '713555777',
                'reward_id' => $reward->id,
                'notes' => '5% off coffee — charged 4,750',
            ])
            ->assertRedirect(route('till.index'));

        $this->assertDatabaseHas('redemptions', [
            'reward_id' => $reward->id,
            'customer_id' => $customer->id,
            'points_spent' => 4,
            'notes' => '5% off coffee — charged 4,750',
        ]);
        $this->assertDatabaseHas('memberships', [
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'points_balance' => 0,
        ]);
        $this->assertNull(\App\Models\Redemption::first()->visit_id);
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
            ->assertSee(__('loop.settings'));

        $this->actingAs($owner)
            ->get(route('campaigns.show', $campaign))
            ->assertOk()
            ->assertSee('Earn');
    }

    public function test_admin_feature_flags_and_owner_daily_notifications(): void
    {
        \App\Models\PlatformSetting::putValue(\App\Support\FeatureFlags::KEY, \App\Support\FeatureFlags::defaults());

        $admin = User::factory()->admin()->create(['phone' => '710111222', 'password' => 'password']);
        $this->actingAs($admin)
            ->get(route('admin.settings', ['tab' => 'product']))
            ->assertOk()
            ->assertSee(__('loop.admin_product_updates'));

        $this->actingAs($admin)
            ->put(route('admin.settings.feature-flags'), [
                'pay_with_points' => 1,
                'premium_clients' => 1,
                'owner_daily_digest' => 1,
                'customer_unlock_hints' => 1,
                'birthday_campaigns' => 1,
                'welcome_campaigns' => 1,
                'streak_campaigns' => 1,
                'featured_product' => 1,
                'raffles' => 0,
                'content_studio' => 0,
            ])
            ->assertRedirect();

        $this->assertFalse(\App\Support\FeatureFlags::enabled('raffles'));
        $this->assertTrue(\App\Support\FeatureFlags::enabled('owner_daily_digest'));

        [$owner, $business] = $this->seedBusiness();
        Campaign::create([
            'business_id' => $business->id,
            'name' => 'Earn',
            'type' => 'earn',
            'spend_step' => 1000,
            'points_per_step' => 2,
            'starts_at' => now()->subDay(),
            'is_active' => true,
        ]);
        \App\Models\Reward::create([
            'business_id' => $business->id,
            'name' => '5% off',
            'points_cost' => 100,
            'reward_type' => 'percent_off',
            'reward_value' => 5,
            'is_active' => true,
        ]);

        $created = app(\App\Services\DailyNotificationService::class)->generateForBusiness($business);
        $this->assertGreaterThan(0, $created);

        $this->actingAs($owner)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(__('loop.notifications_title'));

        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $owner->id,
            'business_id' => $business->id,
            'audience' => 'owner',
        ]);
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

    public function test_public_pages_use_relative_in_app_links(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('127.0.0.1', $html);
        $this->assertStringNotContainsString('http://localhost', $html);
        $this->assertStringContainsString('href="/for-customers"', $html);
        $this->assertStringContainsString('href="/for-business"', $html);
        $this->assertStringContainsString('href="/customer/login"', $html);
        $this->assertStringContainsString('href="/business/register"', $html);
        $this->assertStringNotContainsString('id="pricing"', $html);
        $this->assertStringNotContainsString(__('loop.pricing_title'), $html);

        $business = $this->get('/for-business')->assertOk()->getContent();
        $this->assertStringContainsString('id="pricing"', $business);
        $this->assertStringNotContainsString('tel:+255747001001', $business);
        $this->assertStringNotContainsString(__('loop.loop_hotline_display'), $business);

        $customer = $this->get('/for-customers')->assertOk()->getContent();
        $this->assertStringNotContainsString('127.0.0.1', $customer);
        $this->assertStringContainsString('href="/discover"', $customer);

        $this->from('/for-customers')
            ->get('/locale/en')
            ->assertRedirect('/for-customers');

        $this->get('/locale/sw?return='.urlencode('http://localhost/sale/lookup'))
            ->assertRedirect(route('home'));
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
            ->get(route('onboarding.show', ['step' => 5]))
            ->assertOk()
            ->assertSee(__('loop.create_first_campaign'))
            ->assertDontSee('earn_with_discount');

        $this->actingAs($owner)
            ->post(route('onboarding.campaign'), [
                'template' => 'everyday_earn',
                'name' => 'Coastal Bites Points',
                'spend_step' => 1000,
                'points_per_step' => 20,
            ])
            ->assertRedirect(route('onboarding.show', ['step' => 6]));

        $this->assertDatabaseHas('campaigns', [
            'business_id' => $business->id,
            'template_key' => 'everyday_earn',
            'type' => 'earn',
            'name' => 'Coastal Bites Points',
            'spend_step' => 1000,
            'points_per_step' => 20,
        ]);
        $this->assertNull($business->fresh()->onboarding_completed_at);

        $this->actingAs($owner)
            ->get(route('onboarding.show', ['step' => 6]))
            ->assertOk()
            ->assertSee(__('loop.create_first_offer'));

        $this->actingAs($owner)
            ->post(route('onboarding.offers'), [
                'offers' => [
                    [
                        'reward_type' => 'percent_off',
                        'name' => '5% off',
                        'product_name' => '',
                        'points_cost' => 100,
                        'reward_value' => 5,
                    ],
                    [
                        'reward_type' => 'free_item',
                        'name' => 'Free item',
                        'product_name' => 'Burger',
                        'points_cost' => 100,
                        'reward_value' => 0,
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(2, $business->rewards()->count());
        $this->assertNotNull($business->fresh()->onboarding_completed_at);
        $this->assertDatabaseHas('campaigns', [
            'business_id' => $business->id,
            'name' => 'Coastal Bites Points',
        ]);
        // Offers are business-wide — not pivoted onto the campaign.
        $this->assertSame(0, $business->campaigns()->first()->rewards()->count());
    }

    public function test_online_presence_skips_physical_address(): void
    {
        $owner = User::factory()->owner()->create(['phone' => '712777011']);
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Net Juice',
            'slug' => 'net-juice',
            'sector' => 'other',
            'country' => 'TZ',
            'currency' => 'TZS',
            'branch_count' => 1,
            'logo_path' => 'business-logos/demo.png',
            'onboarding_completed_at' => null,
        ]);
        $owner->update(['business_id' => $business->id]);

        $this->actingAs($owner)
            ->post(route('onboarding.presence'), [
                'presence' => 'online',
            ])
            ->assertRedirect(route('onboarding.show', ['step' => 4, 'branch' => 1]));

        $this->assertSame('online', $business->fresh()->presence);
        $this->assertSame(1, (int) $business->fresh()->branch_count);

        $this->actingAs($owner)
            ->get(route('onboarding.show', ['step' => 3]))
            ->assertRedirect(route('onboarding.show', ['step' => 4, 'branch' => 1]));

        $this->actingAs($owner)
            ->post(route('onboarding.shop'), [
                'city' => 'Online',
                'address' => '',
                'hotline' => '712333444',
            ])
            ->assertRedirect(route('onboarding.show', ['step' => 5]));

        $shop = $business->shops()->first();
        $this->assertNotNull($shop);
        $this->assertSame('Online', $shop->city);
        $this->assertTrue($shop->address === null || $shop->address === '');
    }

    public function test_physical_presence_moves_to_branches_step(): void
    {
        $owner = User::factory()->owner()->create(['phone' => '712777012']);
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Street Grill',
            'slug' => 'street-grill',
            'sector' => 'restaurants',
            'country' => 'TZ',
            'currency' => 'TZS',
            'branch_count' => 1,
            'logo_path' => 'business-logos/demo.png',
            'onboarding_completed_at' => null,
        ]);
        $owner->update(['business_id' => $business->id]);

        $this->actingAs($owner)
            ->post(route('onboarding.presence'), [
                'presence' => 'physical',
            ])
            ->assertRedirect(route('onboarding.show', ['step' => 3]));

        $this->actingAs($owner)
            ->get(route('onboarding.show', ['step' => 3]))
            ->assertOk()
            ->assertSee(__('loop.how_many_branches'));

        $this->actingAs($owner)
            ->post(route('onboarding.branches'), [
                'branch_count' => 2,
            ])
            ->assertRedirect(route('onboarding.show', ['step' => 4, 'branch' => 1]));

        $this->assertSame('physical', $business->fresh()->presence);
        $this->assertSame(2, (int) $business->fresh()->branch_count);
    }

    public function test_featured_product_bonus_requires_till_confirmation(): void
    {
        [$owner, $business, $shop] = $this->seedBusiness();
        Campaign::create([
            'business_id' => $business->id,
            'name' => 'Featured push',
            'type' => 'product_push',
            'spend_step' => 1000,
            'points_per_step' => 2,
            'bonus_points' => 15,
            'featured_product_name' => 'New Coffee',
            'starts_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $staff = User::factory()->frontDesk()->create([
            'phone' => '712999102',
            'business_id' => $business->id,
            'password' => 'password',
        ]);
        $customer = User::factory()->customer()->create([
            'phone' => '713999102',
            'country' => 'TZ',
            'city' => 'Dar es Salaam',
            'password' => '1234',
            'profile_completed' => true,
        ]);

        $without = app(\App\Services\TillService::class)->recordSale(
            $staff, $shop, $customer, 2000, null, 'in_store', false, null, false
        );
        $this->assertSame(4, $without->points_earned);

        $customer2 = User::factory()->customer()->create([
            'phone' => '713999103',
            'country' => 'TZ',
            'city' => 'Dar es Salaam',
            'password' => '1234',
            'profile_completed' => true,
        ]);
        $with = app(\App\Services\TillService::class)->recordSale(
            $staff, $shop, $customer2, 2000, null, 'in_store', false, null, true
        );
        $this->assertSame(19, $with->points_earned);
    }

    public function test_campaign_create_requires_an_offer(): void
    {
        $owner = User::factory()->owner()->create(['phone' => '712777002']);
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Offer Gate',
            'slug' => 'offer-gate',
            'sector' => 'coffee',
            'country' => 'TZ',
            'currency' => 'TZS',
            'city' => 'Dar es Salaam',
            'onboarding_completed_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);

        $this->actingAs($owner)
            ->get(route('campaigns.create'))
            ->assertOk();

        $this->assertTrue($business->fresh()->hasRedeemableOffer());
        $this->assertDatabaseHas('rewards', [
            'business_id' => $business->id,
            'is_default' => true,
        ]);
    }

    public function test_raffle_unlocks_after_member_threshold(): void
    {
        \App\Models\PlatformSetting::putValue(\App\Support\GrowthSettings::KEY, [
            ...\App\Support\GrowthSettings::defaults(),
            'raffle_min_members' => 2,
            'raffle_max_winners_percent' => 30,
        ]);

        $owner = User::factory()->owner()->create(['phone' => '712777003']);
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Raffle Shop',
            'slug' => 'raffle-shop',
            'sector' => 'coffee',
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

        $this->actingAs($owner)
            ->get(route('raffles.index'))
            ->assertOk()
            ->assertSee(__('loop.raffle_locked_title'));

        foreach (['713777101', '713777102'] as $phone) {
            $customer = User::factory()->customer()->create(['phone' => $phone]);
            \App\Models\Membership::create([
                'business_id' => $business->id,
                'shop_id' => $shop->id,
                'customer_id' => $customer->id,
                'points_balance' => 10,
                'lifetime_points' => 10,
                'joined_at' => now(),
                'member_code' => 'M-'.$phone,
            ]);
        }

        $this->actingAs($owner)
            ->get(route('raffles.index'))
            ->assertOk()
            ->assertSee(__('loop.create_raffle'));

        // 2 members × 30% = max 1 winner
        $this->assertSame(1, \App\Support\GrowthSettings::maxWinnersForMembers(2));

        $this->actingAs($owner)
            ->post(route('raffles.store'), [
                'name' => 'Friday Draw',
                'prize_name' => 'Free coffee',
                'prize_type' => 'free_item',
                'winners_count' => 2,
                'frequency' => 'weekly',
                'draw_at' => now()->addDays(3)->format('Y-m-d'),
                'claim_days' => 7,
            ])
            ->assertSessionHasErrors('winners_count');

        $this->actingAs($owner)
            ->post(route('raffles.store'), [
                'name' => 'Friday Draw',
                'prize_name' => 'Free coffee',
                'prize_type' => 'free_item',
                'winners_count' => 1,
                'frequency' => 'weekly',
                'draw_at' => now()->addDays(3)->format('Y-m-d'),
                'claim_days' => 7,
            ])
            ->assertRedirect();

        $raffle = $business->raffles()->first();
        $this->assertNotNull($raffle);

        $this->actingAs($owner)
            ->post(route('raffles.draw', $raffle))
            ->assertRedirect(route('raffles.live', $raffle));

        $this->assertSame(1, $raffle->fresh()->winners()->count());
    }

    public function test_raffle_max_winners_is_thirty_percent_of_members(): void
    {
        $this->assertSame(3, \App\Support\GrowthSettings::maxWinnersForMembers(10));
        $this->assertSame(3, \App\Support\GrowthSettings::maxWinnersForMembers(11));
        $this->assertSame(6, \App\Support\GrowthSettings::maxWinnersForMembers(20));
        $this->assertSame(10, \App\Support\GrowthSettings::raffleMinMembers());
    }

    public function test_campaign_templates_are_earn_only_without_bundled_offers(): void
    {
        $this->assertArrayNotHasKey('earn_with_discount', \App\Support\CampaignTemplates::all());
        $this->assertArrayHasKey('faster_earn', \App\Support\CampaignTemplates::all());

        $offers = \App\Support\OfferTemplates::forSector('coffee');
        $keys = collect($offers)->pluck('key')->all();
        $this->assertContains('percent_5', $keys);
        $this->assertContains('percent_10', $keys);
        $this->assertContains('free_item', $keys);
        $this->assertContains('fixed_off', $keys);
        $this->assertNotContains('free_coffee_100', $keys);
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
        \App\Models\PlatformSetting::putValue(\App\Support\ReferralProgram::KEY, \App\Support\ReferralProgram::defaults());

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
        $this->assertSame(0, $referred->referral_credit_months);
        $this->assertSame(5, $referred->referral_credit_days);
        $this->assertTrue(
            $referred->trial_ends_at->greaterThan(now()->addDays(\App\Support\Plans::trialDays() + 3))
        );
        $this->assertTrue(
            $referred->trial_ends_at->lessThanOrEqualTo(now()->addDays(\App\Support\Plans::trialDays() + 6)->endOfDay())
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
        $this->assertSame(3, $business->fresh()->referral_credit_days);

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
            ->assertRedirect(route('admin.settings', ['tab' => 'referrals']));

        $this->actingAs($admin)
            ->get(route('admin.affiliates.index', ['tab' => 'settings']))
            ->assertRedirect(route('admin.settings', ['tab' => 'affiliates']));

        $this->actingAs($admin)
            ->get(route('admin.settings', ['tab' => 'affiliates']))
            ->assertOk()
            ->assertSee(__('loop.affiliate_program_settings'));

        $this->actingAs($admin)
            ->get(route('admin.reports.index', ['tab' => 'sectors']))
            ->assertOk()
            ->assertSee(__('loop.customers_by_sector'));

        $this->actingAs($admin)
            ->get(route('admin.settings', ['tab' => 'billing']))
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
        \App\Models\PlatformSetting::putValue(\App\Support\BillingSettings::KEY, [
            ...\App\Support\BillingSettings::defaults(),
            'grace_days' => 0,
            'block_till_when_trial_ends' => true,
        ]);

        [$owner, $business, $shop] = $this->seedBusiness();
        $business->update([
            'plan_key' => 'free',
            'billing_status' => 'past_due',
            'trial_ends_at' => now()->subDay(),
            'past_due_at' => now()->subDay(),
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

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee(__('loop.pricing_title'), false);

        $this->get(route('landing.business'))
            ->assertOk()
            ->assertSee(__('loop.pricing_title'))
            ->assertDontSee('tel:+255747001001', false)
            ->assertDontSee(__('loop.loop_hotline_display'), false);
    }

    public function test_owner_can_upgrade_plan_and_admin_can_export_reports(): void
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

        [$owner, $business] = array_slice($this->seedBusiness(), 0, 2);
        $business->update(['plan_key' => 'free', 'billing_status' => 'trialing', 'trial_ends_at' => now()->addDays(3)]);

        $this->actingAs($owner)
            ->get(route('billing.show'))
            ->assertOk()
            ->assertSee(__('loop.upgrade_title'));

        $this->actingAs($owner)
            ->post(route('billing.choose'), [
                'plan_key' => 'growth',
                'phone' => '714123456',
                'country' => 'TZ',
            ])
            ->assertRedirect();

        $intent = \App\Models\PaymentIntent::query()->latest('id')->first();
        $this->assertNotNull($intent);
        $this->assertSame('processing', $intent->status);

        $this->actingAs($owner)
            ->post(route('payments.stub-confirm', $intent))
            ->assertRedirect(route('billing.show'));

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'plan_key' => 'growth',
            'billing_status' => 'active',
        ]);

        $admin = User::factory()->admin()->create(['phone' => '710000222', 'password' => 'password']);
        $this->actingAs($admin)
            ->get(route('admin.reports.export', ['type' => 'customers_by_sector', 'format' => 'csv']))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($admin)
            ->get(route('admin.reports.export', ['type' => 'overview', 'format' => 'json']))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.reports.export', [
                'reports' => ['overview', 'customers_by_sector'],
                'formats' => ['csv', 'json'],
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');
    }

    public function test_owner_pages_for_customers_transactions_and_branch_view(): void
    {
        [$owner, $business, $shop] = $this->seedBusiness();

        $customer = User::factory()->customer()->create([
            'phone' => '713777001',
            'first_name' => 'Asha',
            'last_name' => 'Mwamba',
        ]);

        \App\Models\Membership::create([
            'business_id' => $business->id,
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'points_balance' => 40,
            'lifetime_points' => 40,
            'joined_at' => now()->subDay(),
        ]);

        $this->actingAs($owner)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Asha');

        $this->actingAs($owner)
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Asha Mwamba');

        $this->actingAs($owner)
            ->get(route('transactions.index'))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('shops.index'))
            ->assertOk()
            ->assertSee($shop->name)
            ->assertDontSee(route('shops.edit', $shop), false);

        $this->actingAs($owner)
            ->get(route('shops.show', $shop))
            ->assertOk()
            ->assertSee(__('loop.edit'))
            ->assertSee(__('loop.branch_details'))
            ->assertDontSee(__('loop.shared_logo_hint'))
            ->assertSee(__('loop.settings'));

        $this->actingAs($owner)
            ->get(route('transactions.index', ['q' => 'Asha', 'period' => 'all']))
            ->assertOk()
            ->assertSee(__('loop.search_name_or_phone'));

        $this->actingAs($owner)
            ->get(route('customers.index', ['q' => 'Asha', 'tab' => 'all']))
            ->assertOk()
            ->assertSee(__('loop.search_name_or_phone'))
            ->assertSee('Asha');

        $this->actingAs($owner)
            ->put(route('shops.update', $shop), [
                'name' => 'Harbor Beans Downtown',
                'city' => 'Dar es Salaam',
                'address' => 'Samora Ave',
                'country_code' => '+255',
                'phone' => '712000111',
                'is_active' => '1',
            ])
            ->assertRedirect(route('shops.show', $shop))
            ->assertSessionHas('confirm');

        $this->assertDatabaseHas('shops', [
            'id' => $shop->id,
            'name' => 'Harbor Beans Downtown',
            'phone' => '+255 712000111',
            'logo_path' => null,
        ]);

        $this->actingAs($owner)
            ->get(route('business.edit'))
            ->assertOk()
            ->assertSee(__('loop.business_logo'));

        $this->actingAs($owner)
            ->get(route('staff.index'))
            ->assertOk()
            ->assertSee(__('loop.your_team'));
    }

    public function test_affiliate_apply_approve_activate_and_attach_promo(): void
    {
        \App\Models\PlatformSetting::putValue(\App\Support\AffiliateProgram::KEY, \App\Support\AffiliateProgram::defaults());

        $admin = User::factory()->admin()->create(['phone' => '710999001']);

        $this->get(route('affiliates.landing'))
            ->assertOk()
            ->assertSee(__('loop.become_affiliate'));

        $this->post(route('affiliates.apply.store'), [
            'first_name' => 'Joy',
            'last_name' => 'Affiliate',
            'country' => 'TZ',
            'phone' => '715555001',
            'email' => 'joy@loop.test',
            'id_type' => 'national_id',
            'id_number' => 'ID-123456',
            'city' => 'Dar es Salaam',
            'district' => 'Ilala',
            'address' => 'Samora Avenue',
        ])->assertRedirect(route('affiliates.status'));

        $affiliate = \App\Models\Affiliate::query()->where('phone', '715555001')->first();
        $this->assertNotNull($affiliate);
        $this->assertSame('pending', $affiliate->status);

        $this->actingAs($admin)
            ->post(route('admin.affiliates.decide', $affiliate), [
                'decision' => 'approved',
                'decision_note' => 'Looks good',
            ])
            ->assertRedirect();

        $affiliate->refresh();
        $this->assertSame('approved', $affiliate->status);
        $this->assertNotNull($affiliate->promo_code);
        $this->assertNotNull($affiliate->tracking_code);

        $this->post(route('logout'));

        $this->post(route('affiliate.activate.store'), [
            'country_code' => $affiliate->country_code,
            'phone' => $affiliate->phone,
            'password' => 'password',
            'password_confirmation' => 'password',
            'pin' => '1234',
            'pin_confirmation' => '1234',
        ])->assertRedirect(route('affiliate.setup'));

        $affiliate->refresh();
        $this->assertSame('active', $affiliate->status);
        $this->assertNull($affiliate->setup_completed_at);

        $this->actingAs($affiliate->user)
            ->post(route('affiliate.setup.store'), [
                'promo_code' => 'JOYLOOP',
            ])
            ->assertRedirect(route('affiliate.dashboard'));

        $affiliate->refresh();
        $this->assertSame('JOYLOOP', $affiliate->promo_code);
        $this->assertNotNull($affiliate->setup_completed_at);

        $this->post(route('logout'));

        $this->post(route('business.register'), [
            'first_name' => 'Biz',
            'last_name' => 'Owner',
            'country' => 'TZ',
            'phone' => '715555002',
            'password' => 'password',
            'password_confirmation' => 'password',
            'business_name' => 'Affiliate Cafe',
            'sector' => 'coffee',
            'referral_code' => 'JOYLOOP',
        ])->assertRedirect(route('onboarding.show'));

        $business = \App\Models\Business::query()->where('name', 'Affiliate Cafe')->first();
        $this->assertNotNull($business);
        $this->assertSame($affiliate->id, $business->referred_by_affiliate_id);
        $this->assertDatabaseHas('affiliate_referrals', [
            'affiliate_id' => $affiliate->id,
            'business_id' => $business->id,
        ]);
    }

    public function test_customer_landing_page_loads(): void
    {
        $this->get(route('landing.customer'))
            ->assertOk()
            ->assertSee('Loop', false)
            ->assertSee(__('loop.customer_landing_title'));
    }

    public function test_scout_invite_opens_whatsapp_with_platform_base_url(): void
    {
        \App\Models\PlatformSetting::putValue(\App\Support\PlatformUrl::KEY, [
            'base_url' => 'https://loop.example.com',
        ]);
        \App\Support\PlatformUrl::applyRootUrl();

        $customer = User::factory()->customer()->create([
            'phone' => '713444001',
            'first_name' => 'Neema',
            'last_name' => 'Juma',
            'country' => 'TZ',
            'city' => 'Dar es Salaam',
            'profile_completed' => true,
            'password' => '1234',
        ]);

        $response = $this->actingAs($customer)->post(route('business-invites.store'), [
            'business_name' => 'Harbor Beans',
            'share_via' => 'whatsapp',
            'country_code' => '+255',
            'city' => 'Dar es Salaam',
        ]);

        $response->assertRedirect();
        $target = $response->headers->get('Location');
        $this->assertNotNull($target);
        $this->assertStringContainsString('https://wa.me/', $target);
        $this->assertStringContainsString(rawurlencode('https://loop.example.com/business/register'), $target);
        $this->assertStringContainsString('scout%3D'.$customer->id, $target);

        $this->assertDatabaseHas('business_invites', [
            'customer_id' => $customer->id,
            'business_name' => 'Harbor Beans',
            'status' => 'pending',
        ]);
    }

    public function test_settings_hub_is_source_of_truth_for_program_config(): void
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

        $admin = User::factory()->admin()->create(['phone' => '710000333', 'password' => 'password']);
        $plan = \App\Models\Plan::query()->where('key', 'growth')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.plans.index'))
            ->assertOk()
            ->assertSee(__('loop.edit_in_settings_hub'));

        $this->actingAs($admin)
            ->put(route('admin.plans.update', $plan), [
                'name' => 'Hacked',
                'price_monthly' => 1,
                'currency' => 'TZS',
                'sort_order' => 1,
            ])
            ->assertRedirect(route('admin.settings', ['tab' => 'packages']));

        $this->assertSame('Growth', $plan->fresh()->name);

        $this->actingAs($admin)
            ->put(route('admin.settings.plans.update', $plan), [
                'name' => 'Growth Plus',
                'tagline' => 'More till energy',
                'price_monthly' => 45000,
                'currency' => 'TZS',
                'sort_order' => 2,
                'is_public' => 1,
                'features_text' => "More shops\nMore members",
            ])
            ->assertRedirect(route('admin.settings', ['tab' => 'packages']));

        $this->assertSame('Growth Plus', $plan->fresh()->name);
        $this->assertSame(45000, $plan->fresh()->price_monthly);

        $this->actingAs($admin)
            ->put(route('admin.affiliates.settings'), [])
            ->assertRedirect(route('admin.settings', ['tab' => 'affiliates']));

        $this->actingAs($admin)
            ->put(route('admin.referrals.program.update'), [])
            ->assertRedirect(route('admin.settings', ['tab' => 'referrals']));

        $this->actingAs($admin)
            ->get(route('admin.integrations.index', ['tab' => 'automation']))
            ->assertRedirect(route('admin.settings', ['tab' => 'notifications']));

        $this->actingAs($admin)
            ->get(route('admin.integrations.index'))
            ->assertOk()
            ->assertDontSee(__('loop.integrations_tab_automation'))
            ->assertSee(__('loop.payin_balance'));

        $this->actingAs($admin)
            ->put(route('admin.settings.notifications'), [
                'owner_in_app' => 1,
                'holiday_messages' => 1,
                'trial_reminders' => 1,
                'quiet_hours_start' => 22,
                'quiet_hours_end' => 6,
            ])
            ->assertRedirect(route('admin.settings', ['tab' => 'notifications']));

        $this->assertTrue(\App\Support\NotificationSettings::settings()['holiday_messages']);
        $this->assertSame(22, \App\Support\NotificationSettings::settings()['quiet_hours_start']);
    }

    public function test_payin_webhook_verifies_signature_and_marks_paid(): void
    {
        \App\Models\PlatformSetting::putValue(\App\Support\IntegrationSettings::KEY, [
            'payments' => [
                'primary' => 'payin',
                'secondary' => null,
                'providers' => [
                    'payin' => [
                        'enabled' => true,
                        'mode' => 'sandbox',
                        'api_key' => 'pk_test',
                        'api_secret' => 'sk_test',
                        'webhook_secret' => 'whsec_test',
                        'docs_url' => 'https://docs.payin.co.tz/',
                    ],
                ],
            ],
            'messaging' => \App\Support\IntegrationSettings::defaults()['messaging'],
            'email' => \App\Support\IntegrationSettings::defaults()['email'],
        ]);

        [$owner, $business] = array_slice($this->seedBusiness(), 0, 2);
        $intent = \App\Models\PaymentIntent::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'purpose' => 'plan_upgrade',
            'plan_key' => 'growth',
            'amount' => 35000,
            'currency' => 'TZS',
            'phone' => '255714123456',
            'country' => 'TZ',
            'provider' => 'payin',
            'provider_ref' => 'PAYTESTREF001',
            'status' => \App\Models\PaymentIntent::STATUS_PROCESSING,
            'description' => 'Test',
            'meta' => ['plan_name' => 'Growth'],
        ]);

        $payload = json_encode([
            'request_ref' => 'PAYTESTREF001',
            'status' => 'completed',
        ], JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $badSig = 'deadbeef';

        $this->call(
            'POST',
            route('payments.webhook.payin'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_PAYIN_SIGNATURE' => $badSig,
                'HTTP_X_PAYIN_TIMESTAMP' => $timestamp,
            ],
            $payload
        )->assertStatus(401);

        $this->assertSame(\App\Models\PaymentIntent::STATUS_PROCESSING, $intent->fresh()->status);

        $goodSig = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');
        $this->call(
            'POST',
            route('payments.webhook.payin'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_PAYIN_SIGNATURE' => $goodSig,
                'HTTP_X_PAYIN_TIMESTAMP' => $timestamp,
            ],
            $payload
        )->assertOk();

        $this->assertTrue($intent->fresh()->isPaid());
        $this->assertSame('growth', $business->fresh()->plan_key);
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
