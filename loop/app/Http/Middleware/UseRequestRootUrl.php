<?php

namespace App\Http\Middleware;

use App\Support\PlatformUrl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UseRequestRootUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        // After TrustProxies: in-app absolute URLs match the browser host/scheme
        // (fixes tunnel demos linking to APP_URL / localhost).
        PlatformUrl::applyRequestRootUrl();

        return $next($request);
    }
}
