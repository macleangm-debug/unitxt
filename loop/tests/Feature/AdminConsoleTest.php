<?php

namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\Business;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\User;
use App\Support\Plans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminConsoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_console_uses_a_sidebar_not_consumer_chips(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '710222000']);

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('admin-sidebar', false)
            ->assertSee('admin-console', false)
            ->assertSee(__('loop.admin_console'), false)
            ->assertDontSee('loop-admin-tabs', false);
    }

    public function test_members_table_paginates_with_a_chosen_page_size(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '710222001']);
        User::factory()->customer()->count(30)->create();

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.insights.customers', ['per_page' => 25]))
            ->assertOk()
            ->assertSee(__('loop.admin_rows_per_page'), false)
            ->assertSee('name="per_page"', false);
    }

    public function test_reports_view_more_shows_a_sector_table_and_export(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '710222002']);

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.reports.index', ['tab' => 'sectors']))
            ->assertOk()
            ->assertSee(__('loop.sales_by_sector'), false)
            ->assertSee(__('loop.export_csv'), false)
            ->assertSee('type=sales_by_sector', false);

        $this->actingAs($admin)
            ->get(route('admin.reports.export', ['type' => 'sales_by_sector', 'format' => 'csv']))
            ->assertOk();
    }

    public function test_business_index_has_summary_cards_and_empty_sales_are_hidden(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '710222003']);
        [$owner, $business] = $this->seedBusiness();

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.businesses.index'))
            ->assertOk()
            ->assertSee($business->name, false)
            ->assertSee(__('loop.admin_businesses'), false);

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.businesses.show', $business))
            ->assertOk()
            ->assertSee(__('loop.admin_roles'), false)
            ->assertSee(__('loop.owner'), false)
            ->assertSee(__('loop.front_desk'), false)
            ->assertDontSee(__('loop.recent_sales'), false);
    }

    public function test_story_form_includes_a_member_preview(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '710222004']);

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.articles.create'))
            ->assertOk()
            ->assertSee('articlePreview', false)
            ->assertSee(__('loop.article_preview'), false);
    }

    public function test_admin_can_create_an_affiliate(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '710222005']);

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->post(route('admin.affiliates.store'), [
                'first_name' => 'Asha',
                'last_name' => 'Mushi',
                'country' => 'TZ',
                'phone' => '715222111',
                'city' => 'Dar es Salaam',
                'password' => 'password12',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('affiliates', [
            'phone' => '715222111',
            'first_name' => 'Asha',
        ]);
        $this->assertNotNull(Affiliate::query()->where('phone', '715222111')->value('promo_code'));
    }

    public function test_settings_hub_is_read_first_and_packages_can_be_copied_per_country(): void
    {
        $this->seedPlans();
        $admin = User::factory()->admin()->create(['phone' => '710222006']);

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee(__('loop.settings_snapshot_title'), false);

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.settings', ['tab' => 'packages']))
            ->assertOk()
            ->assertSee(__('loop.add_country_packages'), false)
            ->assertSee(__('loop.edit'), false);

        $this->actingAs($admin)
            ->post(route('admin.settings.plans.clone-country'), [
                'from' => 'TZ',
                'to' => 'KE',
            ])
            ->assertRedirect(route('admin.settings', ['tab' => 'packages', 'country' => 'KE']));

        $this->assertTrue(Plan::query()->where('key', Plans::GROWTH)->where('country', 'KE')->exists());
        $this->assertSame('KES', Plan::query()->where('key', Plans::GROWTH)->where('country', 'KE')->value('currency'));
    }

    /**
     * @return array{0: User, 1: Business, 2: Shop}
     */
    private function seedBusiness(): array
    {
        $owner = User::factory()->owner()->create(['phone' => '712222221']);
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Harbor Admin Co',
            'slug' => 'harbor-admin-co',
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

    private function seedPlans(): void
    {
        foreach (Plans::catalog() as $key => $plan) {
            Plan::query()->updateOrCreate(
                ['key' => $key, 'country' => 'TZ'],
                [
                    'name' => $plan['name'],
                    'tagline' => $plan['tagline'],
                    'price_monthly' => $plan['price_monthly'],
                    'currency' => $plan['currency'],
                    'max_shops' => $plan['max_shops'],
                    'max_members' => $plan['max_members'],
                    'max_monthly_visits' => $plan['max_monthly_visits'] ?? null,
                    'max_product_pushes' => $plan['max_product_pushes'] ?? null,
                    'max_offers' => $plan['max_offers'] ?? null,
                    'has_raffles' => (bool) ($plan['has_raffles'] ?? false),
                    'has_sms' => (bool) ($plan['has_sms'] ?? false),
                    'is_public' => true,
                    'sort_order' => $plan['sort_order'],
                    'features' => $plan['features'],
                    'country' => 'TZ',
                ]
            );
        }
    }
}
