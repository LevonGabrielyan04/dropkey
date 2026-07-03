<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class PolicyDenialLogger
{
    private const GLOBAL_THROTTLE_KEY = 'policy-denial:global';

    public function log(): void
    {
        if (! config('policy.denial_logging.enabled')) {
            return;
        }

        if ($this->isThrottled(self::GLOBAL_THROTTLE_KEY, config('policy.denial_logging.global_max_per_minute'))) {
            return;
        }

        $sourceKey = $this->sourceThrottleKey();

        if ($this->isThrottled($sourceKey, config('policy.denial_logging.max_per_minute'))) {
            return;
        }

        RateLimiter::hit(self::GLOBAL_THROTTLE_KEY);
        RateLimiter::hit($sourceKey);

        Log::warning('Policy authorization denied', [
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
        ]);
    }

    private function isThrottled(string $key, int $maxPerMinute): bool
    {
        return RateLimiter::tooManyAttempts($key, $maxPerMinute);
    }

    private function sourceThrottleKey(): string
    {
        $throttleBy = config('policy.denial_logging.throttle_by', 'ip');

        $suffix = match ($throttleBy) {
            'user' => (string) (auth()->id() ?? 'guest'),
            'ip_and_user' => request()->ip().'|'.(auth()->id() ?? 'guest'),
            default => request()->ip(),
        };

        return 'policy-denial:'.$suffix;
    }
}
