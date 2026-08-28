<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\Plan;
use App\Models\Reward;
use App\Models\Shop;
use App\Models\User;
use App\Services\TillService;
use App\Support\Plans;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Plans::catalog() as $key => $plan) {
            Plan::query()->updateOrCreate(
                ['key' => $key],
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
                    'has_games' => (bool) ($plan['has_games'] ?? false),
                    'is_public' => true,
                    'sort_order' => $plan['sort_order'],
                    'features' => $plan['features'],
                ]
            );
        }

        \App\Models\PlatformSetting::putValue(\App\Support\BillingSettings::KEY, \App\Support\BillingSettings::defaults());
        \App\Models\PlatformSetting::putValue(\App\Support\ReferralProgram::KEY, \App\Support\ReferralProgram::defaults());
        \App\Models\PlatformSetting::putValue(\App\Support\GrowthSettings::KEY, \App\Support\GrowthSettings::defaults());
        \App\Models\PlatformSetting::putValue(\App\Support\FeatureFlags::KEY, \App\Support\FeatureFlags::defaults());
        \App\Models\PlatformSetting::putValue(\App\Support\GameSettings::KEY, \App\Support\GameSettings::defaults());
        \App\Models\PlatformSetting::putValue(\App\Support\AffiliateProgram::KEY, \App\Support\AffiliateProgram::defaults());

        User::factory()->admin()->create([
            'first_name' => 'Loop',
            'last_name' => 'Admin',
            'phone' => '710000000',
            'email' => 'admin@loop.test',
            'password' => Hash::make('password'),
        ]);

        $owner = User::factory()->owner()->create([
            'first_name' => 'Amina',
            'last_name' => 'Owusu',
            'phone' => '712000001',
            'email' => 'business@loop.test',
            'password' => Hash::make('password'),
        ]);

        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Harbor Beans',
            'slug' => 'harbor-beans',
            'sector' => 'coffee',
            'country' => 'TZ',
            'currency' => 'TZS',
            'city' => 'Dar es Salaam',
            'hotline' => '+255 712 000 001',
            'description' => 'Neighborhood coffee with Loop loyalty on every cup.',
            'plan_key' => Plans::GROWTH,
            'billing_status' => 'active',
            'trial_ends_at' => now()->subDay(),
            'referral_code' => 'HARBOR01',
        ]);

        $owner->update(['business_id' => $business->id]);

        $frontDesk = User::factory()->frontDesk()->create([
            'first_name' => 'Neema',
            'last_name' => 'Juma',
            'phone' => '712000002',
            'password' => Hash::make('password'),
            'business_id' => $business->id,
        ]);

        $downtown = Shop::create([
            'business_id' => $business->id,
            'name' => 'Harbor Beans Downtown',
            'code' => 'SHOP-HBDOWN',
            'address' => 'Samora Avenue',
            'city' => 'Dar es Salaam',
            'phone' => '+255 712 000 001',
            'is_active' => true,
        ]);

        $waterfront = Shop::create([
            'business_id' => $business->id,
            'name' => 'Harbor Beans Waterfront',
            'code' => 'SHOP-HBWAVE',
            'address' => 'Slipway',
            'city' => 'Dar es Salaam',
            'phone' => '+255 712 000 002',
            'is_active' => true,
        ]);

        $frontDesk->assignedShops()->sync([$downtown->id, $waterfront->id]);

        $campaign = Campaign::create([
            'business_id' => $business->id,
            'name' => 'Everyday earn',
            'type' => 'earn',
            'description' => 'Every TZS 1,000 = 2 points',
            'spend_step' => 1000,
            'points_per_step' => 2,
            'bonus_points' => 0,
            'starts_at' => now()->subDays(7),
            'ends_at' => now()->addMonths(3),
            'is_active' => true,
            'template_key' => 'everyday_earn',
        ]);
        $campaign->shops()->sync([$downtown->id]);

        Campaign::create([
            'business_id' => $business->id,
            'name' => 'Birthday treat',
            'type' => 'birthday',
            'bonus_points' => 50,
            'starts_at' => now()->subDays(7),
            'is_active' => true,
            'template_key' => 'birthday_treat',
        ]);

        Reward::create([
            'business_id' => $business->id,
            'name' => '5% off anything',
            'description' => 'Applied at the till when you have 100 points.',
            'points_cost' => 100,
            'reward_type' => 'percent_off',
            'reward_value' => 5,
            'is_active' => true,
        ]);

        $customer = User::factory()->customer()->create([
            'first_name' => 'Kojo',
            'last_name' => 'Mensah',
            'phone' => '713000001',
            'country' => 'TZ',
            'city' => 'Dar es Salaam',
            'interests' => ['coffee', 'fashion'],
            'birth_month' => (int) now()->format('n'),
            'birth_day' => (int) now()->format('j'),
            'password' => Hash::make('1234'),
            'phone_verified_at' => now(),
            'profile_completed' => true,
        ]);

        app(TillService::class)->recordSale($frontDesk, $downtown, $customer, 10000);

        $extraMembers = [
            ['Asha', 'Mushi', '713000002'],
            ['Baraka', 'Ngoma', '713000003'],
            ['Clara', 'Mwakyusa', '713000004'],
            ['David', 'Kimaro', '713000005'],
            ['Eliza', 'Shayo', '713000006'],
            ['Faraji', 'Hassan', '713000007'],
            ['Grace', 'Lyimo', '713000008'],
            ['Hassan', 'Omar', '713000009'],
            ['Irene', 'Massawe', '713000010'],
            ['Juma', 'Kweka', '713000011'],
            ['Lulu', 'Ngowi', '713000012'],
        ];
        foreach ($extraMembers as $i => [$first, $last, $phone]) {
            $member = User::factory()->customer()->create([
                'first_name' => $first,
                'last_name' => $last,
                'phone' => $phone,
                'country' => 'TZ',
                'city' => 'Dar es Salaam',
                'password' => Hash::make('1234'),
                'phone_verified_at' => now(),
                'profile_completed' => true,
            ]);
            app(TillService::class)->recordSale($frontDesk, $downtown, $member, 2500 + ($i * 400));
        }

        $business->raffles()->create([
            'created_by' => $owner->id,
            'name' => 'Friday coffee draw',
            'prize_name' => 'Free pourover',
            'prize_type' => 'custom',
            'winners_count' => 2,
            'frequency' => 'once',
            'draw_at' => now()->toDateString(),
            'claim_days' => 7,
            'status' => 'scheduled',
            'is_active' => true,
        ]);

        $business->update(['onboarding_completed_at' => now()]);

        // Fashion demo business for sector grouping
        $fashionOwner = User::factory()->owner()->create([
            'first_name' => 'Fatma',
            'last_name' => 'Ali',
            'phone' => '714000001',
            'password' => Hash::make('password'),
        ]);
        $fashion = Business::create([
            'owner_id' => $fashionOwner->id,
            'name' => 'Kanga Collective',
            'slug' => 'kanga-collective',
            'sector' => 'fashion',
            'country' => 'TZ',
            'currency' => 'TZS',
            'city' => 'Dar es Salaam',
            'hotline' => '+255 714 000 001',
        ]);
        $fashionOwner->update(['business_id' => $fashion->id]);
        Shop::create([
            'business_id' => $fashion->id,
            'name' => 'Kanga Collective Masaki',
            'city' => 'Dar es Salaam',
            'is_active' => true,
        ]);
        Campaign::create([
            'business_id' => $fashion->id,
            'name' => 'Style points',
            'type' => 'earn',
            'spend_step' => 5000,
            'points_per_step' => 5,
            'starts_at' => now()->subDay(),
            'is_active' => true,
        ]);

        // Restaurant demo for discover carousels
        $restoOwner = User::factory()->owner()->create([
            'first_name' => 'Joseph',
            'last_name' => 'Mwangi',
            'phone' => '715000001',
            'password' => Hash::make('password'),
        ]);
        $resto = Business::create([
            'owner_id' => $restoOwner->id,
            'name' => 'Coast Kitchen',
            'slug' => 'coast-kitchen',
            'sector' => 'restaurants',
            'country' => 'TZ',
            'currency' => 'TZS',
            'city' => 'Dar es Salaam',
            'hotline' => '+255 715 000 001',
            'description' => 'Coastal plates with Loop points on every table.',
            'onboarding_completed_at' => now(),
        ]);
        $restoOwner->update(['business_id' => $resto->id]);
        Shop::create([
            'business_id' => $resto->id,
            'name' => 'Coast Kitchen Oyster Bay',
            'city' => 'Dar es Salaam',
            'is_active' => true,
        ]);
        Campaign::create([
            'business_id' => $resto->id,
            'name' => 'Table earn',
            'type' => 'earn',
            'spend_step' => 2000,
            'points_per_step' => 3,
            'starts_at' => now()->subDay(),
            'is_active' => true,
        ]);

        Article::query()->create([
            'user_id' => User::query()->where('role', User::ROLE_ADMIN)->value('id'),
            'slug' => 'welcome-to-loop-tanzania',
            'title_en' => 'Harbor Beans is live in Dar',
            'title_sw' => 'Harbor Beans imeanza Dar',
            'excerpt_en' => 'Coffee points across Downtown and Waterfront — one balance, two shops.',
            'excerpt_sw' => 'Pointi za kahawa Downtown na Waterfront — salio moja, maduka mawili.',
            'body_en' => "Harbor Beans joined Loop this week.\n\nEarn on every cup, then redeem a free drink once you hit the offer. Same points at Downtown and Waterfront.",
            'body_sw' => "Harbor Beans imejiunga na Loop wiki hii.\n\nPata pointi kila kikombe, kisha komboa kinywaji bure unapofikia ofa. Pointi zilezile Downtown na Waterfront.",
            'country' => 'TZ',
            'audience' => Article::AUDIENCE_MEMBERS,
            'published_at' => now()->subHour(),
        ]);

        Article::query()->create([
            'user_id' => User::query()->where('role', User::ROLE_ADMIN)->value('id'),
            'slug' => 'how-loop-offers-work',
            'title_en' => 'How Loop offers unlock',
            'title_sw' => 'Ofa za Loop zinavyofunguka',
            'excerpt_en' => 'Reach the points, then come back another day unless the shop allows same-day redeem.',
            'excerpt_sw' => 'Fikia pointi, kisha rudi siku nyingine isipokuwa duka linaruhusu kukomboa siku ileile.',
            'body_en' => "Loop’s default is simple: points you earn today do not unlock an offer until a later day.\n\nThat is how most loyalty programs avoid buying a reward on the same ticket. A shop can turn same-day redeem on in Redeem settings.",
            'body_sw' => "Chaguo-msingi la Loop ni rahisi: pointi za leo hazifungui ofa hadi siku nyingine.\n\nProgramu nyingi za uaminifu hufanya hivyo ili mtu asinunue ofa kwenye tiketi ileile. Duka linaweza kuwasha kukomboa siku ileile kwenye mipangilio.",
            'country' => null,
            'audience' => Article::AUDIENCE_MEMBERS,
            'published_at' => now()->subDay(),
        ]);
        app(\App\Services\LegalService::class)->syncDrafts();
    }
}
