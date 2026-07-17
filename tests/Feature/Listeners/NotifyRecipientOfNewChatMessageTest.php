<?php

use App\Events\ChatMessageSent;
use App\Listeners\NotifyRecipientOfNewChatMessage;
use App\Models\ChatMessage;
use App\Models\User;
use App\Notifications\NewChatMessageNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

it('is registered to listen for ChatMessageSent', function () {
    Event::fake();

    Event::assertListening(
        ChatMessageSent::class,
        NotifyRecipientOfNewChatMessage::class,
    );
});

it('notifies the recipient when they have a push subscription', function () {
    Notification::fake();

    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $conversation = createConversation($sender, $recipient);

    $recipient->updatePushSubscription(
        'https://fcm.googleapis.com/fcm/send/test-endpoint',
        'p256dh-test-key',
        'auth-test-token',
        'aes128gcm',
    );

    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $sender->id,
        'payload' => fakeChatPayload(),
    ]);

    (new NotifyRecipientOfNewChatMessage)->handle(new ChatMessageSent($message, $sender, $recipient));

    Notification::assertSentTo($recipient, NewChatMessageNotification::class, function (NewChatMessageNotification $notification) use ($sender) {
        return $notification->sender->is($sender);
    });
    Notification::assertNotSentTo($sender, NewChatMessageNotification::class);
});

it('does not notify recipients without a push subscription', function () {
    Notification::fake();

    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $conversation = createConversation($sender, $recipient);

    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $sender->id,
        'payload' => fakeChatPayload(),
    ]);

    (new NotifyRecipientOfNewChatMessage)->handle(new ChatMessageSent($message, $sender, $recipient));

    Notification::assertNothingSent();
});
