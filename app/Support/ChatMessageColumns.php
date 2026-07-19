<?php

declare(strict_types=1);

namespace App\Support;

final class ChatMessageColumns
{
    /**
     * Columns loaded when polling chat messages.
     *
     * @var array<int, string>
     */
    public const COLUMNS = ['public_id', 'sender_id', 'payload', 'is_viewed', 'created_at'];
}
