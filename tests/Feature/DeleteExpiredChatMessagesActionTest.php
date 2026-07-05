<?php

use App\Actions\DeleteExpiredChatMessagesAction;
use App\Enums\TimePeriod;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;

it('permanently deletes chat messages older than the conversation retention period', function () {
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
        ->update(['created_at' => now()->subDays(8)]);

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

it('respects each conversation auto delete period', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $carol = User::factory()->create();

    $shortRetentionConversation = createConversation($alice, $bob);
    Conversation::query()
        ->whereKey($shortRetentionConversation->id)
        ->update(['auto_delete' => TimePeriod::ONE_HOUR->value]);

    $longRetentionConversation = createConversation($alice, $carol);
    Conversation::query()
        ->whereKey($longRetentionConversation->id)
        ->update(['auto_delete' => TimePeriod::THIRTY_DAYS->value]);

    $expiredShortRetentionMessage = ChatMessage::query()->create([
        'conversation_id' => $shortRetentionConversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    ChatMessage::query()
        ->whereKey($expiredShortRetentionMessage->id)
        ->update(['created_at' => now()->subHours(2)]);

    $recentShortRetentionMessage = ChatMessage::query()->create([
        'conversation_id' => $shortRetentionConversation->id,
        'sender_id' => $bob->id,
        'payload' => fakeChatPayload(),
    ]);

    $expiredLongRetentionMessage = ChatMessage::query()->create([
        'conversation_id' => $longRetentionConversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    ChatMessage::query()
        ->whereKey($expiredLongRetentionMessage->id)
        ->update(['created_at' => now()->subDays(8)]);

    $deletedCount = app(DeleteExpiredChatMessagesAction::class)->execute();

    expect($deletedCount)->toBe(1)
        ->and(ChatMessage::query()->count())->toBe(2)
        ->and(ChatMessage::query()->whereKey($expiredShortRetentionMessage->id)->exists())->toBeFalse()
        ->and(ChatMessage::query()->whereKey($recentShortRetentionMessage->id)->exists())->toBeTrue()
        ->and(ChatMessage::query()->whereKey($expiredLongRetentionMessage->id)->exists())->toBeTrue();
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
