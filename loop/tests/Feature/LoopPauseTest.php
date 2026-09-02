<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\InAppNotification;
use App\Models\Membership;
use App\Models\Reward;
use App\Models\Shop;
use App\Models\User;
use App\Services\LoopAccess;
use App\Support\BillingSettings;
use App\Support\NotificationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoopPauseTest extends TestCase
{
    use RefreshDatabase;

    public function test_grace_keeps_till_open_and_pause_hides_discover_without_deleting_members(): void
    {
        \App\Models\PlatformSetting::putValue(BillingSettings::KEY, BillingSettings::defaults());

        [$owner, $business, $shop] = $this->seedBusiness();
        $customer = User::factory()->customer()->create([
            'phone' => '713777001',
            'password' => '1234',
            'profile_completed' => true,
        ]);
        $membership = Membership::create([
            'business_id' => $business->id,
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'points_balance' => 74,
            'lifetime_points' => 74,
        ]);
        Reward::create([
            'business_id' => $business->id,
            'name' => 'Free coffee',
            'points_cost' => 80,
            'reward_type' => 'free_item',
            'reward_value' => 0,
            'is_active' => true,
        ]);

        $business->update([
            'plan_key' => 'free',
            'billing_status' => 'trialing',
            'trial_ends_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $access = app(LoopAccess::class);
        $this->assertSame(LoopAccess::PHASE_GRACE, $access->phase($business->fresh()));
        $this->actingAs($owner)->get(route('discover'))->assertOk()->assertSee($business->name, false);

        $business->update(['trial_ends_at' => now()->subDays(8)]);
        $paused = $access->sync($business->fresh());
        $this->assertSame(LoopAccess::PHASE_PAUSED, $access->phase($paused));
        $this->assertSame('paused', $paused->billing_status);
        $this->assertNotNull($paused->paused_at);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('loop.loop_momentum_paused'), false)
            ->assertSee(__('loop.reactivate_loop'), false);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('billing.show'))
            ->assertOk()
            ->assertSee(__('loop.upgrade_title_paused'), false)
            ->assertSee(__('loop.loop_paused_safe'), false);

        $this->actingAs($customer)
            ->get(route('discover'))
            ->assertOk()
            ->assertDontSee($business->name, false);

        $this->actingAs($customer)
            ->get(route('discover.show', $business))
            ->assertOk()
            ->assertSee(__('loop.discover_paused_blurb'), false)
            ->assertSee(__('loop.want_loop_back'), false);

        $this->actingAs($customer)
            ->get(route('memberships.show', $business))
            ->assertOk()
            ->assertSee(__('loop.member_paused_title'), false)
            ->assertSee('74', false);

        $this->actingAs($customer)
            ->post(route('memberships.want-back', $business))
            ->assertRedirect();

        $this->assertDatabaseHas('loop_back_requests', [
            'business_id' => $business->id,
            'customer_id' => $customer->id,
        ]);
        $this->assertDatabaseHas('memberships', [
            'id' => $membership->id,
            'points_balance' => 74,
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(trans_choice('loop.loop_paused_demand_title', 1, ['count' => 1]), false);
    }

    public function test_billing_reminders_fire_seven_days_out_and_when_paused(): void
    {
        \App\Models\PlatformSetting::putValue(BillingSettings::KEY, BillingSettings::defaults());
        \App\Models\PlatformSetting::putValue(NotificationSettings::KEY, NotificationSettings::defaults());

        [$owner, $business] = array_slice($this->seedBusiness(), 0, 2);
        $business->update([
            'plan_key' => 'growth',
            'billing_status' => 'active',
            'plan_renews_at' => now()->addDays(7),
        ]);

        $created = app(\App\Services\DailyNotificationService::class)->generateForBusiness($business->fresh());
        $this->assertGreaterThan(0, $created);
        $this->assertTrue(InAppNotification::query()->where('user_id', $owner->id)->where('type', 'billing_renew_7')->exists());

        $business->update(['plan_renews_at' => now()->subDays(8)]);
        app(\App\Services\DailyNotificationService::class)->generateForBusiness($business->fresh());
        $this->assertTrue(InAppNotification::query()->where('user_id', $owner->id)->where('type', 'billing_paused')->exists());
    }

    public function test_admin_grace_days_come_from_the_hub(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '710888001']);

        $this->actingAs($admin)
            ->put(route('admin.settings.billing'), [
                'trial_days' => 14,
                'grace_days' => 3,
                'free_max_shops' => 1,
                'free_max_members' => 50,
                'free_max_monthly_visits' => 50,
                'free_max_product_pushes' => 1,
                'free_max_offers' => 3,
                'block_till_when_trial_ends' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(3, BillingSettings::graceDays());
    }

    /**
     * @return array{0: User, 1: Business, 2: Shop}
     */
    private function seedBusiness(): array
    {
        $owner = User::factory()->owner()->create(['phone' => '712777001']);
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Pause Cafe',
            'slug' => 'pause-cafe',
            'sector' => 'coffee',
            'country' => 'TZ',
            'currency' => 'TZS',
            'city' => 'Dar es Salaam',
            'hotline' => '255710000111',
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
