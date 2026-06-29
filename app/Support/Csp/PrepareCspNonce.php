<?php

namespace App\Support\Csp;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrepareCspNonce
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            config('csp.enabled')
            && config('csp.nonce_enabled')
            && ! DisablesCspForPulse::matches($request)
        ) {
            app('csp-nonce');
        }

        return $next($request);
    }
}
