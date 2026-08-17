<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Reward;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignOfferUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_campaign_shows_template_pick_inside_wizard(): void
    {
        [$owner] = $this->seedOwnerWithOffer();

        $this->actingAs($owner)
            ->get(route('campaigns.create'))
            ->assertOk()
            ->assertSee(__('loop.pick_campaign_template'), false)
            ->assertSee(__('loop.main_campaign'), false)
            ->assertSee(__('loop.bonus_campaigns'), false)
            ->assertSee(__('loop.templates.everyday_earn.name'), false)
            ->assertSee(__('loop.templates.product_push.name'), false)
            ->assertSee(__('loop.templates.birthday_treat.name'), false)
            ->assertSee(__('loop.templates.visit_streak.name'), false)
            ->assertSee(__('loop.templates.welcome_bonus.name'), false)
            ->assertDontSee(__('loop.create_own'), false)
            ->assertDontSee(__('loop.templates.faster_earn.name'), false)
            ->assertSee('campaignWizard', false)
            ->assertSee('data-step="1"', false)
            ->assertSee('data-step="4"', false)
            ->assertDontSee('data-step="5"', false)
            ->assertDontSee('<option value="earn">', false);
    }

    public function test_campaign_template_does_not_fill_name_or_description(): void
    {
        [$owner] = $this->seedOwnerWithOffer();
        $html = $this->actingAs($owner)
            ->get(route('campaigns.create', ['template' => 'everyday_earn']))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/name="name"[^>]*value=""/', $html);
        $this->assertTrue(
            str_contains($html, 'placeholder="'.trans('loop.templates.everyday_earn.name', [], 'en').'"')
            || str_contains($html, 'placeholder="'.trans('loop.templates.everyday_earn.name', [], 'sw').'"')
        );
        $this->assertStringNotContainsString('>'.trans('loop.templates.everyday_earn.name', [], 'en').'</textarea>', $html);
        $this->assertStringNotContainsString('>'.trans('loop.templates.everyday_earn.name', [], 'sw').'</textarea>', $html);
    }

    public function test_new_campaign_hides_main_earn_when_one_already_exists(): void
    {
        [$owner, $business] = $this->seedOwnerWithOffer();
        \App\Models\Campaign::create([
            'business_id' => $business->id,
            'name' => 'Main earn',
            'type' => 'earn',
            'spend_step' => 1000,
            'points_per_step' => 2,
            'starts_at' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('campaigns.create'))
            ->assertOk()
            ->assertDontSee(__('loop.templates.everyday_earn.name'), false)
            ->assertSee(__('loop.templates.product_push.name'), false)
            ->assertSee(__('loop.templates.birthday_treat.name'), false);
    }

    public function test_swahili_offer_type_titles(): void
    {
        $this->assertSame('Punguzo la Asilimia', trans('loop.offer_type_percent_off_title', [], 'sw'));
        $this->assertSame('Punguzo la Kiasi', trans('loop.offer_type_fixed_off_title', [], 'sw'));
        $this->assertSame('Bidhaa ya Bure', trans('loop.offer_type_free_item_title', [], 'sw'));
    }

    public function test_new_offer_shows_type_cards_inside_wizard(): void
    {
        [$owner] = $this->seedOwnerWithOffer();

        $this->actingAs($owner)
            ->get(route('rewards.create'))
            ->assertOk()
            ->assertSee(__('loop.choose_offer_type'), false)
            ->assertSee('offerWizard', false)
            ->assertSee(__('loop.offer_type_percent_off_title'), false)
            ->assertDontSee('href="'.route('rewards.create', ['type' => 'percent_off']).'"', false);
    }

    public function test_offer_list_cards_match_campaign_card_layout(): void
    {
        [$owner] = $this->seedOwnerWithOffer();

        $this->actingAs($owner)
            ->get(route('campaigns.index'))
            ->assertOk()
            ->assertSee(__('loop.new_campaign'), false)
            ->assertSee(__('loop.how_they_earn'), false)
            ->assertSee(__('loop.what_they_choose'), false)
            ->assertSee(__('loop.live_campaigns'), false);

        $this->actingAs($owner)
            ->get(route('campaigns.index', ['tab' => 'offers']))
            ->assertOk()
            ->assertSee(__('loop.add_offer'), false)
            ->assertSee(__('loop.live_offers'), false)
            ->assertSee('5% off anything', false)
            ->assertSee('text-white', false)
            ->assertDontSee(__('loop.view_stats'), false);
    }

    public function test_view_offer_hides_recent_redemptions_when_empty(): void
    {
        [$owner, $business] = $this->seedOwnerWithOffer();
        $reward = $business->rewards()->first();

        $html = $this->actingAs($owner)
            ->get(route('rewards.show', $reward))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('text-white', $html);
        $this->assertStringNotContainsString(trans('loop.recent_redemptions', [], 'en'), $html);
        $this->assertStringNotContainsString(trans('loop.recent_redemptions', [], 'sw'), $html);
        $this->assertStringNotContainsString(trans('loop.no_redemptions_yet', [], 'en'), $html);
        $this->assertStringNotContainsString(trans('loop.no_redemptions_yet', [], 'sw'), $html);
        $this->assertTrue(
            str_contains($html, trans('loop.pause_offer_confirm_title', [], 'en'))
            || str_contains($html, trans('loop.pause_offer_confirm_title', [], 'sw'))
        );
    }

    public function test_paused_offer_uses_red_status_pill(): void
    {
        [$owner, $business] = $this->seedOwnerWithOffer();
        $reward = $business->rewards()->first();
        $reward->update(['is_active' => false]);

        $html = $this->actingAs($owner)
            ->get(route('rewards.show', $reward))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('bg-coral', $html);
        $this->assertStringContainsString('text-white', $html);
        $this->assertTrue(
            str_contains($html, trans('loop.paused', [], 'en'))
            || str_contains($html, trans('loop.paused', [], 'sw'))
        );
    }

    public function test_view_offer_matches_campaign_layout(): void
    {
        [$owner, $business] = $this->seedOwnerWithOffer();
        $reward = $business->rewards()->first();

        $this->actingAs($owner)
            ->get(route('rewards.show', $reward))
            ->assertOk()
            ->assertSee(__('loop.member_redeems'), false)
            ->assertSee(__('loop.pause_campaign'), false)
            ->assertDontSee(__('loop.whats_working'), false)
            ->assertDontSee(__('loop.offer_details'), false);
    }

    public function test_offer_can_be_paused(): void
    {
        [$owner, $business] = $this->seedOwnerWithOffer();
        $reward = $business->rewards()->first();

        $this->actingAs($owner)
            ->post(route('rewards.toggle', $reward))
            ->assertRedirect(route('rewards.show', $reward));

        $this->assertFalse($reward->fresh()->is_active);
    }

    /**
     * @return array{0: User, 1: Business, 2: Shop}
     */
    private function seedOwnerWithOffer(): array
    {
        $owner = User::factory()->owner()->create(['phone' => '712888301']);
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Ui Shop',
            'slug' => 'ui-shop',
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
            'name' => '5% off anything',
            'points_cost' => 100,
            'reward_type' => 'percent_off',
            'reward_value' => 5,
            'is_active' => true,
        ]);

        return [$owner, $business, $shop];
    }
}
