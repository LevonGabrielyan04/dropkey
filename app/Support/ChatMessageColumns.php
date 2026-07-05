<?php

declare(strict_types=1);

namespace App\Support;

final class ChatMessageColumns
{
    /**
     * Columns exposed when polling chat messages.
     *
     * @var array<int, string>
     */
    public const COLUMNS = ['id', 'sender_id', 'payload', 'created_at'];
}
