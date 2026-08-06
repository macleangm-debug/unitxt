<?php

use App\Http\Controllers\Admin\BusinessController as AdminBusinessController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\ReferralController as AdminReferralController;
use App\Http\Controllers\Admin\ReferralProgramController as AdminReferralProgramController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SettingsHubController as AdminSettingsHubController;
use App\Http\Controllers\Auth\BusinessRegisterController;
use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\Auth\StaffSessionController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessInviteController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ReferralHubController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TillController;
use App\Http\Controllers\TransactionController;
use App\Models\Plan;
use App\Support\Plans;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', [
        'plans' => Plans::publicPlans(),
    ]);
})->name('home');

Route::get('/for-business', function () {
    return view('landings.business', [
        'plans' => Plans::publicPlans(),
    ]);
})->name('landing.business');

Route::get('/for-customers', fn () => view('landings.customer'))->name('landing.customer');
Route::get('/pricing', PricingController::class)->name('pricing');
Route::get('/locale/{locale}', [PreferenceController::class, 'locale'])->name('locale');
Route::post('/preference/country', [PreferenceController::class, 'country'])->name('preference.country');

Route::get('/discover', DiscoverController::class)->name('discover');
Route::get('/discover/{business:slug}', [DiscoverController::class, 'show'])->name('discover.show');

Route::middleware('guest')->group(function () {
    Route::get('/business/register', [BusinessRegisterController::class, 'create'])->name('business.register');
    Route::post('/business/register', [BusinessRegisterController::class, 'store']);

    Route::get('/staff/login', [StaffSessionController::class, 'create'])->name('staff.login');
    Route::post('/staff/login', [StaffSessionController::class, 'store']);

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

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [AdminReportController::class, 'export'])->name('reports.export');
        Route::get('/businesses', [AdminBusinessController::class, 'index'])->name('businesses.index');
        Route::patch('/businesses/{business}', [AdminBusinessController::class, 'update'])->name('businesses.update');
        Route::get('/referrals', [AdminReferralController::class, 'index'])->name('referrals.index');
        Route::get('/referrals/program', [AdminReferralProgramController::class, 'edit'])->name('referrals.program');
        Route::put('/referrals/program', [AdminReferralProgramController::class, 'update'])->name('referrals.program.update');
        Route::post('/referrals/{referral}/qualify', [AdminReferralController::class, 'qualify'])->name('referrals.qualify');
        Route::post('/referrals/{referral}/reward', [AdminReferralController::class, 'reward'])->name('referrals.reward');
        Route::get('/plans', [AdminPlanController::class, 'index'])->name('plans.index');
        Route::get('/settings', [AdminSettingsHubController::class, 'index'])->name('settings');
        Route::put('/settings/billing', [AdminSettingsHubController::class, 'updateBilling'])->name('settings.billing');
    });

    Route::middleware('role:owner')->group(function () {
        Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
        Route::post('/onboarding/logo', [OnboardingController::class, 'logo'])->name('onboarding.logo');
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
        Route::resource('shops', ShopController::class);
        Route::resource('campaigns', CampaignController::class);
        Route::get('/offers', [RewardController::class, 'index'])->name('rewards.index');
        Route::get('/offers/create', [RewardController::class, 'create'])->name('rewards.create');
        Route::post('/offers', [RewardController::class, 'store'])->name('rewards.store');
        Route::get('/offers/{reward}', [RewardController::class, 'show'])->name('rewards.show');
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::patch('/staff/{staff}/toggle', [StaffController::class, 'toggle'])->name('staff.toggle');
    });

    Route::middleware('role:owner,front_desk')->group(function () {
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/sale', [TillController::class, 'index'])->name('till.index');
        Route::post('/sale/lookup', [TillController::class, 'lookup'])->name('till.lookup');
        Route::post('/sale', [TillController::class, 'store'])->name('till.store');
    });

    Route::middleware('role:customer')->group(function () {
        Route::get('/wallets', [MembershipController::class, 'index'])->name('memberships.index');
        Route::get('/wallets/{business:slug}', [MembershipController::class, 'show'])->name('memberships.show');
        Route::post('/invite-business', [BusinessInviteController::class, 'store'])->name('business-invites.store');
    });
});
