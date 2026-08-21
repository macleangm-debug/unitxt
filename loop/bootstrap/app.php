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
        $middleware->append(\App\Http\Middleware\DenyBrowserPrompts::class);
        $middleware->web(append: [
            \App\Http\Middleware\UseRequestRootUrl::class,
            \App\Http\Middleware\SetLocale::class,
        ]);
        $middleware->alias([
            'role' => EnsureUserRole::class,
        ]);
        $middleware->redirectGuestsTo(function (Request $request) {
            $path = $request->path();
            if ($path === 'admin' || str_starts_with($path, 'admin/')) {
                return route('staff.login', ['admin' => 1]);
            }
            if (str_starts_with($path, 'affiliate')) {
                return route('affiliate.login');
            }
            if (str_starts_with($path, 'wallets') || str_starts_with($path, 'customer') || $path === 'invite-business') {
                return route('customer.login');
            }

            return route('staff.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
        $exceptions->render(function (\Throwable $e, Request $request) {
            return \App\Support\LoopExceptionRenderer::response($e, $request);
        });
    })->create();
