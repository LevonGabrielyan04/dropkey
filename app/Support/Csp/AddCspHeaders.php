<?php

declare(strict_types=1);

namespace App\Support\Csp;

use Closure;
use Illuminate\Http\Request;
use Spatie\Csp\AddCspHeaders as SpatieAddCspHeaders;

class AddCspHeaders extends SpatieAddCspHeaders
{
    public function handle(
        Request $request,
        Closure $next,
        ?string $customPreset = null
    ): mixed {
        if (DisablesCspForPulse::matches($request)) {
            return $next($request);
        }

        return parent::handle($request, $next, $customPreset);
    }
}
