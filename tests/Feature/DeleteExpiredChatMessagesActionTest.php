<?php

use App\Actions\DeleteExpiredChatMessagesAction;
use App\Models\ChatMessage;
use App\Models\User;

it('permanently deletes chat messages older than the retention period', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $expiredMessage = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    ChatMessage::query()
        ->whereKey($expiredMessage->id)
        ->update(['created_at' => now()->subHours(25)]);

    ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $bob->id,
        'payload' => fakeChatPayload(),
    ]);

    $deletedCount = app(DeleteExpiredChatMessagesAction::class)->execute();

    expect($deletedCount)->toBe(1)
        ->and(ChatMessage::query()->count())->toBe(1)
        ->and(ChatMessage::query()->whereKey($expiredMessage->id)->exists())->toBeFalse();
});

it('returns zero when no chat messages have expired', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    $deletedCount = app(DeleteExpiredChatMessagesAction::class)->execute();

    expect($deletedCount)->toBe(0)
        ->and(ChatMessage::query()->count())->toBe(1);
});
