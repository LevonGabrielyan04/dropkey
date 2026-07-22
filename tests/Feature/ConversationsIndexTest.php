<?php

use App\Models\ChatMessage;
use App\Models\User;

it('requires authentication to list conversations', function () {
    $this->getJson(route('conversations.index'))
        ->assertUnauthorized();
});

it('returns the authenticated users conversations as json', function () {
    $alice = User::factory()->create(['name' => 'Alice Inbox']);
    $bob = User::factory()->create(['name' => 'Bob Inbox']);
    $carol = User::factory()->create(['name' => 'Carol Outside']);
    $conversation = createConversation($alice, $bob);
    createConversation($bob, $carol);

    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $bob->id,
        'payload' => fakeChatPayload(),
    ]);

    $this->actingAs($alice)
        ->getJson(route('conversations.index'))
        ->assertSuccessful()
        ->assertJsonPath('conversations.0.public_key', $conversation->public_key)
        ->assertJsonPath('conversations.0.unread_messages_count', 1)
        ->assertJsonPath('conversations.0.partner.name', 'Bob Inbox')
        ->assertJsonPath('conversations.0.partner.url', route('chat.show', $bob))
        ->assertJsonPath('conversations.0.last_message_at', $message->created_at->utc()->toIso8601String())
        ->assertJsonCount(1, 'conversations');
});
