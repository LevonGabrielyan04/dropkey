<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\TimePeriod;
use App\Models\Conversation;
use App\Models\User;
use App\Repositories\Interfaces\ConversationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ConversationRepository implements ConversationRepositoryInterface
{
    public function __construct(protected Conversation $model) {}

    /**
     * {@inheritDoc}
     */
    public function getConversationsForUser(User $user): Collection
    {
        return $this->model->query()
            ->where(function ($query) use ($user): void {
                $query->where('user_one_id', $user->id)
                    ->orWhere('user_two_id', $user->id);
            })
            ->with([
                'userOne',
                'userTwo',
                'messages' => fn ($query) => $query->latest('id')->limit(1),
            ])
            ->withCount([
                'messages as unread_messages_count' => function ($query) use ($user): void {
                    $query->where('sender_id', '!=', $user->id)
                        ->where('is_viewed', false);
                },
            ])
            ->latest('id')
            ->get();
    }

    public function updateAutoDelete(Conversation $conversation, TimePeriod $autoDelete): Conversation
    {
        $conversation->update([
            'auto_delete' => $autoDelete,
        ]);

        return $conversation->refresh();
    }
}
