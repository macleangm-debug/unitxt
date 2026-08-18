<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\InAppNotification;
use App\Models\Membership;
use App\Models\PointTransaction;
use App\Models\Redemption;
use App\Models\Reward;
use App\Models\Shop;
use App\Models\User;
use App\Models\Visit;
use App\Services\DailyNotificationService;
use App\Services\MessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoopMemberExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_member_register_is_a_three_step_wizard(): void
    {
        $this->post(route('customer.send'), [
            'country_code' => '+255',
            'phone' => '716111222',
        ])->assertRedirect(route('customer.register'));

        $this->get(route('customer.register'))
            ->assertOk()
            ->assertSee('memberRegisterWizard', false)
            ->assertSee(__('loop.member_intro'), false)
            ->assertSee(__('loop.interests'), false)
            ->assertSee(__('loop.create_pin'), false)
            ->assertSee(__('loop.member_intro_blurb'), false)
            ->assertSee(__('loop.member_pin_blurb'), false);
    }

    public function test_member_home_shows_business_cards_and_offer_badge(): void
    {
        [$owner, $business, $shop] = $this->seedBusiness();
        Reward::create([
            'business_id' => $business->id,
            'name' => 'Free pour',
            'points_cost' => 100,
            'reward_type' => 'percent_off',
            'reward_value' => 5,
            'is_active' => true,
        ]);
        $customer = User::factory()->customer()->create([
            'phone' => '713111001',
            'country' => 'TZ',
            'city' => 'Dar es Salaam',
            'interests' => ['automotive'],
            'profile_completed' => true,
            'password' => '1234',
        ]);
        Membership::create([
            'business_id' => $business->id,
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'points_balance' => 172,
            'lifetime_points' => 172,
        ]);

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Harbor Beans', false)
            ->assertSee('Coffee', false)
            ->assertSee('right-3 top-3', false)
            ->assertSee(trans_choice('loop.offer_ready_badge', 1, ['count' => 1]), false)
            ->assertDontSee(__('loop.explore_nearby'), false)
            ->assertSee('172 pts', false)
            ->assertDontSee('absolute left-3 bottom-3', false);
    }

    public function test_redeem_places_empty_state_invites_explore_and_a_notification(): void
    {
        $customer = User::factory()->customer()->create([
            'phone' => '713111002',
            'country' => 'TZ',
            'profile_completed' => true,
            'password' => '1234',
        ]);

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('loop.redeem_places_empty_title'), false)
            ->assertSee(__('loop.redeem_places_empty'), false)
            ->assertSee(__('loop.explore'), false);
    }

    public function test_points_activity_groups_a_visit_into_one_line(): void
    {
        [$owner, $business, $shop] = $this->seedBusiness();
        $customer = User::factory()->customer()->create([
            'phone' => '713111003',
            'country' => 'TZ',
            'profile_completed' => true,
            'password' => '1234',
        ]);
        $membership = Membership::create([
            'business_id' => $business->id,
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'points_balance' => 210,
            'lifetime_points' => 210,
        ]);
        $visit = Visit::create([
            'business_id' => $business->id,
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'membership_id' => $membership->id,
            'recorded_by' => $owner->id,
            'amount_spent' => 20000,
            'points_earned' => 210,
            'channel' => 'in_store',
        ]);
        PointTransaction::create([
            'membership_id' => $membership->id,
            'visit_id' => $visit->id,
            'recorded_by' => $owner->id,
            'type' => PointTransaction::TYPE_EARN,
            'points' => 200,
            'balance_after' => 200,
            'description' => 'Purchase +200 pts',
        ]);
        PointTransaction::create([
            'membership_id' => $membership->id,
            'visit_id' => $visit->id,
            'recorded_by' => $owner->id,
            'type' => PointTransaction::TYPE_EARN,
            'points' => 10,
            'balance_after' => 210,
            'description' => 'Featured (Codee Macha) +10 pts',
        ]);

        $this->actingAs($customer)
            ->get(route('memberships.show', $business))
            ->assertOk()
            ->assertSee(__('loop.points_visit_earned'), false)
            ->assertSee('+210', false)
            ->assertDontSee('Purchase +200 pts', false)
            ->assertDontSee('Featured (Codee Macha)', false);
    }

    public function test_admin_writes_one_story_in_two_languages_and_members_see_their_country(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '710111000']);
        $this->actingAs($admin)
            ->post(route('admin.articles.store'), [
                'title_en' => 'New shops in Dar',
                'title_sw' => 'Maduka mapya Dar',
                'excerpt_en' => 'Two cafés joined Loop.',
                'excerpt_sw' => 'Mikahawa miwili imejiunga.',
                'body_en' => 'Harbor Beans and Coast Kitchen are live.',
                'body_sw' => 'Harbor Beans na Coast Kitchen ziko hai.',
                'country' => 'TZ',
                'audience' => 'members',
                'published' => '1',
            ])
            ->assertRedirect(route('admin.articles.index'));

        $this->actingAs($admin)
            ->post(route('admin.articles.store'), [
                'title_en' => 'How points work',
                'title_sw' => 'Pointi zinavyofanya kazi',
                'body_en' => 'One wallet, many shops.',
                'body_sw' => 'Mkoba mmoja, maduka mengi.',
                'country' => '',
                'audience' => 'members',
                'published' => '1',
            ])
            ->assertRedirect(route('admin.articles.index'));

        $this->assertSame(1, Article::query()->where('country', 'TZ')->count());
        $this->assertSame(1, Article::query()->whereNull('country')->count());

        $tzStory = Article::query()->where('country', 'TZ')->first();
        $general = Article::query()->whereNull('country')->first();
        $tz = User::factory()->customer()->create([
            'phone' => '713111004',
            'country' => 'TZ',
            'profile_completed' => true,
            'password' => '1234',
        ]);
        $ke = User::factory()->customer()->create([
            'phone' => '713111005',
            'country' => 'KE',
            'profile_completed' => true,
            'password' => '1234',
        ]);

        $this->actingAs($tz)
            ->withSession(['locale' => 'en', 'preferred_country' => 'TZ'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($tzStory->title_en, false)
            ->assertSee($general->title_en, false);

        $this->actingAs($tz)
            ->withSession(['locale' => 'en', 'preferred_country' => 'TZ'])
            ->get(route('stories.show', $tzStory))
            ->assertOk()
            ->assertSee($tzStory->body_en, false)
            ->assertSee($general->title_en, false);

        $this->actingAs($ke)
            ->withSession(['locale' => 'en', 'preferred_country' => 'KE'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($general->title_en, false)
            ->assertDontSee($tzStory->title_en, false);

        $this->actingAs($ke)
            ->withSession(['locale' => 'en', 'preferred_country' => 'KE'])
            ->get(route('stories.show', $tzStory))
            ->assertNotFound();
    }

    public function test_sms_audience_can_target_one_person_points_and_redeemed(): void
    {
        [$owner, $business, $shop] = $this->seedBusiness();
        $reward = Reward::create([
            'business_id' => $business->id,
            'name' => 'Tea',
            'points_cost' => 50,
            'reward_type' => 'percent_off',
            'reward_value' => 5,
            'is_active' => true,
        ]);

        $low = User::factory()->customer()->create(['phone' => '713200001', 'gender' => 'male']);
        $rich = User::factory()->customer()->create(['phone' => '713200002', 'gender' => 'female']);
        $redeemed = User::factory()->customer()->create(['phone' => '713200003', 'gender' => 'male']);

        $lowM = Membership::create([
            'business_id' => $business->id,
            'shop_id' => $shop->id,
            'customer_id' => $low->id,
            'points_balance' => 40,
        ]);
        $richM = Membership::create([
            'business_id' => $business->id,
            'shop_id' => $shop->id,
            'customer_id' => $rich->id,
            'points_balance' => 150,
        ]);
        $redeemedM = Membership::create([
            'business_id' => $business->id,
            'shop_id' => $shop->id,
            'customer_id' => $redeemed->id,
            'points_balance' => 20,
        ]);
        Redemption::create([
            'reward_id' => $reward->id,
            'membership_id' => $redeemedM->id,
            'customer_id' => $redeemed->id,
            'points_spent' => 50,
            'status' => 'applied',
        ]);

        $messaging = app(MessagingService::class);

        $one = $messaging->recipients($business, ['audience' => 'person', 'customer_id' => $low->id]);
        $this->assertEqualsCanonicalizing([$low->id], $one->pluck('id')->all());

        $byPoints = $messaging->recipients($business, ['audience' => 'points', 'min_points' => 100]);
        $this->assertEqualsCanonicalizing([$rich->id], $byPoints->pluck('id')->all());

        $didRedeem = $messaging->recipients($business, ['audience' => 'redeemed']);
        $this->assertEqualsCanonicalizing([$redeemed->id], $didRedeem->pluck('id')->all());

        $women = $messaging->recipients($business, ['audience' => 'gender', 'genders' => ['female']]);
        $this->assertEqualsCanonicalizing([$rich->id], $women->pluck('id')->all());

        unset($owner, $lowM, $richM, $shop);
    }

    public function test_member_is_notified_when_an_offer_is_ready_to_redeem(): void
    {
        [$owner, $business, $shop] = $this->seedBusiness();
        Reward::create([
            'business_id' => $business->id,
            'name' => 'Free pour',
            'points_cost' => 100,
            'reward_type' => 'percent_off',
            'reward_value' => 5,
            'is_active' => true,
        ]);
        $customer = User::factory()->customer()->create([
            'phone' => '713111006',
            'country' => 'TZ',
            'profile_completed' => true,
            'password' => '1234',
        ]);
        Membership::create([
            'business_id' => $business->id,
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'points_balance' => 120,
            'lifetime_points' => 120,
        ]);

        app(DailyNotificationService::class)->generateForCustomer($customer);

        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $customer->id,
            'type' => 'member_redeem_ready',
            'title_key' => 'loop.notif_member_redeem_title',
        ]);
        $this->assertSame(0, InAppNotification::query()->where('user_id', $customer->id)->where('type', 'member_offers')->count());
    }

    public function test_sale_notifies_when_same_day_redeem_is_on_and_stays_quiet_when_off(): void
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
        Reward::create([
            'business_id' => $business->id,
            'name' => 'Free pour',
            'points_cost' => 100,
            'reward_type' => 'percent_off',
            'reward_value' => 5,
            'is_active' => true,
        ]);
        $customer = User::factory()->customer()->create([
            'phone' => '713111007',
            'country' => 'TZ',
            'profile_completed' => true,
            'password' => '1234',
        ]);

        app(\App\Services\TillService::class)->recordSale($owner, $shop, $customer, 50000);
        $this->assertSame(0, InAppNotification::query()->where('user_id', $customer->id)->where('type', 'member_redeem_ready')->count());

        $business->update(['allow_same_day_earn_redeem' => true]);
        $later = User::factory()->customer()->create([
            'phone' => '713111008',
            'country' => 'TZ',
            'profile_completed' => true,
            'password' => '1234',
        ]);
        app(\App\Services\TillService::class)->recordSale($owner, $shop, $later, 50000);

        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $later->id,
            'type' => 'member_redeem_ready',
        ]);
    }

    /**
     * @return array{0: User, 1: Business, 2: Shop}
     */
    private function seedBusiness(): array
    {
        $owner = User::factory()->owner()->create(['phone' => '712111221']);
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Harbor Beans',
            'slug' => 'harbor-beans-test',
            'sector' => 'coffee',
            'country' => 'TZ',
            'currency' => 'TZS',
            'city' => 'Dar es Salaam',
            'onboarding_completed_at' => now(),
            'is_active' => true,
        ]);
        $owner->update(['business_id' => $business->id]);
        $shop = Shop::create([
            'business_id' => $business->id,
            'name' => 'Downtown',
            'city' => 'Dar es Salaam',
            'is_active' => true,
        ]);

        return [$owner, $business, $shop];
    }
}
