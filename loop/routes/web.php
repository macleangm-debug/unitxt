<?php

use App\Http\Controllers\Auth\BusinessRegisterController;
use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\Auth\StaffSessionController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TillController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');
Route::get('/for-business', fn () => view('landings.business'))->name('landing.business');
Route::get('/for-customers', fn () => view('landings.customer'))->name('landing.customer');

Route::get('/discover', DiscoverController::class)->name('discover');
Route::get('/discover/{business:slug}', [DiscoverController::class, 'show'])->name('discover.show');

Route::middleware('guest')->group(function () {
    Route::get('/business/register', [BusinessRegisterController::class, 'create'])->name('business.register');
    Route::post('/business/register', [BusinessRegisterController::class, 'store']);

    Route::get('/staff/login', [StaffSessionController::class, 'create'])->name('staff.login');
    Route::post('/staff/login', [StaffSessionController::class, 'store']);

    Route::get('/customer/login', [CustomerAuthController::class, 'create'])->name('customer.login');
    Route::post('/customer/login', [CustomerAuthController::class, 'send'])->name('customer.send');
    Route::get('/customer/otp', [CustomerAuthController::class, 'otpForm'])->name('customer.otp');
    Route::post('/customer/otp', [CustomerAuthController::class, 'verify'])->name('customer.verify');
});

Route::post('/logout', [StaffSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:owner,front_desk')->group(function () {
        Route::get('/till', [TillController::class, 'index'])->name('till.index');
        Route::post('/till/lookup', [TillController::class, 'lookup'])->name('till.lookup');
        Route::post('/till/sale', [TillController::class, 'store'])->name('till.store');
    });

    Route::middleware('role:owner')->group(function () {
        Route::resource('shops', ShopController::class)->except(['show']);
        Route::resource('campaigns', CampaignController::class)->except(['show']);
        Route::get('/rewards', [RewardController::class, 'index'])->name('rewards.index');
        Route::get('/rewards/create', [RewardController::class, 'create'])->name('rewards.create');
        Route::post('/rewards', [RewardController::class, 'store'])->name('rewards.store');
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::patch('/staff/{staff}/toggle', [StaffController::class, 'toggle'])->name('staff.toggle');
    });

    Route::middleware('role:customer')->group(function () {
        Route::get('/wallets', [MembershipController::class, 'index'])->name('memberships.index');
        Route::get('/wallets/{business:slug}', [MembershipController::class, 'show'])->name('memberships.show');
    });
});
