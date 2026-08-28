<?php

namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\Business;
use App\Models\SettingAudit;
use App\Models\Shop;
use App\Models\User;
use App\Services\AffiliateService;
use App\Support\AffiliateProgram;
use App\Support\BillingSettings;
use App\Support\CountrySettings;
use App\Support\FeatureFlags;
use App\Support\GrowthSettings;
use App\Support\Sectors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformAlignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_hub_system_check_and_audit_are_admin_only(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '710333001']);

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.settings', ['tab' => 'health']))
            ->assertOk()
            ->assertSee(__('loop.settings_health_title'))
            ->assertSee(__('loop.settings_audit_title'))
            ->assertSee(__('loop.health_connected'));

        $this->actingAs($admin)
            ->put(route('admin.settings.countries'), ['enabled' => ['TZ']])
            ->assertRedirect();

        $this->assertTrue(SettingAudit::query()->where('setting_key', CountrySettings::KEY)->exists());
        $this->assertSame(['TZ'], \App\Support\Countries::enabledCodes());
    }

    public function test_disabled_country_leaves_every_selector(): void
    {
        \App\Models\PlatformSetting::putValue(CountrySettings::KEY, ['enabled' => ['TZ']]);

        $this->withSession(['locale' => 'en'])
            ->get(route('business.register'))
            ->assertOk()
            ->assertSee('+255', false)
            ->assertDontSee('+254', false);

        $this->withSession(['locale' => 'en'])
            ->get(route('affiliates.apply'))
            ->assertOk()
            ->assertSee(__('loop.search_city'), false)
            ->assertSee('Tanzania', false)
            ->assertDontSee('Kenya', false);

        $this->withSession(['locale' => 'en'])
            ->get(route('discover'))
            ->assertOk()
            ->assertSee('Tanzania', false)
            ->assertDontSee('Kenya', false);

        $this->post('/business/register', [
            'first_name' => 'Amina',
            'last_name' => 'Owusu',
            'country' => 'KE',
            'phone' => '0712111444',
            'password' => 'password',
            'password_confirmation' => 'password',
            'business_name' => 'Nairobi Cafe',
            'sector' => 'coffee',
        ])->assertSessionHasErrors('country');
    }

    public function test_affiliate_off_stops_applications_and_promo_lookup_but_not_login(): void
    {
        \App\Models\PlatformSetting::putValue(AffiliateProgram::KEY, AffiliateProgram::normalizeInput([
            ...AffiliateProgram::defaults(),
            'enabled' => 0,
        ]));

        $this->get(route('affiliates.landing'))
            ->assertOk()
            ->assertDontSee(__('loop.become_affiliate'), false);

        $this->get(route('affiliates.apply'))
            ->assertRedirect(route('affiliates.landing'));

        $this->assertNull(app(AffiliateService::class)->findByPromo('LOOPJOY'));

        $affiliate = Affiliate::query()->create([
            'first_name' => 'Joy',
            'last_name' => 'Partner',
            'country' => 'TZ',
            'country_code' => '+255',
            'phone' => '715333001',
            'id_type' => 'national_id',
            'id_number' => 'ID-333001',
            'status' => 'active',
            'promo_code' => 'LOOPJOY',
            'tracking_code' => 'TRKJOY1',
            'setup_completed_at' => now(),
            'activated_at' => now(),
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_AFFILIATE,
            'phone' => '715333001',
            'country_code' => '+255',
            'pin_hash' => Hash::make('1234'),
        ]);
        $affiliate->update(['user_id' => $user->id]);

        $this->withSession(['locale' => 'en'])
            ->get(route('affiliate.login'))
            ->assertOk()
            ->assertSee(__('loop.affiliate_stamp'), false)
            ->assertSee(__('loop.affiliate_aside_title'), false)
            ->assertDontSee(__('loop.customer_stamp'), false)
            ->assertDontSee(__('loop.password'), false);

        $this->post('/affiliate/login', [
            'country_code' => '+255',
            'phone' => '715333001',
            'pin' => '1234',
        ])->assertRedirect(route('affiliate.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_new_affiliate_attribution_uses_hub_commission(): void
    {
        \App\Models\PlatformSetting::putValue(AffiliateProgram::KEY, AffiliateProgram::normalizeInput([
            ...AffiliateProgram::defaults(),
            'enabled' => 1,
            'commission_percent' => 12,
            'referred_discount_percent' => 8,
        ]));

        $affiliate = Affiliate::query()->create([
            'first_name' => 'Joy',
            'last_name' => 'Partner',
            'country' => 'TZ',
            'country_code' => '+255',
            'phone' => '715333002',
            'id_type' => 'national_id',
            'id_number' => 'ID-333002',
            'status' => 'active',
            'promo_code' => 'LOOP12',
            'tracking_code' => 'TRK1201',
        ]);

        [$owner, $business] = $this->seedBusiness();

        $referral = app(AffiliateService::class)->attachToBusiness($business, 'LOOP12');

        $this->assertNotNull($referral);
        $this->assertSame(12, (int) $referral->commission_percent);
        $this->assertSame(8, (int) $business->fresh()->referral_discount_percent);
    }

    public function test_product_flags_pause_studio_raffles_and_sms_without_deleting_history(): void
    {
        [$owner, $business] = $this->seedBusiness();
        $business->raffles()->create([
            'name' => 'Kept raffle',
            'prize_name' => 'Coffee',
            'prize_type' => 'free_item',
            'winners_count' => 1,
            'frequency' => 'once',
            'draw_at' => now()->addDay(),
            'claim_days' => 7,
            'status' => 'scheduled',
        ]);

        \App\Models\PlatformSetting::putValue(FeatureFlags::KEY, FeatureFlags::normalizeInput([
            'pay_with_points' => 1,
            'premium_clients' => 1,
            'owner_daily_digest' => 1,
            'customer_unlock_hints' => 1,
            'birthday_campaigns' => 1,
            'welcome_campaigns' => 1,
            'streak_campaigns' => 1,
            'featured_product' => 1,
            'raffles' => 0,
            'content_studio' => 0,
            'sms_messaging' => 0,
            'member_daily_digest' => 1,
            'affiliate_daily_digest' => 1,
        ]));

        $this->actingAs($owner)
            ->get(route('content-studio.index'))
            ->assertRedirect(route('settings'));

        $this->actingAs($owner)
            ->get(route('raffles.index'))
            ->assertOk()
            ->assertSee(__('loop.feature_paused_raffles_title'))
            ->assertSee('Kept raffle')
            ->assertDontSee(__('loop.create_raffle'), false);

        $this->actingAs($owner)
            ->get(route('raffles.create'))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('members.messages.index'))
            ->assertOk()
            ->assertSee(__('loop.feature_paused_sms_title'));

        $this->actingAs($owner)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertDontSee(__('loop.send_messages'), false);

        $this->actingAs($owner)
            ->get(route('settings'))
            ->assertOk()
            ->assertDontSee(__('loop.content_studio'), false)
            ->assertSee(__('loop.raffles'));
    }

    public function test_raffle_minimum_members_comes_from_growth_policy(): void
    {
        \App\Models\PlatformSetting::putValue(GrowthSettings::KEY, [
            ...GrowthSettings::defaults(),
            'raffle_min_members' => 40,
        ]);

        [$owner, $business] = $this->seedBusiness();
        $business->update(['plan_key' => 'growth', 'billing_status' => 'active']);
        $owner->unsetRelation('ownedBusiness');

        $this->actingAs($owner)
            ->get(route('raffles.index'))
            ->assertOk()
            ->assertSee(__('loop.raffle_locked_title'))
            ->assertSee('40');
    }

    public function test_trial_days_and_sectors_follow_the_hub(): void
    {
        \App\Models\PlatformSetting::putValue(BillingSettings::KEY, BillingSettings::normalizeInput([
            ...BillingSettings::defaults(),
            'trial_days' => 7,
            'block_till_when_trial_ends' => 1,
        ]));
        \App\Models\PlatformSetting::putValue(Sectors::KEY, Sectors::normalizeInput([
            ...Sectors::OPTIONS,
            'gelato' => 'Gelato bars',
        ]));

        $this->assertSame(7, \App\Support\Plans::trialDays());

        $this->get(route('business.register'))
            ->assertOk()
            ->assertSee('Gelato bars', false)
            ->assertSee('Laundry', false);

        $this->post('/business/register', [
            'first_name' => 'Amina',
            'last_name' => 'Owusu',
            'country' => 'TZ',
            'phone' => '0712111555',
            'password' => 'password',
            'password_confirmation' => 'password',
            'business_name' => 'Gelato Lane',
            'sector' => 'gelato',
        ])->assertRedirect(route('onboarding.show'));

        $business = Business::query()->where('name', 'Gelato Lane')->first();
        $this->assertNotNull($business);
        $this->assertSame('gelato', $business->sector);
        $this->assertTrue($business->trial_ends_at->isSameDay(now()->addDays(7)));
    }

    public function test_sector_catalogue_is_searchable_and_misses_reach_the_hub(): void
    {
        $this->post(route('business.register'), [
            'first_name' => 'Neema',
            'last_name' => 'Linen',
            'country' => 'TZ',
            'phone' => '0712111666',
            'password' => 'password',
            'password_confirmation' => 'password',
            'business_name' => 'Fresh Fold',
            'sector' => 'laundry',
        ])->assertRedirect(route('onboarding.show'));

        $this->assertDatabaseHas('businesses', [
            'name' => 'Fresh Fold',
            'sector' => 'laundry',
        ]);

        $this->post(route('sector-search.miss'), ['q' => 'Butchery'])
            ->assertOk();

        $this->assertDatabaseHas('sector_search_misses', [
            'query_key' => 'butchery',
            'query' => 'Butchery',
        ]);

        $admin = User::factory()->admin()->create(['phone' => '710333019']);
        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.settings', ['tab' => 'sectors']))
            ->assertOk()
            ->assertSee(__('loop.sector_search_misses'), false)
            ->assertSee('Butchery', false)
            ->assertSee(__('loop.sector_aliases_placeholder'), false);
    }

    public function test_admin_ops_pages_keep_affiliate_and_referral_programs_named_apart(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '710333009']);

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.affiliates.index'))
            ->assertOk()
            ->assertSee(__('loop.admin_affiliates_program_kind'))
            ->assertDontSee(__('loop.admin_referrals_program_kind'), false);

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.referrals.index'))
            ->assertOk()
            ->assertSee(__('loop.admin_referrals_program_kind'))
            ->assertDontSee(__('loop.admin_affiliates_program_kind'), false);
    }

    /**
     * @return array{0: User, 1: Business, 2: Shop}
     */
    private function seedBusiness(): array
    {
        $owner = User::factory()->owner()->create(['phone' => '712333001']);
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Align Shop',
            'slug' => 'align-shop',
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
