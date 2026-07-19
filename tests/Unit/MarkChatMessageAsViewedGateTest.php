<?php

use App\Gates\MarkChatMessageAsViewed;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('allows conversation recipients to mark the opposite users message as viewed', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    expect(Gate::forUser($bob)->allows('markChatMessageAsViewed', $message))->toBeTrue()
        ->and((new MarkChatMessageAsViewed)($bob, $message)->allowed())->toBeTrue();
});

it('denies the sender and outsiders from marking a message as viewed', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $outsider = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    expect(Gate::forUser($alice)->denies('markChatMessageAsViewed', $message))->toBeTrue()
        ->and(Gate::forUser($outsider)->denies('markChatMessageAsViewed', $message))->toBeTrue()
        ->and((new MarkChatMessageAsViewed)($alice, $message)->denied())->toBeTrue()
        ->and((new MarkChatMessageAsViewed)($outsider, $message)->denied())->toBeTrue();
});
