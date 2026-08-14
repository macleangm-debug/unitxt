<?php

use App\Http\Middleware\EnsureUserRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [
            \App\Http\Middleware\UseRequestRootUrl::class,
            \App\Http\Middleware\SetLocale::class,
        ]);
        $middleware->alias([
            'role' => EnsureUserRole::class,
        ]);
        $middleware->redirectGuestsTo(function (Request $request) {
            $path = trim($request->path(), '/');
            if (str_starts_with($path, 'affiliate')) {
                return route('affiliate.login');
            }
            if (
                str_starts_with($path, 'wallets')
                || str_starts_with($path, 'wallet')
                || $path === 'customer'
                || str_starts_with($path, 'customer/')
            ) {
                return route('customer.login');
            }
            if (
                str_starts_with($path, 'sale')
                || str_starts_with($path, 'transactions')
                || str_starts_with($path, 'customers')
                || str_starts_with($path, 'settings')
                || str_starts_with($path, 'admin')
                || str_starts_with($path, 'staff')
                || str_starts_with($path, 'campaigns')
                || str_starts_with($path, 'shops')
                || str_starts_with($path, 'billing')
            ) {
                return route('staff.login');
            }

            return route('home');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
