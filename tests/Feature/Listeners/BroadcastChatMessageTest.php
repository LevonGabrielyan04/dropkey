<?php

use App\Events\ChatMessageBroadcast;
use App\Events\ChatMessageSent;
use App\Listeners\BroadcastChatMessage;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Event;

it('is registered to listen for ChatMessageSent', function () {
    Event::fake();

    Event::assertListening(
        ChatMessageSent::class,
        BroadcastChatMessage::class,
    );
});

it('broadcasts the chat message over the conversation channel', function () {
    Event::fake([ChatMessageBroadcast::class]);

    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $conversation = createConversation($sender, $recipient);

    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $sender->id,
        'payload' => fakeChatPayload(),
    ])->load(['conversation', 'sender:id,public_key']);

    (new BroadcastChatMessage)->handle(new ChatMessageSent($message, $sender, $recipient));

    Event::assertDispatched(ChatMessageBroadcast::class, function (ChatMessageBroadcast $event) use ($message, $conversation) {
        return $event->message->is($message)
            && $event->broadcastAs() === 'ChatMessageSent'
            && $event->broadcastOn()[0]->name === 'private-conversation.'.$conversation->public_key;
    });
});
