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
            ->assertSee(__('loop.sales'))
            ->assertDontSee(__('loop.performance'), false)
            ->assertSee(__('loop.notifications'))
            ->assertSee('bg-coral', false);

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
            ->assertSee(__('loop.look_up'));

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

    public function test_same_day_earn_does_not_unlock_redeem_by_default(): void
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

        app(\App\Services\TillService::class)->recordSale($owner, $shop, $customer, 50000);
        $membership = $business->memberships()->where('customer_id', $customer->id)->first();
        $this->assertSame(100, (int) $membership->points_balance);
        $this->assertSame(0, $membership->redeemablePoints());
        $this->assertTrue($membership->availableRewards()->isEmpty());

        $this->actingAs($owner)
            ->post(route('till.lookup'), [
                'shop_id' => $shop->id,
                'country_code' => '+255',
                'phone' => '713777009',
                'channel' => 'in_store',
            ])
            ->assertRedirect(route('till.ticket'));

        $this->actingAs($owner)
            ->post(route('till.store'), [
                'shop_id' => $shop->id,
                'country_code' => '+255',
                'phone' => '713777009',
                'channel' => 'in_store',
                'amount_spent' => 0,
                'reward_id' => $offer->id,
            ])
            ->assertSessionHasErrors('reward_id');

        $business->update(['allow_same_day_earn_redeem' => true]);
        $this->assertSame(100, $membership->fresh()->redeemablePoints());

        $business->update(['allow_same_day_earn_redeem' => false]);
        $this->travel(1)->day();
        $this->assertSame(100, $membership->fresh()->redeemablePoints());
        $this->assertTrue($membership->fresh()->availableRewards()->contains('id', $offer->id));
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
