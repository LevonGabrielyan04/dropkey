<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Policy Denial Logging
    |--------------------------------------------------------------------------
    |
    | Policy denials are logged for security auditing. Logging uses a two-tier
    | throttle: per-source limits catch noisy clients; a global limit caps total
    | volume when many distinct sources deny at once (e.g. distributed scans).
    |
    */

    'denial_logging' => [
        'enabled' => env('POLICY_DENIAL_LOGGING_ENABLED', true),

        'max_per_minute' => (int) env('POLICY_DENIAL_LOG_MAX_PER_MINUTE', 10),

        'global_max_per_minute' => (int) env('POLICY_DENIAL_LOG_GLOBAL_MAX_PER_MINUTE', 100),

        'throttle_by' => env('POLICY_DENIAL_LOG_THROTTLE_BY', 'ip'),
    ],

];
