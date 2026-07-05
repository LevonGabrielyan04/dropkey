<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface ChatMessageServiceInterface
{
    /**
     * @return Collection<int, ChatMessage>
     */
    public function getMessagesForUsers(User $user, User $otherUser, int $afterId = 0): Collection;

    public function storeMessage(User $sender, int $recipientId, string $payload): ChatMessage;
}
