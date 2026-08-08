<?php

namespace App\Providers;

use App\Support\PlatformUrl;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            PlatformUrl::applyRootUrl();
        } catch (\Throwable) {
            // Platform settings table may not exist during early migrate.
        }
    }
}
