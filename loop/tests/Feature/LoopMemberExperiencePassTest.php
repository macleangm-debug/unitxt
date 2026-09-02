<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\LegalDocument;
use App\Models\Reward;
use App\Models\Shop;
use App\Models\User;
use App\Support\Plans;
use App\Support\Sectors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoopMemberExperiencePassTest extends TestCase
{
    use RefreshDatabase;

    public function test_help_replaces_landing_faq_and_lives_under_account(): void
    {
        $this->get(route('landing.customer'))
            ->assertOk()
            ->assertDontSee(__('loop.member_faq_1_q'), false)
            ->assertSee(__('loop.help_faqs'), false);

        $this->get(route('help'))
            ->assertOk()
            ->assertSee(__('loop.help_how_q'), false)
            ->assertSee(__('loop.help_earn_q'), false);

        $customer = User::factory()->customer()->create(['phone' => '713880001', 'password' => '1234']);
        $this->actingAs($customer)
            ->get(route('more.index'))
            ->assertOk()
            ->assertSee(__('loop.help_faqs'), false)
            ->assertSee(route('help'), false)
            ->assertSee(__('loop.legal_documents'), false);
    }

    public function test_member_home_caps_activity_and_does_not_dump_the_directory(): void
    {
        [$owner, $business, $shop] = $this->seedBusiness();
        $customer = User::factory()->customer()->create([
            'phone' => '713880002',
            'password' => '1234',
            'city' => 'Dar es Salaam',
            'country' => 'TZ',
            'profile_completed' => true,
        ]);
        $staff = User::factory()->frontDesk()->create([
            'phone' => '712880002',
            'business_id' => $business->id,
            'password' => 'password',
        ]);
        $till = app(\App\Services\TillService::class);
        for ($i = 0; $i < 7; $i++) {
            $till->recordSale($staff, $shop, $customer, 2000);
        }

        $home = $this->actingAs($customer)->get(route('dashboard'))->assertOk();
        $home->assertSee(__('loop.see_all_activity'), false);
        $home->assertSee(__('loop.your_businesses'), false);
        $home->assertSee(__('loop.share_invite'), false);
        $home->assertDontSee(__('loop.member_faq_title'), false);
        $home->assertDontSee('hayMatch', false);

        $this->actingAs($customer)
            ->get(route('member.activity'))
            ->assertOk()
            ->assertSee($business->name, false);
    }

    public function test_discover_is_server_side_and_uses_sector_catalogue(): void
    {
        [$owner, $business] = $this->seedBusiness();
        $business->update(['sector' => 'restaurants', 'name' => 'Real Burger']);
        Shop::query()->where('business_id', $business->id)->update(['city' => 'Dar es Salaam']);

        $this->get(route('discover'))
            ->assertOk()
            ->assertSee($business->name)
            ->assertSee('loop-discover-row', false)
            ->assertDontSee('hayMatch', false)
            ->assertSee(__('loop.search_shops_placeholder'), false);

        foreach (Sectors::featured() as $row) {
            $this->get(route('discover'))
                ->assertSee($row['short'] ?: $row['label'], false);
            break;
        }

        $this->get(route('discover', ['q' => 'burger']))
            ->assertOk()
            ->assertSee($business->name)
            ->assertSee(__('loop.search_results', ['q' => 'burger']), false);
    }

    public function test_rewards_page_lists_ready_rewards_and_qr_is_member_identity(): void
    {
        [$owner, $business, $shop] = $this->seedBusiness();
        \App\Models\Campaign::query()->create([
            'business_id' => $business->id,
            'name' => 'Everyday earn',
            'type' => 'earn',
            'spend_step' => 1000,
            'points_per_step' => 2,
            'starts_at' => now()->subDay(),
            'is_active' => true,
        ]);
        Reward::query()->create([
            'business_id' => $business->id,
            'name' => 'Free coffee',
            'points_cost' => 10,
            'reward_type' => 'free_item',
            'is_active' => true,
        ]);
        $customer = User::factory()->customer()->create([
            'phone' => '713880003',
            'password' => '1234',
            'profile_completed' => true,
        ]);
        $staff = User::factory()->frontDesk()->create([
            'phone' => '712880003',
            'business_id' => $business->id,
            'password' => 'password',
        ]);
        app(\App\Services\TillService::class)->recordSale($staff, $shop, $customer, 20000);

        $this->actingAs($customer)
            ->get(route('memberships.index'))
            ->assertOk()
            ->assertSee(__('loop.your_rewards'), false)
            ->assertSee(__('loop.ready_to_use'), false)
            ->assertSee('Free coffee', false)
            ->assertSee(__('loop.wallet_qr_title'), false);

        $this->actingAs($customer)
            ->get(route('memberships.show', $business))
            ->assertOk()
            ->assertSee(__('loop.wallet_qr_title'), false)
            ->assertSee(__('loop.show_at_till'), false);

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('loop.nav_rewards'), false)
            ->assertSee(__('loop.activity'), false);
    }

    public function test_legal_centre_versions_and_signup_copy(): void
    {
        $this->get(route('legal.index'))
            ->assertOk()
            ->assertSee(__('loop.legal_privacy'), false)
            ->assertSee(__('loop.footer_terms'), false);

        $this->get(route('legal.show', 'terms'))
            ->assertOk()
            ->assertSee(__('loop.legal_draft_banner'), false)
            ->assertSee('1.0', false);

        $this->assertNotNull(LegalDocument::current('privacy'));

        $this->post(route('customer.send'), [
            'country_code' => '+255',
            'phone' => '716880099',
        ])->assertRedirect(route('customer.register'));

        $this->get(route('customer.register'))
            ->assertOk()
            ->assertSee(__('loop.marketing_opt_in'), false)
            ->assertSee(__('loop.footer_terms'), false)
            ->assertSee(__('loop.footer_privacy'), false);
    }

    /**
     * @return array{0: User, 1: Business, 2: Shop}
     */
    private function seedBusiness(): array
    {
        $owner = User::factory()->owner()->create(['phone' => '712880001']);
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Test Shop Co',
            'slug' => 'test-shop-pass',
            'sector' => 'coffee',
            'country' => 'TZ',
            'currency' => 'TZS',
            'city' => 'Dar es Salaam',
            'plan_key' => Plans::GROWTH,
            'billing_status' => 'active',
            'onboarding_completed_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);
        $shop = Shop::create([
            'business_id' => $business->id,
            'name' => 'Main',
            'city' => 'Dar es Salaam',
            'is_active' => true,
        ]);
        \App\Models\Campaign::query()->create([
            'business_id' => $business->id,
            'name' => 'Everyday earn',
            'type' => 'earn',
            'spend_step' => 1000,
            'points_per_step' => 2,
            'starts_at' => now()->subDay(),
            'is_active' => true,
        ]);

        return [$owner, $business, $shop];
    }
}
