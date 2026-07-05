<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface ChatMessageRepositoryInterface
{
    public function findConversationBetweenUsers(User $first, User $second): ?Conversation;

    /**
     * @return Collection<int, ChatMessage>
     */
    public function getMessagesForConversation(Conversation $conversation, int $afterId = 0): Collection;

    public function findUserOrFail(int $userId): User;

    public function findOrCreateConversation(User $first, User $second): Conversation;

    public function createMessage(Conversation $conversation, int $senderId, string $payload): ChatMessage;
}
