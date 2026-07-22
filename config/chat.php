<?php

return [
    'cache_ttl' => env('CHAT_CACHE_TTL', 60),
    'payload' => [
        'max_length' => env('CHAT_ENCRYPTED_MAX_LENGTH', 8192),
    ],
    'poll' => [
        'batch_size' => env('CHAT_POLL_BATCH_SIZE', 100),
    ],
    'poll_interval_ms' => env('CHAT_POLL_INTERVAL_MS', 3000),
    'retention_hours' => env('CHAT_RETENTION_HOURS', 24),
];
