<?php

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\BinaryCodec;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('assigns a unique uuid v4 public id when creating a chat message', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    expect($message->public_id)
        ->toBeString()
        ->and(Str::isUuid($message->public_id, version: 4))->toBeTrue();
});

it('stores chat message public ids as binary uuids in the database', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    $rawPublicId = DB::table('chat_messages')
        ->where('id', $message->id)
        ->value('public_id');

    expect($rawPublicId)
        ->toBeString()
        ->and(strlen($rawPublicId))->toBe(16)
        ->and(BinaryCodec::decode($rawPublicId, 'uuid'))->toBe($message->public_id);
});

it('enforces unique public ids across chat messages', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $firstMessage = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    $secondMessage = new ChatMessage([
        'conversation_id' => $conversation->id,
        'sender_id' => $bob->id,
        'payload' => fakeChatPayload(),
    ]);
    $secondMessage->public_id = $firstMessage->public_id;

    expect(fn () => $secondMessage->save())->toThrow(UniqueConstraintViolationException::class);
});
