<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\InAppNotification;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\User;
use App\Support\FeatureFlags;
use App\Support\Plans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoopProductPassTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_moves_insights_to_notifications_and_shows_sales_amount(): void
    {
        [$owner, $business] = $this->seedBusiness();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($business->currency, false)
            ->assertSee(__('loop.pulse_what_happened'))
            ->assertDontSee(__('loop.performance'), false)
            ->assertSee(__('loop.notifications'))
            ->assertSee(__('loop.create_next_offer'));

        app(\App\Services\DailyNotificationService::class)->generateForBusiness($business->fresh());
        $this->assertGreaterThanOrEqual(2, InAppNotification::query()->where('user_id', $owner->id)->count());
    }

    public function test_till_requires_a_branch_choice(): void
    {
        [$owner, $business, $shop] = $this->seedBusiness();
        Shop::create([
            'business_id' => $business->id,
            'name' => 'Second',
            'city' => 'Dar es Salaam',
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('till.index'))
            ->assertOk()
            ->assertSee(__('loop.choose_branch'))
            ->assertSee(__('loop.choose_branch_first_blurb'))
            ->assertDontSee(__('loop.look_up'));

        $this->actingAs($owner)
            ->post(route('till.lookup'), [
                'country_code' => '+255',
                'phone' => '713555111',
                'channel' => 'in_store',
            ])
            ->assertRedirect(route('till.index'))
            ->assertSessionHasErrors('shop_id');

        $this->actingAs($owner)
            ->post(route('till.branch'), ['shop_id' => $shop->id])
            ->assertRedirect(route('till.index'));

        $this->actingAs($owner)
            ->get(route('till.index'))
            ->assertOk()
            ->assertSee(__('loop.selling_at'))
            ->assertSee('Main')
            ->assertSee(__('loop.whos_buying'))
            ->assertSee(__('loop.continue'));

        $this->actingAs($owner)
            ->post(route('till.lookup'), [
                'shop_id' => $shop->id,
                'country_code' => '+255',
                'phone' => '713555111',
                'channel' => 'in_store',
            ])
            ->assertRedirect(route('till.ticket'));
    }

    public function test_front_desk_can_be_assigned_multiple_branches(): void
    {
        [$owner, $business, $shop] = $this->seedBusiness();
        $second = Shop::create([
            'business_id' => $business->id,
            'name' => 'Branch B',
            'city' => 'Dar es Salaam',
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('staff.store'), [
                'first_name' => 'Neema',
                'last_name' => 'Desk',
                'country_code' => '+255',
                'phone' => '712444001',
                'password' => 'password',
                'shop_ids' => [$shop->id, $second->id],
            ])
            ->assertRedirect();

        $staff = User::query()->where('phone', '712444001')->first();
        $this->assertNotNull($staff);
        $this->assertEqualsCanonicalizing([$shop->id, $second->id], $staff->assignedShops()->pluck('shops.id')->all());

        $this->actingAs($owner)
            ->post(route('staff.store'), [
                'first_name' => 'Asha',
                'last_name' => 'Till',
                'country_code' => '+255',
                'phone' => '712444002',
                'password' => 'password',
                'shop_ids' => [$shop->id],
            ])
            ->assertSessionHasErrors('shop_ids');
    }

    public function test_owner_can_reassign_front_desk_branches(): void
    {
        [$owner, $business, $shop] = $this->seedBusiness();
        $second = Shop::create([
            'business_id' => $business->id,
            'name' => 'Branch B',
            'city' => 'Dar es Salaam',
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('staff.store'), [
                'first_name' => 'Neema',
                'last_name' => 'Desk',
                'country_code' => '+255',
                'phone' => '712444011',
                'password' => 'password',
                'shop_ids' => [$shop->id, $second->id],
            ])
            ->assertRedirect();

        $neema = User::query()->where('phone', '712444011')->first();
        $this->actingAs($owner)
            ->patch(route('staff.shops', $neema), ['shop_ids' => [$shop->id]])
            ->assertRedirect();
        $this->assertEqualsCanonicalizing([$shop->id], $neema->assignedShops()->pluck('shops.id')->all());

        $this->actingAs($owner)
            ->post(route('staff.store'), [
                'first_name' => 'Asha',
                'last_name' => 'Till',
                'country_code' => '+255',
                'phone' => '712444012',
                'password' => 'password',
                'shop_ids' => [$second->id],
            ])
            ->assertRedirect();

        $asha = User::query()->where('phone', '712444012')->first();
        $this->actingAs($owner)
            ->patch(route('staff.shops', $neema), ['shop_ids' => [$shop->id, $second->id]])
            ->assertSessionHasErrors('shop_ids');
        $this->assertEqualsCanonicalizing([$second->id], $asha->assignedShops()->pluck('shops.id')->all());
    }

    public function test_kenya_registration_maps_kes_currency(): void
    {
        $this->post('/business/register', [
            'first_name' => 'Amina',
            'last_name' => 'Owusu',
            'country' => 'KE',
            'phone' => '0712111333',
            'password' => 'password',
            'password_confirmation' => 'password',
            'business_name' => 'Nairobi Cafe',
            'sector' => 'coffee',
        ])->assertRedirect(route('onboarding.show'));

        $this->assertDatabaseHas('businesses', [
            'name' => 'Nairobi Cafe',
            'country' => 'KE',
            'currency' => 'KES',
        ]);
    }

    public function test_settings_pages_use_back_icons_not_rudi_links(): void
    {
        [$owner] = $this->seedBusiness();
        foreach (Plans::catalog() as $key => $plan) {
            Plan::query()->updateOrCreate(['key' => $key], [
                'name' => $plan['name'],
                'tagline' => $plan['tagline'],
                'price_monthly' => $plan['price_monthly'],
                'currency' => $plan['currency'],
                'sort_order' => $plan['sort_order'],
                'is_public' => true,
                'features' => $plan['features'],
                'has_raffles' => $plan['has_raffles'] ?? false,
                'has_sms' => $plan['has_sms'] ?? false,
            ]);
        }

        $this->actingAs($owner)
            ->get(route('billing.show'))
            ->assertOk()
            ->assertSee('aria-label="'.__('loop.back').'"', false)
            ->assertSee('billingPayConfirm', false)
            ->assertDontSee('mt-4 block text-center text-sm font-semibold text-ink-muted underline', false);
    }

    public function test_tanzania_messaging_is_gated_by_plan_and_sender_id_length(): void
    {
        [$owner, $business] = $this->seedBusiness();
        $business->update(['plan_key' => 'growth', 'billing_status' => 'active', 'country' => 'TZ']);
        $owner->unsetRelation('ownedBusiness');
        \App\Models\PlatformSetting::putValue(\App\Support\FeatureFlags::KEY, FeatureFlags::defaults());

        $this->actingAs($owner)
            ->get(route('members.messages.index'))
            ->assertOk()
            ->assertSee(__('loop.sms_plan_locked_title'));

        $business->update(['plan_key' => 'scale']);
        $owner->unsetRelation('ownedBusiness');
        Plan::query()->updateOrCreate(['key' => 'scale'], [
            'name' => 'Scale',
            'price_monthly' => 120000,
            'currency' => 'TZS',
            'has_sms' => true,
            'has_raffles' => true,
            'is_public' => true,
            'sort_order' => 4,
        ]);

        $this->actingAs($owner)
            ->get(route('members.messages.index'))
            ->assertOk()
            ->assertSee(__('loop.register_sender_id'));

        $this->actingAs($owner)
            ->post(route('members.messages.sender'), [
                'code' => 'TOOLONGSENDER',
                'phone' => '712000009',
            ])
            ->assertSessionHasErrors('code');

        $business->update(['country' => 'KE', 'currency' => 'KES']);
        $owner->unsetRelation('ownedBusiness');
        $this->actingAs($owner)
            ->get(route('members.messages.index'))
            ->assertOk()
            ->assertSee(__('loop.sms_country_unsupported_title'));
    }

    public function test_members_and_affiliates_get_at_least_two_daily_notifications(): void
    {
        \App\Models\PlatformSetting::putValue(\App\Support\FeatureFlags::KEY, FeatureFlags::defaults());
        $customer = User::factory()->customer()->create(['phone' => '713888001']);
        $affiliate = User::factory()->create([
            'phone' => '716888001',
            'role' => User::ROLE_AFFILIATE,
            'password' => 'password',
        ]);

        $daily = app(\App\Services\DailyNotificationService::class);
        $this->assertSame(2, $daily->generateForCustomer($customer));
        $this->assertSame(2, $daily->generateForAffiliate($affiliate));
    }

    public function test_free_item_offer_requires_a_product_name(): void
    {
        [$owner] = $this->seedBusiness();

        $this->actingAs($owner)
            ->post(route('rewards.store'), [
                'name' => 'Free pastry',
                'reward_type' => 'free_item',
                'points_cost' => 80,
            ])
            ->assertSessionHasErrors('product_name');

        $this->actingAs($owner)
            ->post(route('rewards.store'), [
                'name' => 'Free pastry',
                'reward_type' => 'free_item',
                'product_name' => 'Cinnamon roll',
                'points_cost' => 80,
            ])
            ->assertRedirect();
    }

    public function test_free_item_create_wizard_renders_alpine_setup(): void
    {
        [$owner] = $this->seedBusiness();

        $html = $this->actingAs($owner)
            ->get(route('rewards.create'))
            ->assertOk()
            ->assertSee('offerWizard', false)
            ->assertSee(__('loop.tie_to_product'), false)
            ->getContent();

        $this->assertStringContainsString("persistKey: 'loop.offerWizard.create',", $html);
        $this->assertStringNotContainsString("persistKey: 'loop.offerWizard.create',\"", $html);
    }

    public function test_till_shows_earned_offers_on_the_next_ticket(): void
    {
        [$owner, $business, $shop] = $this->seedBusiness();
        \App\Models\Campaign::create([
            'business_id' => $business->id,
            'name' => 'Earn',
            'type' => 'earn',
            'spend_step' => 1000,
            'points_per_step' => 2,
            'starts_at' => now()->subDay(),
            'is_active' => true,
        ]);
        $offer = \App\Models\Reward::create([
            'business_id' => $business->id,
            'name' => 'Free drink',
            'product_name' => 'Latte',
            'points_cost' => 100,
            'reward_type' => 'free_item',
            'reward_value' => 0,
            'is_active' => true,
        ]);
        $customer = User::factory()->customer()->create(['phone' => '713777009']);
        $newCustomer = User::factory()->customer()->create(['phone' => '713777010']);

        $this->actingAs($owner)
            ->post(route('till.store'), [
                'shop_id' => $shop->id,
                'country_code' => '+255',
                'phone' => '713777010',
                'channel' => 'in_store',
                'amount_spent' => 50000,
                'reward_id' => $offer->id,
            ])
            ->assertSessionHasErrors('reward_id');

        app(\App\Services\TillService::class)->recordSale($owner, $shop, $customer, 50000);
        $membership = $business->memberships()->where('customer_id', $customer->id)->first();
        $this->assertSame(100, (int) $membership->points_balance);
        $this->assertSame(100, $membership->redeemablePoints());
        $this->assertTrue($membership->availableRewards()->contains('id', $offer->id));

        $this->actingAs($owner)
            ->post(route('till.lookup'), [
                'shop_id' => $shop->id,
                'country_code' => '+255',
                'phone' => '713777009',
                'channel' => 'in_store',
            ])
            ->assertRedirect(route('till.ticket'));

        $this->actingAs($owner)
            ->get(route('till.ticket'))
            ->assertOk()
            ->assertSee('Free drink', false);

        $this->actingAs($owner)
            ->post(route('till.store'), [
                'shop_id' => $shop->id,
                'country_code' => '+255',
                'phone' => '713777009',
                'channel' => 'in_store',
                'amount_spent' => 0,
                'reward_id' => $offer->id,
            ])
            ->assertRedirect();

        $this->assertSame(0, (int) $membership->fresh()->points_balance);
        $this->assertNotNull($newCustomer->fresh());
    }

    public function test_product_push_bonus_unlocks_every_offer_type_on_the_next_ticket(): void
    {
        [$owner, $business, $shop] = $this->seedBusiness();
        \App\Models\Campaign::create([
            'business_id' => $business->id,
            'name' => 'Featured mocha',
            'type' => 'product_push',
            'bonus_points' => 100,
            'featured_product_name' => 'Mocha',
            'starts_at' => now()->subDay(),
            'is_active' => true,
        ]);
        $percent = \App\Models\Reward::create([
            'business_id' => $business->id,
            'name' => '5% off anything',
            'points_cost' => 100,
            'reward_type' => 'percent_off',
            'reward_value' => 5,
            'is_active' => true,
        ]);
        $fixed = \App\Models\Reward::create([
            'business_id' => $business->id,
            'name' => '2k off the bill',
            'points_cost' => 100,
            'reward_type' => 'fixed_off',
            'reward_value' => 2000,
            'is_active' => true,
        ]);
        $free = \App\Models\Reward::create([
            'business_id' => $business->id,
            'name' => 'Free pastry',
            'product_name' => 'Pastry',
            'points_cost' => 100,
            'reward_type' => 'free_item',
            'reward_value' => 0,
            'is_active' => true,
        ]);
        $customer = User::factory()->customer()->create(['phone' => '713777011']);

        $without = app(\App\Services\TillService::class)->recordSale(
            $owner, $shop, $customer, 5000, null, 'in_store', false, null, false
        );
        $this->assertSame(0, $without->points_earned);

        $with = app(\App\Services\TillService::class)->recordSale(
            $owner, $shop, $customer, 5000, null, 'in_store', false, null, true
        );
        $this->assertSame(100, $with->points_earned);

        $membership = $business->memberships()->where('customer_id', $customer->id)->first();
        $this->assertSame(100, (int) $membership->points_balance);
        $this->assertSame(100, $membership->redeemablePoints());
        $this->assertTrue($membership->availableRewards()->contains('id', $percent->id));
        $this->assertTrue($membership->availableRewards()->contains('id', $fixed->id));
        $this->assertTrue($membership->availableRewards()->contains('id', $free->id));

        $this->actingAs($owner)
            ->post(route('till.lookup'), [
                'shop_id' => $shop->id,
                'country_code' => '+255',
                'phone' => '713777011',
                'channel' => 'in_store',
            ])
            ->assertRedirect(route('till.ticket'));

        $this->actingAs($owner)
            ->get(route('till.ticket'))
            ->assertOk()
            ->assertSee('5% off anything', false)
            ->assertSee('2k off the bill', false)
            ->assertSee('Free pastry', false)
            ->assertSee('Mocha', false);
    }

    public function test_welcome_bonus_unlocks_offers_the_same_way_as_earn(): void
    {
        [$owner, $business, $shop] = $this->seedBusiness();
        \App\Models\Campaign::create([
            'business_id' => $business->id,
            'name' => 'Welcome',
            'type' => 'welcome',
            'bonus_points' => 150,
            'starts_at' => now()->subDay(),
            'is_active' => true,
        ]);
        $offer = \App\Models\Reward::create([
            'business_id' => $business->id,
            'name' => 'Welcome drink',
            'product_name' => 'Latte',
            'points_cost' => 150,
            'reward_type' => 'free_item',
            'reward_value' => 0,
            'is_active' => true,
        ]);
        $customer = User::factory()->customer()->create(['phone' => '713777012']);

        app(\App\Services\TillService::class)->recordSale($owner, $shop, $customer, 1000);
        $membership = $business->memberships()->where('customer_id', $customer->id)->first();
        $this->assertSame(150, (int) $membership->points_balance);
        $this->assertTrue($membership->availableRewards()->contains('id', $offer->id));
    }

    public function test_content_studio_keeps_canned_copy_and_reads_live_offers(): void
    {
        [$owner, $business] = $this->seedBusiness();
        \App\Models\Reward::create([
            'business_id' => $business->id,
            'name' => 'Free coffee',
            'product_name' => 'Coffee',
            'points_cost' => 100,
            'reward_type' => 'free_item',
            'reward_value' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('content-studio.index'))
            ->assertOk()
            ->assertSee(__('loop.studio_make_something'), false)
            ->assertSee('We’re on Loop — earn points every visit.', false)
            ->assertSee('Your phone is your loyalty card.', false)
            ->assertSee(__('loop.studio_look_plain'), false)
            ->assertSee(__('loop.studio_look_photo'), false)
            ->assertSee(__('loop.save_image'), false)
            ->assertSee('Free coffee is waiting on Loop', false);

        $this->actingAs($owner)
            ->get(route('content-studio.index', ['about' => 'offer']))
            ->assertOk()
            ->assertSee('Free coffee is waiting on Loop', false);
    }

    public function test_raffle_public_display_hides_phone_and_notifies_the_winner(): void
    {
        \App\Models\PlatformSetting::putValue(\App\Support\GrowthSettings::KEY, [
            ...\App\Support\GrowthSettings::defaults(),
            'raffle_min_members' => 2,
            'raffle_max_winners_percent' => 30,
        ]);
        [$owner, $business, $shop] = $this->seedBusiness();
        $business->update(['plan_key' => 'growth', 'billing_status' => 'active']);
        $owner->unsetRelation('ownedBusiness');

        $customers = [];
        foreach (['713777201', '713777202'] as $phone) {
            $customer = User::factory()->customer()->create([
                'phone' => $phone,
                'first_name' => 'Amina',
                'last_name' => 'Mwamba',
                'profile_completed' => true,
            ]);
            \App\Models\Membership::create([
                'business_id' => $business->id,
                'shop_id' => $shop->id,
                'customer_id' => $customer->id,
                'points_balance' => 10,
                'lifetime_points' => 10,
                'joined_at' => now(),
                'member_code' => 'LP-'.$phone,
            ]);
            $customers[] = $customer;
        }

        $this->actingAs($owner)
            ->post(route('raffles.store'), [
                'name' => 'Friday Loop Draw',
                'prize_name' => 'Free coffee',
                'prize_type' => 'free_item',
                'winners_count' => 1,
                'frequency' => 'once',
                'draw_at' => now()->addDay()->format('Y-m-d'),
                'claim_days' => 7,
            ])
            ->assertRedirect();

        $raffle = $business->raffles()->first();
        $this->assertNotNull($raffle);

        $this->actingAs($owner)
            ->get(route('raffles.index'))
            ->assertOk()
            ->assertSee('Friday Loop Draw', false)
            ->assertSee(__('loop.start_draw'), false)
            ->assertSee(__('loop.members_are_in', ['count' => 2]), false);

        $this->actingAs($owner)
            ->post(route('raffles.draw', $raffle))
            ->assertRedirect(route('raffles.live', $raffle));

        $winner = $raffle->fresh()->winners()->with('customer')->first();
        $this->assertNotNull($winner);

        $this->actingAs($owner)
            ->get(route('raffles.display', $raffle))
            ->assertOk()
            ->assertSee('Friday Loop Draw', false)
            ->assertDontSee($winner->customer->full_phone, false)
            ->assertDontSee('713777201', false)
            ->assertDontSee('713777202', false);

        $json = $this->actingAs($owner)
            ->getJson(route('raffles.board', $raffle))
            ->assertOk()
            ->json();

        $this->assertSame($winner->publicName(), $json['latest']['name'] ?? null);
        $this->assertArrayNotHasKey('phone', $json['latest'] ?? []);
        $this->assertStringNotContainsString('713777201', json_encode($json));
        $this->assertStringNotContainsString('713777202', json_encode($json));

        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $winner->customer_id,
            'type' => 'member_raffle_won',
        ]);
    }

    private function seedBusiness(): array
    {
        $owner = User::factory()->owner()->create(['phone' => '712888221']);
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Pass Shop',
            'slug' => 'pass-shop',
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

        return [$owner, $business, $shop];
    }
}
