<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\Interfaces\ChatMessageRepositoryInterface;

class DeleteExpiredChatMessagesAction
{
    public function __construct(private ChatMessageRepositoryInterface $chatMessages) {}

    /**
     * Permanently delete all chat messages older than the configured retention period.
     */
    public function execute(): int
    {
        return $this->chatMessages->deleteExpired();
    }
}
