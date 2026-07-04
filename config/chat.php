<?php

return [
    'payload' => [
        'max_length' => env('CHAT_ENCRYPTED_MAX_LENGTH', 8192),
    ],
    'poll_interval_ms' => env('CHAT_POLL_INTERVAL_MS', 3000),
];
