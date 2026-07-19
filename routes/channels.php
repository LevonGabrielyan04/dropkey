<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return $user->id === $id;
});

Broadcast::channel('conversation.{conversation}', function (User $user, Conversation $conversation): bool {
    return $conversation->includesUser($user);
});

Broadcast::channel('conversation.{conversation}.receipts', function (User $user, Conversation $conversation): bool {
    return $conversation->includesUser($user);
});
