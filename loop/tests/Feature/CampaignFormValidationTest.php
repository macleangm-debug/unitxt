<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Reward;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignFormValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_pages_block_browser_notification_prompts(): void
    {
        $this->get(route('staff.login'))
            ->assertOk()
            ->assertHeader('Permissions-Policy', 'notifications=(), push=()')
            ->assertSee('autocomplete="off"', false);

        $html = $this->get(route('staff.login'))->getContent();
        $this->assertStringNotContainsString('value="password"', $html);
    }

    public function test_campaign_cannot_be_created_without_spend_and_points(): void
    {
        [$owner] = $this->seedOwnerWithOffer();

        $this->actingAs($owner)
            ->from(route('campaigns.create', ['own' => 1]))
            ->post(route('campaigns.store'), $this->validPayload([
                'spend_step' => '',
                'points_per_step' => '',
            ]))
            ->assertRedirect(route('campaigns.create', ['own' => 1]))
            ->assertSessionHasErrors(['spend_step', 'points_per_step']);

        $this->assertDatabaseCount('campaigns', 0);
    }

    public function test_campaign_cannot_be_created_with_zero_spend_or_points(): void
    {
        [$owner] = $this->seedOwnerWithOffer();

        $this->actingAs($owner)
            ->post(route('campaigns.store'), $this->validPayload([
                'spend_step' => 0,
                'points_per_step' => 0,
            ]))
            ->assertSessionHasErrors(['spend_step', 'points_per_step']);

        $this->assertDatabaseCount('campaigns', 0);
    }

    public function test_campaign_cannot_be_created_without_name_or_start_date(): void
    {
        [$owner] = $this->seedOwnerWithOffer();

        $this->actingAs($owner)
            ->post(route('campaigns.store'), $this->validPayload([
                'name' => '',
                'starts_at' => '',
            ]))
            ->assertSessionHasErrors(['name', 'starts_at']);

        $this->assertDatabaseCount('campaigns', 0);
    }

    public function test_complete_campaign_form_creates_campaign(): void
    {
        [$owner, $business] = $this->seedOwnerWithOffer();

        $this->actingAs($owner)
            ->post(route('campaigns.store'), $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('campaigns', [
            'business_id' => $business->id,
            'name' => 'Everyday earn',
            'type' => 'earn',
            'spend_step' => 1500,
            'points_per_step' => 3,
        ]);
    }

    public function test_bonus_campaign_requires_bonus_points(): void
    {
        [$owner] = $this->seedOwnerWithOffer();

        $this->actingAs($owner)
            ->post(route('campaigns.store'), $this->validPayload([
                'type' => 'birthday',
                'spend_step' => '',
                'points_per_step' => '',
                'bonus_points' => '',
            ]))
            ->assertSessionHasErrors('bonus_points');

        $this->assertDatabaseCount('campaigns', 0);
    }

    public function test_birthday_campaign_can_be_created_without_spend(): void
    {
        [$owner, $business] = $this->seedOwnerWithOffer();

        $this->actingAs($owner)
            ->post(route('campaigns.store'), $this->validPayload([
                'name' => 'Birthday treat',
                'type' => 'birthday',
                'spend_step' => '',
                'points_per_step' => '',
                'bonus_points' => 50,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('campaigns', [
            'business_id' => $business->id,
            'type' => 'birthday',
            'bonus_points' => 50,
            'spend_step' => null,
        ]);
    }

    public function test_product_push_can_be_created_without_spend_or_earn_points(): void
    {
        [$owner, $business] = $this->seedOwnerWithOffer();

        $this->actingAs($owner)
            ->post(route('campaigns.store'), $this->validPayload([
                'name' => 'ALPHA',
                'type' => 'product_push',
                'spend_step' => '',
                'points_per_step' => '',
                'featured_product_name' => 'ALPHA',
                'bonus_points' => 10,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('campaigns', [
            'business_id' => $business->id,
            'name' => 'ALPHA',
            'type' => 'product_push',
            'featured_product_name' => 'ALPHA',
            'bonus_points' => 10,
            'spend_step' => null,
            'points_per_step' => null,
        ]);
    }

    public function test_second_earn_campaign_is_rejected(): void
    {
        [$owner, $business] = $this->seedOwnerWithOffer();
        Campaign::create([
            'business_id' => $business->id,
            'name' => 'Main',
            'type' => 'earn',
            'spend_step' => 1000,
            'points_per_step' => 2,
            'starts_at' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->from(route('campaigns.create'))
            ->post(route('campaigns.store'), $this->validPayload([
                'name' => 'Another earn',
            ]))
            ->assertRedirect(route('campaigns.create'))
            ->assertSessionHasErrors('type');

        $this->assertSame(1, $business->campaigns()->where('type', 'earn')->count());
    }

    public function test_second_birthday_campaign_is_rejected(): void
    {
        [$owner, $business] = $this->seedOwnerWithOffer();
        Campaign::create([
            'business_id' => $business->id,
            'name' => 'Birthday',
            'type' => 'birthday',
            'bonus_points' => 50,
            'starts_at' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->from(route('campaigns.create'))
            ->post(route('campaigns.store'), $this->validPayload([
                'name' => 'Another birthday',
                'type' => 'birthday',
                'spend_step' => '',
                'points_per_step' => '',
                'bonus_points' => 40,
            ]))
            ->assertRedirect(route('campaigns.create'))
            ->assertSessionHasErrors('type');

        $this->assertSame(1, $business->campaigns()->where('type', 'birthday')->count());
    }

    public function test_multiple_percent_off_offers_are_allowed(): void
    {
        [$owner, $business] = $this->seedOwnerWithOffer();

        $this->actingAs($owner)
            ->post(route('rewards.store'), $this->offerPayload())
            ->assertRedirect();

        $this->assertSame(2, $business->rewards()->where('reward_type', 'percent_off')->count());
        $this->assertDatabaseHas('rewards', [
            'business_id' => $business->id,
            'name' => '10% off',
            'points_cost' => 180,
            'reward_value' => 10,
        ]);
    }

    public function test_trial_offer_cap_blocks_a_fourth_offer(): void
    {
        [$owner, $business] = $this->seedOwnerWithOffer();
        foreach ([2, 3] as $i) {
            Reward::create([
                'business_id' => $business->id,
                'name' => $i.'0% off',
                'points_cost' => 100 + ($i * 40),
                'reward_type' => 'percent_off',
                'reward_value' => $i * 5,
                'is_active' => true,
            ]);
        }

        $this->actingAs($owner)
            ->from(route('rewards.create'))
            ->post(route('rewards.store'), $this->offerPayload([
                'name' => '15% off',
                'points_cost' => 250,
                'reward_value' => 15,
            ]))
            ->assertRedirect(route('campaigns.index', ['tab' => 'offers']))
            ->assertSessionHasErrors('plan');

        $this->assertSame(3, $business->rewards()->count());
    }

    public function test_trial_product_push_cap_blocks_a_second_push(): void
    {
        [$owner, $business] = $this->seedOwnerWithOffer();
        Campaign::create([
            'business_id' => $business->id,
            'name' => 'ALPHA',
            'type' => 'product_push',
            'featured_product_name' => 'ALPHA',
            'bonus_points' => 10,
            'starts_at' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->from(route('campaigns.create'))
            ->post(route('campaigns.store'), $this->validPayload([
                'name' => 'BETA',
                'type' => 'product_push',
                'spend_step' => '',
                'points_per_step' => '',
                'featured_product_name' => 'BETA',
                'bonus_points' => 12,
            ]))
            ->assertRedirect(route('campaigns.create'))
            ->assertSessionHasErrors('plan');

        $this->assertSame(1, $business->campaigns()->where('type', 'product_push')->count());
    }

    public function test_product_push_requires_featured_product_and_bonus_points(): void
    {
        [$owner] = $this->seedOwnerWithOffer();

        $this->actingAs($owner)
            ->post(route('campaigns.store'), $this->validPayload([
                'type' => 'product_push',
                'spend_step' => '',
                'points_per_step' => '',
                'featured_product_name' => '',
                'bonus_points' => '',
            ]))
            ->assertSessionHasErrors(['featured_product_name', 'bonus_points']);

        $this->assertDatabaseCount('campaigns', 0);
    }

    public function test_earn_campaign_update_requires_spend_and_points(): void
    {
        [$owner, $business] = $this->seedOwnerWithOffer();
        $campaign = Campaign::create([
            'business_id' => $business->id,
            'name' => 'Earn',
            'type' => 'earn',
            'spend_step' => 1000,
            'points_per_step' => 2,
            'starts_at' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->put(route('campaigns.update', $campaign), [
                'name' => 'Earn',
                'type' => 'earn',
                'spend_step' => '',
                'points_per_step' => '',
                'starts_at' => now()->toDateString(),
                'is_active' => 1,
            ])
            ->assertSessionHasErrors(['spend_step', 'points_per_step']);

        $this->assertSame(1000, $campaign->fresh()->spend_step);
        $this->assertSame(2, $campaign->fresh()->points_per_step);
    }

    public function test_onboarding_campaign_requires_spend_and_points(): void
    {
        $owner = User::factory()->owner()->create(['phone' => '712888101']);
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Onboard Cafe',
            'slug' => 'onboard-cafe',
            'sector' => 'coffee',
            'country' => 'TZ',
            'currency' => 'TZS',
            'city' => 'Dar es Salaam',
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
            ->post(route('onboarding.campaign'), [
                'template' => 'everyday_earn',
                'name' => 'Onboard Cafe Points',
            ])
            ->assertSessionHasErrors(['spend_step', 'points_per_step']);

        $this->assertDatabaseCount('campaigns', 0);
    }

    /**
     * @return array{0: User, 1: Business, 2: Shop}
     */
    private function seedOwnerWithOffer(): array
    {
        $owner = User::factory()->owner()->create(['phone' => '712888201']);
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Form Shop',
            'slug' => 'form-shop',
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
        Reward::create([
            'business_id' => $business->id,
            'name' => '5% off',
            'points_cost' => 100,
            'reward_type' => 'percent_off',
            'reward_value' => 5,
            'is_active' => true,
        ]);

        return [$owner, $business, $shop];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Everyday earn',
            'type' => 'earn',
            'spend_step' => 1500,
            'points_per_step' => 3,
            'starts_at' => now()->toDateString(),
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function offerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => '10% off',
            'points_cost' => 180,
            'reward_type' => 'percent_off',
            'reward_value' => 10,
        ], $overrides);
    }
}
