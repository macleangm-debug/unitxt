<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\GamePlay;
use App\Models\Membership;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\User;
use App\Support\FeatureFlags;
use App\Support\GameSettings;
use App\Support\Plans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamesWinsTest extends TestCase
{
    use RefreshDatabase;

    public function test_growth_sees_games_locked_to_scale(): void
    {
        $this->seedFlags();
        [$owner, $business] = $this->seedBusiness('growth');

        $this->actingAs($owner)
            ->get(route('games.index'))
            ->assertOk()
            ->assertSee(__('loop.game_plan_locked_title'), false)
            ->assertSee(__('loop.games_wins'), false);

        $this->actingAs($owner)
            ->get(route('games.create'))
            ->assertForbidden();
    }

    public function test_scale_owner_can_launch_and_play_is_server_side_and_idempotent(): void
    {
        $this->seedFlags();
        [$owner, $business, $shop] = $this->seedBusiness('scale');
        $customer = User::factory()->customer()->create([
            'phone' => '713555901',
            'first_name' => 'Asha',
        ]);
        Membership::create([
            'business_id' => $business->id,
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'points_balance' => 0,
            'lifetime_points' => 0,
            'joined_at' => now(),
            'member_code' => 'LP-713555901',
        ]);

        $response = $this->actingAs($owner)
            ->post(route('games.store'), [
                'type' => 'spin',
                'qualify_mode' => 'spend',
                'spend_threshold' => 20000,
                'play_limit' => 'daily',
                'win_mode' => 'automatic',
                'expected_plays' => 100,
                'starts_at' => now()->format('Y-m-d'),
                'ends_at' => now()->addDays(3)->format('Y-m-d'),
                'claim_days' => 7,
                'prizes' => [
                    ['kind' => 'free_item', 'name' => 'Free juice', 'quantity' => 5],
                    ['kind' => 'points', 'name' => '+20 points', 'quantity' => 20, 'points_value' => 20],
                ],
            ]);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $game = \App\Models\Game::query()->where('business_id', $business->id)->first();
        $this->assertNotNull($game);
        $this->assertSame('spin', $game->type);

        $low = app(\App\Services\TillService::class)->recordSale($owner, $shop, $customer, 5000);
        $this->assertSame(0, GamePlay::query()->where('visit_id', $low->id)->count());

        $high = app(\App\Services\TillService::class)->recordSale($owner, $shop, $customer, 25000);
        $play = GamePlay::query()->where('visit_id', $high->id)->first();
        $this->assertNotNull($play);
        $this->assertSame('pending', $play->status);

        $this->actingAs($customer)
            ->post(route('games.reveal', $play))
            ->assertRedirect(route('games.play', $play));

        $play->refresh();
        $this->assertSame('played', $play->status);
        $this->assertContains($play->outcome, ['win', 'no_win']);
        $firstOutcome = $play->outcome;
        $firstPrize = $play->prize_id;

        $this->actingAs($customer)
            ->post(route('games.reveal', $play))
            ->assertRedirect(route('games.play', $play));

        $play->refresh();
        $this->assertSame($firstOutcome, $play->outcome);
        $this->assertSame($firstPrize, $play->prize_id);
        $this->assertSame(1, GamePlay::query()->where('game_id', $game->id)->count());
    }

    public function test_admin_can_save_games_engine_settings(): void
    {
        $this->seedFlags();
        $admin = User::factory()->admin()->create(['phone' => '710555010', 'password' => 'password']);

        $this->actingAs($admin)
            ->put(route('admin.settings.games'), [
                'enabled' => 1,
                'types' => ['spin', 'boxes', 'scratch'],
                'default_qualify' => 'spend',
                'spend_multiplier' => 1.6,
                'recommended_visit_threshold' => 3,
                'recommended_win_rate' => 20,
                'max_win_rate' => 50,
                'default_play_frequency' => 'daily',
                'max_duration_days' => 90,
                'claim_days' => 7,
                'expected_plays' => 500,
                'allowed_prize_kinds' => ['free_item', 'percent', 'points', 'custom'],
            ])
            ->assertRedirect();

        $this->assertSame(20, GameSettings::settings()['recommended_win_rate']);
    }

    public function test_create_game_prize_kind_uses_a_sheet_and_hides_other_fields(): void
    {
        $this->seedFlags();
        [$owner] = $this->seedBusiness('scale');

        $this->actingAs($owner)
            ->get(route('games.create'))
            ->assertOk()
            ->assertSee('prizes[0][kind]', false)
            ->assertSee('spendDisplay', false)
            ->assertSee(__('loop.game_prize_kind'), false);
    }

    private function seedFlags(): void
    {
        \App\Models\PlatformSetting::putValue(FeatureFlags::KEY, FeatureFlags::defaults());
        \App\Models\PlatformSetting::putValue(GameSettings::KEY, GameSettings::defaults());
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
                'has_games' => $plan['has_games'] ?? false,
            ]);
        }
    }

    private function seedBusiness(string $planKey): array
    {
        $owner = User::factory()->owner()->create(['phone' => $planKey === 'scale' ? '712555801' : '712555802']);
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Game Cafe',
            'slug' => 'game-cafe-'.$planKey,
            'sector' => 'coffee',
            'country' => 'TZ',
            'currency' => 'TZS',
            'city' => 'Dar es Salaam',
            'onboarding_completed_at' => now(),
            'plan_key' => $planKey,
            'billing_status' => 'active',
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
