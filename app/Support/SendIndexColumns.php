<?php

declare(strict_types=1);

namespace App\Support;

final class SendIndexColumns
{
    /**
     * Columns exposed on send index/list reads and used for list-cache keys.
     *
     * @var array<int, string>
     */
    public const COLUMNS = ['id', 'user_id', 'name', 'valid_to', 'public_id'];
}
