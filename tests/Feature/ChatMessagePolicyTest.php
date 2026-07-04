<?php

use App\Models\Conversation;
use App\Models\User;

it('denies message polling when the authenticated user is not a conversation participant', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $eve = User::factory()->create();

    $conversation = Conversation::findOrCreateForUsers($alice, $bob);

    expect($eve->can('view', $conversation))->toBeFalse();

    $this->actingAs($eve)
        ->getJson(route('messages.index', $alice))
        ->assertSuccessful()
        ->assertJson(['messages' => []]);
});

it('denies storing messages for invalid recipients via validation', function () {
    $sender = User::factory()->create();

    $this->actingAs($sender)
        ->postJson(route('messages.store'), [
            'recipient_id' => 0,
            'payload' => fakeChatPayload(),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('recipient_id');
});

it('denies unauthenticated access to the message relay API', function () {
    $recipient = User::factory()->create();

    $this->getJson(route('messages.index', $recipient))
        ->assertUnauthorized();

    $this->postJson(route('messages.store'), [
        'recipient_id' => $recipient->id,
        'payload' => fakeChatPayload(),
    ])->assertUnauthorized();
});
