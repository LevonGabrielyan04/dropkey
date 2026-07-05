<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use App\Repositories\Interfaces\ChatMessageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ChatMessageRepository implements ChatMessageRepositoryInterface
{
    public function __construct(protected ChatMessage $chatMessage) {}

    public function findConversationBetweenUsers(User $first, User $second): ?Conversation
    {
        return Conversation::query()
            ->where('user_one_id', min($first->id, $second->id))
            ->where('user_two_id', max($first->id, $second->id))
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getMessagesForConversation(Conversation $conversation, int $afterId = 0): Collection
    {
        return $this->chatMessage->query()
            ->where('conversation_id', $conversation->id)
            ->when($afterId > 0, fn ($query) => $query->where('id', '>', $afterId))
            ->orderBy('id')
            ->limit(100)
            ->get(['id', 'sender_id', 'payload', 'created_at']);
    }

    public function findUserOrFail(int $userId): User
    {
        return User::query()->findOrFail($userId);
    }

    public function findOrCreateConversation(User $first, User $second): Conversation
    {
        return Conversation::findOrCreateForUsers($first, $second);
    }

    public function createMessage(Conversation $conversation, int $senderId, string $payload): ChatMessage
    {
        return $this->chatMessage->query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $senderId,
            'payload' => $payload,
        ]);
    }
}
