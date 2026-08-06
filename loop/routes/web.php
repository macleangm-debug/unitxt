<?php

use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\VisitController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:business')->group(function () {
        Route::get('/business/setup', [BusinessController::class, 'create'])->name('business.setup');
        Route::post('/business', [BusinessController::class, 'store'])->name('business.store');
        Route::get('/business/edit', [BusinessController::class, 'edit'])->name('business.edit');
        Route::patch('/business', [BusinessController::class, 'update'])->name('business.update');

        Route::resource('shops', ShopController::class)->except(['show']);
        Route::resource('campaigns', CampaignController::class)->except(['show']);
        Route::get('/rewards', [RewardController::class, 'index'])->name('rewards.index');
        Route::get('/rewards/create', [RewardController::class, 'create'])->name('rewards.create');
        Route::post('/rewards', [RewardController::class, 'store'])->name('rewards.store');
    });

    Route::middleware('role:customer')->group(function () {
        Route::get('/check-in', [VisitController::class, 'create'])->name('visits.create');
        Route::post('/check-in', [VisitController::class, 'store'])->name('visits.store');
        Route::get('/memberships', [MembershipController::class, 'index'])->name('memberships.index');
        Route::post('/businesses/{business}/join', [MembershipController::class, 'join'])->name('memberships.join');
        Route::get('/businesses/{business}/wallet', [MembershipController::class, 'show'])->name('memberships.show');
        Route::get('/businesses/{business}/rewards', [RewardController::class, 'catalog'])->name('rewards.catalog');
        Route::post('/rewards/{reward}/redeem', [RewardController::class, 'redeem'])->name('rewards.redeem');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
