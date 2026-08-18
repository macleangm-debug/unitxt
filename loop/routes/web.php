<?php

use App\Http\Controllers\Admin\AffiliateController as AdminAffiliateController;
use App\Http\Controllers\Admin\BusinessController as AdminBusinessController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\InsightController as AdminInsightController;
use App\Http\Controllers\Admin\IntegrationController as AdminIntegrationController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\ReferralController as AdminReferralController;
use App\Http\Controllers\Admin\ReferralProgramController as AdminReferralProgramController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SettingsHubController as AdminSettingsHubController;
use App\Http\Controllers\AffiliateDashboardController;
use App\Http\Controllers\AffiliateLandingController;
use App\Http\Controllers\Auth\AffiliateAuthController;
use App\Http\Controllers\Auth\BusinessRegisterController;
use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\Auth\StaffSessionController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessInviteController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContentStudioController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerWalletQrController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\MemberMessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\RaffleController;
use App\Http\Controllers\ReferralHubController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TillController;
use App\Http\Controllers\TransactionController;
use App\Models\Plan;
use App\Support\MarketingSettings;
use App\Support\Plans;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/for-business', function () {
    return view('landings.business', [
        'plans' => Plans::publicPlans(),
        'pricingBlurb' => MarketingSettings::pricingBlurb(),
        'heroTagline' => MarketingSettings::heroTagline(),
        'marketing' => MarketingSettings::settings(),
    ]);
})->name('landing.business');

Route::get('/for-customers', fn () => view('landings.customer'))->name('landing.customer');
Route::get('/affiliates', [AffiliateLandingController::class, 'index'])->name('affiliates.landing');
Route::get('/affiliates/apply', [AffiliateLandingController::class, 'applyForm'])->name('affiliates.apply');
Route::post('/affiliates/apply', [AffiliateLandingController::class, 'apply'])->name('affiliates.apply.store');
Route::get('/affiliates/status', [AffiliateLandingController::class, 'statusForm'])->name('affiliates.status');
Route::post('/affiliates/status', [AffiliateLandingController::class, 'statusLookup'])->name('affiliates.status.lookup');
Route::get('/pricing', PricingController::class)->name('pricing');
Route::get('/locale/{locale}', [PreferenceController::class, 'locale'])->name('locale');
Route::post('/preference/country', [PreferenceController::class, 'country'])->name('preference.country');
Route::post('/webhooks/payin', [PaymentController::class, 'payinWebhook'])->name('payments.webhook.payin');

Route::get('/discover', DiscoverController::class)->name('discover');
Route::get('/discover/{business:slug}', [DiscoverController::class, 'show'])->name('discover.show');

Route::middleware('guest')->group(function () {
    Route::get('/business/register', [BusinessRegisterController::class, 'create'])->name('business.register');
    Route::post('/business/register', [BusinessRegisterController::class, 'store']);

    Route::get('/staff/login', [StaffSessionController::class, 'create'])->name('staff.login');
    Route::post('/staff/login', [StaffSessionController::class, 'store']);

    Route::get('/affiliate/login', [AffiliateAuthController::class, 'loginForm'])->name('affiliate.login');
    Route::post('/affiliate/login', [AffiliateAuthController::class, 'login']);
    Route::get('/affiliate/activate', [AffiliateAuthController::class, 'activateForm'])->name('affiliate.activate');
    Route::post('/affiliate/activate/lookup', [AffiliateAuthController::class, 'activateLookup'])->name('affiliate.activate.lookup');
    Route::post('/affiliate/activate', [AffiliateAuthController::class, 'activate'])->name('affiliate.activate.store');

    Route::get('/customer/login', [CustomerAuthController::class, 'create'])->name('customer.login');
    Route::post('/customer/login', [CustomerAuthController::class, 'send'])->name('customer.send');
    Route::get('/customer/pin', [CustomerAuthController::class, 'pinForm'])->name('customer.pin');
    Route::post('/customer/pin', [CustomerAuthController::class, 'pinVerify'])->name('customer.pin.verify');
    Route::get('/customer/register', [CustomerAuthController::class, 'registerForm'])->name('customer.register');
    Route::post('/customer/register', [CustomerAuthController::class, 'register'])->name('customer.register.store');
});

Route::post('/logout', [StaffSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [AdminReportController::class, 'export'])->name('reports.export');
        Route::get('/businesses', [AdminBusinessController::class, 'index'])->name('businesses.index');
        Route::get('/businesses/{business}', [AdminBusinessController::class, 'show'])->name('businesses.show');
        Route::patch('/businesses/{business}', [AdminBusinessController::class, 'update'])->name('businesses.update');
        Route::get('/referrals', [AdminReferralController::class, 'index'])->name('referrals.index');
        Route::get('/referrals/program', [AdminReferralProgramController::class, 'edit'])->name('referrals.program');
        Route::put('/referrals/program', [AdminReferralProgramController::class, 'update'])->name('referrals.program.update');
        Route::post('/referrals/{referral}/qualify', [AdminReferralController::class, 'qualify'])->name('referrals.qualify');
        Route::post('/referrals/{referral}/reward', [AdminReferralController::class, 'reward'])->name('referrals.reward');
        Route::get('/affiliates', [AdminAffiliateController::class, 'index'])->name('affiliates.index');
        Route::put('/affiliates/settings', [AdminAffiliateController::class, 'updateSettings'])->name('affiliates.settings');
        Route::get('/affiliates/{affiliate}', [AdminAffiliateController::class, 'show'])->name('affiliates.show');
        Route::post('/affiliates/{affiliate}/decide', [AdminAffiliateController::class, 'decide'])->name('affiliates.decide');
        Route::get('/plans', [AdminPlanController::class, 'index'])->name('plans.index');
        Route::put('/plans/{plan}', [AdminPlanController::class, 'update'])->name('plans.update');
        Route::get('/insights/sectors', [AdminInsightController::class, 'sectors'])->name('insights.sectors');
        Route::get('/insights/packages', [AdminInsightController::class, 'packages'])->name('insights.packages');
        Route::get('/insights/till-businesses', [AdminInsightController::class, 'tillBusinesses'])->name('insights.till-businesses');
        Route::get('/insights/affiliate-performance', [AdminInsightController::class, 'affiliatePerformance'])->name('insights.affiliate-performance');
        Route::get('/insights/customers', [AdminInsightController::class, 'customers'])->name('insights.customers');
        Route::get('/integrations', [AdminIntegrationController::class, 'index'])->name('integrations.index');
        Route::put('/integrations', [AdminIntegrationController::class, 'update'])->name('integrations.update');
        Route::post('/integrations/test-pay', [AdminIntegrationController::class, 'testPay'])->name('integrations.test-pay');
        Route::post('/integrations/test-sms', [AdminIntegrationController::class, 'testSms'])->name('integrations.test-sms');
        Route::post('/integrations/sender-ids', [AdminIntegrationController::class, 'storeSenderId'])->name('integrations.sender-ids.store');
        Route::patch('/integrations/sender-ids/{platformSenderId}', [AdminIntegrationController::class, 'updateSenderId'])->name('integrations.sender-ids.update');
        Route::post('/integrations/business-sender/{senderId}/activate', [AdminIntegrationController::class, 'activateBusinessSender'])->name('integrations.business-sender.activate');
        Route::post('/integrations/sms/businesses', [AdminIntegrationController::class, 'sendBusinessSms'])->name('integrations.sms.businesses');
        Route::post('/integrations/switch-primary', [AdminIntegrationController::class, 'switchPrimary'])->name('integrations.switch-primary');
        Route::get('/settings', [AdminSettingsHubController::class, 'index'])->name('settings');
        Route::put('/settings/billing', [AdminSettingsHubController::class, 'updateBilling'])->name('settings.billing');
        Route::put('/settings/growth', [AdminSettingsHubController::class, 'updateGrowth'])->name('settings.growth');
        Route::put('/settings/sectors', [AdminSettingsHubController::class, 'updateSectors'])->name('settings.sectors');
        Route::put('/settings/sales-visibility', [AdminSettingsHubController::class, 'updateSalesVisibility'])->name('settings.sales-visibility');
        Route::put('/settings/base-url', [AdminSettingsHubController::class, 'updatePlatformUrl'])->name('settings.base-url');
        Route::put('/settings/feature-flags', [AdminSettingsHubController::class, 'updateFeatureFlags'])->name('settings.feature-flags');
        Route::put('/settings/referrals', [AdminSettingsHubController::class, 'updateReferrals'])->name('settings.referrals');
        Route::put('/settings/affiliates', [AdminSettingsHubController::class, 'updateAffiliates'])->name('settings.affiliates');
        Route::put('/settings/countries', [AdminSettingsHubController::class, 'updateCountries'])->name('settings.countries');
        Route::put('/settings/notifications', [AdminSettingsHubController::class, 'updateNotifications'])->name('settings.notifications');
        Route::put('/settings/marketing', [AdminSettingsHubController::class, 'updateMarketing'])->name('settings.marketing');
        Route::put('/settings/plans/{plan}', [AdminSettingsHubController::class, 'updatePlan'])->name('settings.plans.update');
    });

    Route::get('/payments/{payment}/wait', [PaymentController::class, 'wait'])->name('payments.wait');
    Route::get('/payments/{payment}/status', [PaymentController::class, 'status'])->name('payments.status');
    Route::post('/payments/{payment}/stub-confirm', [PaymentController::class, 'stubConfirm'])->name('payments.stub-confirm');

    Route::middleware('role:affiliate')->prefix('affiliate')->name('affiliate.')->group(function () {
        Route::get('/', AffiliateDashboardController::class)->name('dashboard');
        Route::get('/setup', [AffiliateDashboardController::class, 'setupForm'])->name('setup');
        Route::post('/setup', [AffiliateDashboardController::class, 'setup'])->name('setup.store');
        Route::put('/promo', [AffiliateDashboardController::class, 'updatePromo'])->name('promo.update');
        Route::get('/payout', [AffiliateDashboardController::class, 'payoutForm'])->name('payout');
        Route::put('/payout', [AffiliateDashboardController::class, 'updatePayout'])->name('payout.update');
    });

    Route::middleware('role:owner')->group(function () {
        Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
        Route::post('/onboarding/logo', [OnboardingController::class, 'logo'])->name('onboarding.logo');
        Route::post('/onboarding/presence', [OnboardingController::class, 'presence'])->name('onboarding.presence');
        Route::post('/onboarding/branches', [OnboardingController::class, 'branches'])->name('onboarding.branches');
        Route::post('/onboarding/shop', [OnboardingController::class, 'shop'])->name('onboarding.shop');
        Route::post('/onboarding/campaign', [OnboardingController::class, 'campaign'])->name('onboarding.campaign');
        Route::post('/onboarding/offers', [OnboardingController::class, 'offers'])->name('onboarding.offers');

        Route::get('/settings', SettingsController::class)->name('settings');
        Route::get('/settings/referrals', ReferralHubController::class)->name('settings.referrals');
        Route::get('/business/settings', [BusinessController::class, 'edit'])->name('business.edit');
        Route::patch('/business/settings', [BusinessController::class, 'update'])->name('business.update');
        Route::get('/billing', [BillingController::class, 'show'])->name('billing.show');
        Route::post('/billing/choose', [BillingController::class, 'choose'])->name('billing.choose');
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::get('/members/messages', [MemberMessageController::class, 'index'])->name('members.messages.index');
        Route::post('/members/messages/sender', [MemberMessageController::class, 'storeSender'])->name('members.messages.sender');
        Route::post('/members/messages/starter/{platformSenderId}', [MemberMessageController::class, 'adoptStarter'])->name('members.messages.starter');
        Route::post('/members/messages/groups', [MemberMessageController::class, 'storeGroup'])->name('members.messages.groups');
        Route::post('/members/messages', [MemberMessageController::class, 'storeBroadcast'])->name('members.messages.store');
        Route::get('/raffles', [RaffleController::class, 'index'])->name('raffles.index');
        Route::get('/raffles/create', [RaffleController::class, 'create'])->name('raffles.create');
        Route::post('/raffles', [RaffleController::class, 'store'])->name('raffles.store');
        Route::get('/raffles/{raffle}', [RaffleController::class, 'show'])->name('raffles.show');
        Route::get('/raffles/{raffle}/live', [RaffleController::class, 'live'])->name('raffles.live');
        Route::post('/raffles/{raffle}/draw', [RaffleController::class, 'draw'])->name('raffles.draw');
        Route::post('/raffles/{raffle}/winners/{winner}/contact', [RaffleController::class, 'contact'])->name('raffles.contact');
        Route::post('/raffles/{raffle}/winners/{winner}/claim', [RaffleController::class, 'claim'])->name('raffles.claim');
        Route::get('/content-studio', [ContentStudioController::class, 'index'])->name('content-studio.index');
        Route::resource('shops', ShopController::class);
        Route::post('/campaigns/{campaign}/toggle', [CampaignController::class, 'toggle'])->name('campaigns.toggle');
        Route::resource('campaigns', CampaignController::class);
        Route::get('/offers', [RewardController::class, 'index'])->name('rewards.index');
        Route::get('/offers/create', [RewardController::class, 'create'])->name('rewards.create');
        Route::post('/offers', [RewardController::class, 'store'])->name('rewards.store');
        Route::get('/offers/{reward}/edit', [RewardController::class, 'edit'])->name('rewards.edit');
        Route::put('/offers/{reward}', [RewardController::class, 'update'])->name('rewards.update');
        Route::post('/offers/{reward}/toggle', [RewardController::class, 'toggle'])->name('rewards.toggle');
        Route::get('/offers/{reward}', [RewardController::class, 'show'])->name('rewards.show');
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::patch('/staff/{staff}/toggle', [StaffController::class, 'toggle'])->name('staff.toggle');
    });

    Route::middleware('role:owner,front_desk')->group(function () {
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/sale', [TillController::class, 'index'])->name('till.index');
        Route::post('/sale/lookup', [TillController::class, 'lookup'])->name('till.lookup');
        Route::get('/sale/ticket', [TillController::class, 'ticket'])->name('till.ticket');
        Route::get('/sale/registered', [TillController::class, 'registered'])->name('till.registered');
        Route::post('/sale/register-customer', [TillController::class, 'registerCustomer'])->name('till.register-customer');
        Route::post('/sale', [TillController::class, 'store'])->name('till.store');
        Route::post('/sale/redeem', [TillController::class, 'redeem'])->name('till.redeem');
    });

    Route::middleware('role:customer')->group(function () {
        Route::get('/wallets', [MembershipController::class, 'index'])->name('memberships.index');
        Route::get('/wallets/{business:slug}', [MembershipController::class, 'show'])->name('memberships.show');
        Route::get('/wallet/qr.svg', CustomerWalletQrController::class)->name('customer.wallet-qr');
        Route::post('/invite-business', [BusinessInviteController::class, 'store'])->name('business-invites.store');
    });
});
