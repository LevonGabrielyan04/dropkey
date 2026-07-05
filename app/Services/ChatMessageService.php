<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChatMessage;
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
    public function getMessagesForUsers(User $user, User $otherUser, int $afterId = 0): Collection
    {
        $conversation = $this->chatMessages->findConversationBetweenUsers($user, $otherUser);

        if ($conversation === null) {
            return new Collection;
        }

        Gate::authorize('view', $conversation);

        return $this->chatMessages->getMessagesForConversation($conversation, $afterId);
    }

    public function storeMessage(User $sender, int $recipientId, string $payload): ChatMessage
    {
        $recipient = $this->chatMessages->findUserOrFail($recipientId);
        $conversation = $this->chatMessages->findOrCreateConversation($sender, $recipient);

        Gate::authorize('view', $conversation);

        return $this->chatMessages->createMessage(
            $conversation,
            $sender->id,
            $payload,
        );
    }
}
