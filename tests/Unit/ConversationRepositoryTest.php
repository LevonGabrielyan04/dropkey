<?php

use App\Models\ChatMessage;
use App\Models\User;
use App\Repositories\Interfaces\ConversationRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns conversations for a user ordered by newest first', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $carol = User::factory()->create();
    $repository = app(ConversationRepositoryInterface::class);

    $olderConversation = createConversation($alice, $bob);
    $newerConversation = createConversation($alice, $carol);

    $conversations = $repository->getConversationsForUser($alice);

    expect($conversations)->toHaveCount(2)
        ->and($conversations->first()->is($newerConversation))->toBeTrue()
        ->and($conversations->last()->is($olderConversation))->toBeTrue();
});

it('does not return conversations the user is not part of', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $carol = User::factory()->create();
    $repository = app(ConversationRepositoryInterface::class);

    createConversation($bob, $carol);

    expect($repository->getConversationsForUser($alice))->toHaveCount(0);
});

it('eager loads participants and the latest message', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $repository = app(ConversationRepositoryInterface::class);
    $conversation = createConversation($alice, $bob);

    ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    $result = $repository->getConversationsForUser($alice)->sole();

    expect($result->relationLoaded('userOne'))->toBeTrue()
        ->and($result->relationLoaded('userTwo'))->toBeTrue()
        ->and($result->relationLoaded('messages'))->toBeTrue()
        ->and($result->messages)->toHaveCount(1);
});

it('counts unread messages from the other participant', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $repository = app(ConversationRepositoryInterface::class);
    $conversation = createConversation($alice, $bob);

    ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $bob->id,
        'payload' => fakeChatPayload(),
    ]);
    ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $bob->id,
        'payload' => fakeChatPayload(),
    ])->forceFill(['is_viewed' => true])->save();
    ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    $result = $repository->getConversationsForUser($alice)->sole();

    expect($result->unread_messages_count)->toBe(1);
});
