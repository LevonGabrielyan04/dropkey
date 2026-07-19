<?php

use App\Http\Resources\StoredChatMessageResource;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('resolves the chat message resource from the model', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);
    $payload = fakeChatPayload();

    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => $payload,
    ]);

    $message->load('sender:id,public_key');

    $resource = $message->toResource()
        ->response(Request::create('/'))
        ->getData(true)['data'];

    expect($resource)->toMatchArray([
        'public_id' => $message->public_id,
        'sender' => ['public_id' => $alice->public_key],
        'payload' => $payload,
        'is_viewed' => false,
    ])->and($resource)->not->toHaveKey('sender_id')
        ->and($resource)->not->toHaveKey('id');
});

it('resolves the chat message collection from the model collection', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    $message->load('sender:id,public_key');

    $resource = ChatMessage::query()
        ->with('sender:id,public_key')
        ->whereKey($message->id)
        ->get()
        ->toResourceCollection()
        ->response(Request::create('/'))
        ->getData(true);

    expect($resource)->toHaveKey('messages')
        ->and($resource['messages'])->toHaveCount(1)
        ->and($resource['messages'][0]['sender']['public_id'])->toBe($alice->public_key);
});

it('resolves the stored chat message resource for create responses', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    $message->load('conversation');

    $resource = $message->toResource(StoredChatMessageResource::class)
        ->response(Request::create('/'))
        ->getData(true);

    expect($resource)->toMatchArray([
        'public_id' => $message->public_id,
        'conversation_public_key' => $conversation->public_key,
        'is_viewed' => false,
        'created_at' => $message->created_at?->toJSON(),
    ])->and($resource)->not->toHaveKey('payload')
        ->and($resource)->not->toHaveKey('sender');
});
