<?php

namespace Tests\Feature;

use App\Models\Business;
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
            ->assertSee(__('loop.create_own'), false)
            ->assertSee('campaignWizard', false)
            ->assertSee('data-step="1"', false)
            ->assertSee('data-step="5"', false);
    }

    public function test_campaign_template_does_not_fill_name_or_description(): void
    {
        [$owner] = $this->seedOwnerWithOffer();
        $templateName = __('loop.templates.faster_earn.name');

        $html = $this->actingAs($owner)
            ->get(route('campaigns.create', ['template' => 'faster_earn']))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/name="name"[^>]*value=""/', $html);
        $this->assertStringContainsString('placeholder="'.$templateName.'"', $html);
        $this->assertStringNotContainsString('>'.$templateName.'</textarea>', $html);
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
            ->assertDontSee(__('loop.view_stats'), false)
            ->assertSee(__('loop.add_offer'), false);
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
