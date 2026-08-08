<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
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
        // Keep built CSS/JS host-relative so tunnels/domains still style pages
        // even when APP_URL / Base URL point elsewhere.
        Vite::createAssetPathsUsing(fn (string $path, $secure = null) => '/'.ltrim($path, '/'));

        // Request-host URL root is applied in UseRequestRootUrl middleware
        // (after TrustProxies), not here — boot() runs too early for X-Forwarded-*.
    }
}
