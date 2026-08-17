<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DenyBrowserPrompts
{
    /**
     * Block notification / push permission prompts on every response
     * (web, mobile browser, and in-app webviews).
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('Permissions-Policy', 'notifications=(), push=()');
        $response->headers->set('Feature-Policy', "notifications 'none'; push 'none'");

        return $response;
    }
}
