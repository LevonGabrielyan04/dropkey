<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Enums\TimePeriod;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface ConversationRepositoryInterface
{
    /**
     * @return Collection<int, Conversation>
     */
    public function getConversationsForUser(User $user): Collection;

    public function updateAutoDelete(Conversation $conversation, TimePeriod $autoDelete): Conversation;
}
