<?php

use App\Events\ChatMessagesViewedBroadcast;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\Interfaces\ChatMessageServiceInterface;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

it('broadcasts viewed public ids on the conversation receipts channel', function () {
    Event::fake([ChatMessagesViewedBroadcast::class]);

    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    $this->actingAs($bob);

    app(ChatMessageServiceInterface::class)->getMessagesForUsers($bob, $alice);

    Event::assertDispatched(ChatMessagesViewedBroadcast::class, function (ChatMessagesViewedBroadcast $event) use ($conversation, $message) {
        return $event->conversation->is($conversation)
            && $event->queue === 'broadcasts'
            && $event->broadcastAs() === 'ChatMessagesViewed'
            && $event->broadcastOn()[0]->name === 'private-conversation.'.$conversation->public_key.'.receipts'
            && $event->broadcastWith() === ['public_ids' => [$message->public_id]]
            && $event->broadcastWhen();
    });
});

it('does not broadcast when no messages were newly marked as viewed', function () {
    Event::fake([ChatMessagesViewedBroadcast::class]);

    $alice = User::factory()->create();
    $bob = User::factory()->create();
    createConversation($alice, $bob);

    $this->actingAs($bob);

    app(ChatMessageServiceInterface::class)->getMessagesForUsers($bob, $alice);

    Event::assertNotDispatched(ChatMessagesViewedBroadcast::class);
});

it('places the receipts broadcast job on the dedicated broadcasts queue', function () {
    Queue::fake();

    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    broadcast(new ChatMessagesViewedBroadcast($conversation, [(string) fake()->uuid()]));

    Queue::assertPushedOn('broadcasts', BroadcastEvent::class);
});
