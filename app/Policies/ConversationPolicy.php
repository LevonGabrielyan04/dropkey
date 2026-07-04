<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use App\Policies\Traits\HandlesPolicyResponses;
use Illuminate\Auth\Access\Response;

class ConversationPolicy
{
    use HandlesPolicyResponses;

    /**
     * Determine whether the user can view the conversation.
     */
    public function view(User $user, Conversation $conversation): Response
    {
        return $this->sendResponse($conversation->includesUser($user));
    }
}
