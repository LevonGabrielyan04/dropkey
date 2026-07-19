<?php

declare(strict_types=1);

namespace App\Gates;

use App\Models\ChatMessage;
use App\Models\User;
use App\Policies\Traits\HandlesPolicyResponses;
use Illuminate\Auth\Access\Response;

class MarkChatMessageAsViewed
{
    use HandlesPolicyResponses;

    /**
     * Recipients in the conversation may acknowledge a message as viewed.
     */
    public function __invoke(User $user, ChatMessage $message): Response
    {
        $message->loadMissing('conversation');

        return $this->sendResponse(
            $message->conversation->includesUser($user)
            && $user->id !== $message->sender_id
        );
    }
}
