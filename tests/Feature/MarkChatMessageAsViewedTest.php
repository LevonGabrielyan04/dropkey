<?php

use App\Events\ChatMessagesViewedBroadcast;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

it('marks an unviewed message as viewed and broadcasts a read receipt', function () {
    Event::fake([ChatMessagesViewedBroadcast::class]);

    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    $this->actingAs($bob)
        ->postJson(route('messages.viewed', $message))
        ->assertNoContent();

    expect($message->fresh()->is_viewed)->toBeTrue();

    Event::assertDispatched(ChatMessagesViewedBroadcast::class, function (ChatMessagesViewedBroadcast $event) use ($conversation, $message) {
        return $event->conversation->is($conversation)
            && $event->broadcastWith() === ['public_ids' => [$message->public_id]];
    });
});

it('does not rebroadcast when the message is already viewed', function () {
    Event::fake([ChatMessagesViewedBroadcast::class]);

    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);
    $message->forceFill(['is_viewed' => true])->save();

    $this->actingAs($bob)
        ->postJson(route('messages.viewed', $message))
        ->assertNoContent();

    Event::assertNotDispatched(ChatMessagesViewedBroadcast::class);
});

it('rejects marking the viewers own message as viewed', function () {
    Event::fake([ChatMessagesViewedBroadcast::class]);

    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    $this->actingAs($alice)
        ->postJson(route('messages.viewed', $message))
        ->assertNotFound();

    expect($message->fresh()->is_viewed)->toBeFalse();

    Event::assertNotDispatched(ChatMessagesViewedBroadcast::class);
});

it('hides messages outside the viewers conversations', function () {
    Event::fake([ChatMessagesViewedBroadcast::class]);

    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $outsider = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    $this->actingAs($outsider)
        ->postJson(route('messages.viewed', $message))
        ->assertNotFound();

    expect($message->fresh()->is_viewed)->toBeFalse();

    Event::assertNotDispatched(ChatMessagesViewedBroadcast::class);
});

it('returns not found for an unknown message public id', function () {
    $bob = User::factory()->create();

    $this->actingAs($bob)
        ->postJson(route('messages.viewed', ['message' => (string) Str::uuid()]))
        ->assertNotFound();
});

it('requires authentication', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    $this->postJson(route('messages.viewed', $message))
        ->assertUnauthorized();
});
