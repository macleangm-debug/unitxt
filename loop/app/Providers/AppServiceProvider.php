<?php

namespace App\Providers;

use App\Models\ExceptionHit;
use App\Routing\RelativeUrlGenerator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Default route()/action() URLs to host-relative paths so previews and
        // tunnels never emit http://127.0.0.1:8000 links that leave the app.
        $this->app->singleton('url', function ($app) {
            $routes = $app['router']->getRoutes();
            $app->instance('routes', $routes);

            return new RelativeUrlGenerator(
                $routes,
                $app->rebinding('request', function ($app, $request) {
                    $app['url']->setRequest($request);
                }),
                $app['config']['app.asset_url']
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Keep built CSS/JS host-relative so tunnels/domains still style pages
        // even when APP_URL / Base URL point elsewhere.
        Vite::createAssetPathsUsing(fn (string $path, $secure = null) => '/'.ltrim($path, '/'));

        View::composer('layouts.admin', function ($view) {
            $inbox = \App\Support\AdminInbox::items();
            $view->with('openExceptionHits', ExceptionHit::openCount());
            $view->with('pendingAffiliateApps', \App\Models\Affiliate::query()->where('status', 'pending')->count());
            $view->with('adminInbox', $inbox);
            $view->with('adminInboxCount', count($inbox));
        });

        // Request-host URL root is applied in UseRequestRootUrl middleware
        // (after TrustProxies), not here — boot() runs too early for X-Forwarded-*.
    }
}
