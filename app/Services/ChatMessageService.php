<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use App\Repositories\Interfaces\ChatMessageRepositoryInterface;
use App\Services\Interfaces\ChatMessageServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class ChatMessageService implements ChatMessageServiceInterface
{
    public function __construct(protected ChatMessageRepositoryInterface $chatMessages) {}

    /**
     * {@inheritDoc}
     */
    public function getMessagesForUsers(User $user, User $otherUser, ?string $afterPublicId = null): Collection
    {
        $conversation = $this->chatMessages->findConversationBetweenUsers($user, $otherUser);

        if ($conversation === null) {
            return new Collection;
        }

        $this->authorizeConversation($conversation);

        return $this->chatMessages->getMessagesForConversation($conversation, $afterPublicId);
    }

    public function storeMessage(User $sender, User $recipient, string $payload): ChatMessage
    {
        $conversation = $this->authorizeConversation(
            $this->chatMessages->findOrCreateConversation($sender, $recipient),
        );

        return $this->chatMessages->createMessage($conversation, $sender, $payload);
    }

    private function authorizeConversation(Conversation $conversation): Conversation
    {
        Gate::authorize('view', $conversation);

        return $conversation;
    }
}
