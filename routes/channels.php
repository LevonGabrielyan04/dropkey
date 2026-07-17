<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return $user->id === $id;
});

Broadcast::channel('conversation.{conversationPublicKey}', function (User $user, string $conversationPublicKey): bool {
    $conversation = (new Conversation)->resolveRouteBinding($conversationPublicKey);

    if (! $conversation instanceof Conversation) {
        return false;
    }

    return $conversation->includesUser($user);
});
