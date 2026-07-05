<?php

use App\Models\ChatMessage;
use App\Models\User;

it('requires authentication to view the chat inbox', function () {
    $this->get(route('chat.index'))
        ->assertRedirect(route('login'));
});

it('renders the chat inbox with the start conversation form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('chat.index'))
        ->assertSuccessful()
        ->assertSee('data-chat-base-url', false)
        ->assertSee('e2eeChatInbox', false)
        ->assertSee(__('Start a conversation'))
        ->assertSee(__('Open channel'));
});

it('renders the encrypted chat session shell for a recipient', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $this->actingAs($alice)
        ->get(route('chat.show', $bob))
        ->assertSuccessful()
        ->assertSee('x-data="e2eeChatSession"', false)
        ->assertSee('data-recipient-id="'.$bob->id.'"', false)
        ->assertSee('data-local-user-id="'.$alice->id.'"', false)
        ->assertSee(route('api.users.public-key.show', $bob), false)
        ->assertSee(route('messages.index', $bob), false)
        ->assertSee(route('messages.store'), false)
        ->assertSee($bob->name)
        ->assertSee(__('Send encrypted message'))
        ->assertSee(':disabled="!canSendMessage"', false)
        ->assertSee('data-decryption-failed-message', false)
        ->assertSee(__('Unable to decrypt this message.'), false)
        ->assertSee('message.decryptionError', false)
        ->assertDontSee('${partnerFingerprint}', false);
});

it('includes the messages navigation link in the sidebar', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee(route('chat.index'), false)
        ->assertSee(__('Messages'));
});

it('lists existing conversations on the inbox page', function () {
    $alice = User::factory()->create(['name' => 'Alice Chat']);
    $bob = User::factory()->create(['name' => 'Bob Chat']);
    $conversation = createConversation($alice, $bob);

    ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    $this->actingAs($alice)
        ->get(route('chat.index'))
        ->assertSuccessful()
        ->assertSee('Bob Chat')
        ->assertSee(route('chat.show', $bob), false);
});
