<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileNavTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_has_bottom_nav_and_more_parity(): void
    {
        [$owner] = $this->seedBusiness();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('loop-bottom-nav', false)
            ->assertSee(__('loop.nav_till'), false)
            ->assertSee(__('loop.nav_campaigns'), false)
            ->assertSee(__('loop.nav_more'), false);

        $this->actingAs($owner)
            ->get(route('more.index'))
            ->assertOk()
            ->assertSee(__('loop.staff'), false)
            ->assertSee(__('loop.billing'), false)
            ->assertSee(__('loop.shops'), false)
            ->assertSee(route('staff.index'), false);
    }

    public function test_member_has_rewards_activity_and_account_tabs(): void
    {
        $customer = User::factory()->customer()->create(['phone' => '713444001']);

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('loop-bottom-nav', false)
            ->assertSee(__('loop.nav_rewards'), false)
            ->assertSee(__('loop.activity'), false)
            ->assertSee(__('loop.nav_account'), false)
            ->assertDontSee(__('loop.nav_till'), false);

        $this->actingAs($customer)
            ->get(route('member.activity'))
            ->assertOk()
            ->assertSee(__('loop.activity'), false);

        $this->actingAs($customer)
            ->get(route('more.index'))
            ->assertOk()
            ->assertSee(__('loop.stories'), false)
            ->assertDontSee(__('loop.billing'), false)
            ->assertDontSee(route('staff.index'), false);
    }

    public function test_front_desk_nav_stays_operational(): void
    {
        [$owner, $business] = $this->seedBusiness();
        $staff = User::factory()->frontDesk()->create([
            'phone' => '712444901',
            'business_id' => $business->id,
            'password' => 'password',
        ]);

        $this->actingAs($staff)
            ->get(route('till.index'))
            ->assertOk()
            ->assertSee('loop-bottom-nav', false)
            ->assertSee(__('loop.sale'), false)
            ->assertSee(__('loop.nav_more'), false)
            ->assertDontSee(__('loop.nav_campaigns'), false);

        $this->actingAs($staff)
            ->get(route('more.index'))
            ->assertOk()
            ->assertDontSee(__('loop.billing'), false)
            ->assertDontSee(__('loop.staff'), false)
            ->assertDontSee(route('campaigns.index'), false);

        $this->actingAs($staff)
            ->get(route('staff.index'))
            ->assertForbidden();
    }

    public function test_affiliate_has_own_bottom_nav(): void
    {
        $user = User::factory()->create([
            'phone' => '715444001',
            'role' => User::ROLE_AFFILIATE,
            'password' => 'password',
        ]);

        $this->actingAs($user)
            ->get(route('more.index'))
            ->assertOk()
            ->assertSee('loop-bottom-nav', false)
            ->assertSee(__('loop.nav_earnings'), false)
            ->assertSee(__('loop.nav_share'), false)
            ->assertDontSee(__('loop.nav_till'), false)
            ->assertDontSee(__('loop.billing'), false);
    }

    public function test_in_app_shell_stays_stable_across_member_tabs_and_till(): void
    {
        $boot = file_get_contents(resource_path('views/partials/head-boot.blade.php'));
        $this->assertStringContainsString('history.scrollRestoration', $boot);

        $js = file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString('markTabActive', $js);
        $this->assertStringContainsString('loop:locale-changing', $js);
        $this->assertStringContainsString("sessionStorage.setItem('loopNavKind', 'tab')", $js);

        $customer = User::factory()->customer()->create(['phone' => '713555001']);
        foreach ([
            route('dashboard'),
            route('discover'),
            route('memberships.index'),
            route('member.activity'),
            route('more.index'),
        ] as $url) {
            $this->actingAs($customer)
                ->get($url)
                ->assertOk()
                ->assertSee('loop-bottom-nav', false);
        }

        $this->actingAs($customer)
            ->get(route('more.index'))
            ->assertSee('loop:locale-changing', false);

        [$owner] = $this->seedBusiness();
        $this->actingAs($owner)
            ->get(route('till.index'))
            ->assertOk()
            ->assertSee('loop-bottom-nav', false)
            ->assertSee('data-loop-quiet', false);
    }

    public function test_staff_add_form_stays_visible_when_branches_are_full(): void
    {
        [$owner, $business, $shop] = $this->seedBusiness();
        $second = Shop::create([
            'business_id' => $business->id,
            'name' => 'Branch B',
            'city' => 'Dar es Salaam',
            'is_active' => true,
        ]);
        $desk = User::factory()->frontDesk()->create([
            'phone' => '712444911',
            'business_id' => $business->id,
            'password' => 'password',
        ]);
        $desk->assignedShops()->sync([$shop->id, $second->id]);

        $this->actingAs($owner)
            ->get(route('staff.index'))
            ->assertOk()
            ->assertSee('id="add-staff"', false)
            ->assertSee(__('loop.add_staff'), false)
            ->assertSee(__('loop.add_front_desk'), false)
            ->assertSee(__('loop.your_team'), false)
            ->assertSee(__('loop.all_shops_have_staff'), false);
    }

    public function test_desktop_nav_still_lists_owner_destinations(): void
    {
        [$owner] = $this->seedBusiness();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('loop.settings'), false)
            ->assertSee(__('loop.customers'), false);
    }

    /**
     * @return array{0: User, 1: Business, 2: Shop}
     */
    private function seedBusiness(): array
    {
        $owner = User::factory()->owner()->create(['phone' => '712444001']);
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Nav Shop',
            'slug' => 'nav-shop',
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
