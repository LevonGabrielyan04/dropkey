<?php

use App\Models\ChatMessage;
use App\Models\User;
use App\Repositories\Interfaces\ChatMessageRepositoryInterface;
use App\Services\Interfaces\ChatMessageServiceInterface;

it('defaults new chat messages to not viewed', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    expect($message->fresh()->is_viewed)->toBeFalse();
});

it('marks the opposite users messages as viewed when fetching messages', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);
    $repository = app(ChatMessageRepositoryInterface::class);
    $service = app(ChatMessageServiceInterface::class);

    $fromAlice = $repository->createMessage($conversation, $alice, fakeChatPayload());
    $fromBob = $repository->createMessage($conversation, $bob, fakeChatPayload());

    $this->actingAs($bob);

    $messages = $service->getMessagesForUsers($bob, $alice);

    expect($messages)->toHaveCount(2)
        ->and($fromAlice->fresh()->is_viewed)->toBeTrue()
        ->and($fromBob->fresh()->is_viewed)->toBeFalse();
});

it('does not mark the viewers own messages as viewed', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);
    $repository = app(ChatMessageRepositoryInterface::class);
    $service = app(ChatMessageServiceInterface::class);

    $fromAlice = $repository->createMessage($conversation, $alice, fakeChatPayload());

    $this->actingAs($alice);

    $service->getMessagesForUsers($alice, $bob);

    expect($fromAlice->fresh()->is_viewed)->toBeFalse();
});

it('marks only unviewed messages from the opposite user via the repository', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);
    $repository = app(ChatMessageRepositoryInterface::class);

    $unviewed = $repository->createMessage($conversation, $alice, fakeChatPayload());
    $alreadyViewed = $repository->createMessage($conversation, $alice, fakeChatPayload());
    $alreadyViewed->forceFill(['is_viewed' => true])->save();
    $fromBob = $repository->createMessage($conversation, $bob, fakeChatPayload());

    $updated = $repository->markMessagesAsViewed($conversation, $alice);

    expect($updated)->toBe(1)
        ->and($unviewed->fresh()->is_viewed)->toBeTrue()
        ->and($alreadyViewed->fresh()->is_viewed)->toBeTrue()
        ->and($fromBob->fresh()->is_viewed)->toBeFalse();
});
