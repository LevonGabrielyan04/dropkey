<?php

namespace App\Support\Csp;

use Illuminate\Http\Request;

class DisablesCspForPulse
{
    public static function matches(Request $request): bool
    {
        $path = trim((string) config('pulse.path', 'pulse'), '/');

        if ($path === '') {
            return false;
        }

        $domain = config('pulse.domain');

        if (is_string($domain) && $domain !== '' && $request->getHost() !== $domain) {
            return false;
        }

        return $request->is($path) || $request->is($path.'/*');
    }
}
