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
    public function getMessagesForUsers(User $user, User $otherUser, ?string $afterPublicId = null): Collection;

    public function storeMessage(User $sender, User $recipient, string $payload): ChatMessage;

    public function markMessageAsViewed(User $viewer, ChatMessage $message): void;
}
