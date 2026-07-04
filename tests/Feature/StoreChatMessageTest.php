<?php

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;

it('stores encrypted chat payloads without decrypting them', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $payload = fakeChatPayload();

    $this->actingAs($sender)
        ->postJson(route('messages.store'), [
            'recipient_id' => $recipient->id,
            'payload' => $payload,
        ])
        ->assertCreated()
        ->assertJsonStructure(['id', 'created_at']);

    $conversation = Conversation::query()
        ->where('user_one_id', min($sender->id, $recipient->id))
        ->where('user_two_id', max($sender->id, $recipient->id))
        ->first();

    expect($conversation)->not->toBeNull();

    $message = ChatMessage::query()->first();

    expect($message)->not->toBeNull()
        ->and($message->payload)->toBe($payload)
        ->and($message->sender_id)->toBe($sender->id);
});

it('rejects plaintext chat payloads', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $this->actingAs($sender)
        ->postJson(route('messages.store'), [
            'recipient_id' => $recipient->id,
            'payload' => 'hello world',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('payload');
});

it('rejects messages sent to yourself', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('messages.store'), [
            'recipient_id' => $user->id,
            'payload' => fakeChatPayload(),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('recipient_id');
});

it('returns encrypted messages for conversation participants', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = Conversation::findOrCreateForUsers($alice, $bob);
    $payload = fakeChatPayload();

    ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => $payload,
    ]);

    $this->actingAs($bob)
        ->getJson(route('messages.index', $alice))
        ->assertSuccessful()
        ->assertJsonCount(1, 'messages')
        ->assertJsonPath('messages.0.payload', $payload);
});

it('polls only messages after the provided cursor', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = Conversation::findOrCreateForUsers($alice, $bob);

    $first = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $bob->id,
        'payload' => fakeChatPayload(),
    ]);

    $this->actingAs($alice)
        ->getJson(route('messages.index', $bob).'?after_id='.$first->id)
        ->assertSuccessful()
        ->assertJsonCount(1, 'messages')
        ->assertJsonPath('messages.0.sender_id', $bob->id);
});

it('requires authentication for message relay endpoints', function () {
    $recipient = User::factory()->create();

    $this->postJson(route('messages.store'), [
        'recipient_id' => $recipient->id,
        'payload' => fakeChatPayload(),
    ])->assertUnauthorized();

    $this->getJson(route('messages.index', $recipient))
        ->assertUnauthorized();
});

it('creates a conversation when the first encrypted message is sent', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    expect(Conversation::query()->count())->toBe(0);

    $this->actingAs($sender)
        ->postJson(route('messages.store'), [
            'recipient_id' => $recipient->id,
            'payload' => fakeChatPayload(),
        ])
        ->assertCreated();

    $conversation = Conversation::query()->first();

    expect($conversation)->not->toBeNull()
        ->and($conversation->user_one_id)->toBe(min($sender->id, $recipient->id))
        ->and($conversation->user_two_id)->toBe(max($sender->id, $recipient->id));
});

it('rejects chat payloads that exceed the configured max length', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $maxLength = config('chat.payload.max_length');
    $payload = fakeChatPayload(7_000);

    expect(strlen($payload))->toBeGreaterThan($maxLength);

    $this->actingAs($sender)
        ->postJson(route('messages.store'), [
            'recipient_id' => $recipient->id,
            'payload' => $payload,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('payload');
});

it('rejects send-style encrypted payloads for chat messages', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $this->actingAs($sender)
        ->postJson(route('messages.store'), [
            'recipient_id' => $recipient->id,
            'payload' => fakeEncryptedMessage(),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('payload');
});

it('rejects unknown recipients', function () {
    $sender = User::factory()->create();

    $this->actingAs($sender)
        ->postJson(route('messages.store'), [
            'recipient_id' => 999_999,
            'payload' => fakeChatPayload(),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('recipient_id');
});
