<?php

use App\Models\User;

it('lets user two read an encrypted message sent by user one', function () {
    $userOne = User::factory()->create();
    $userTwo = User::factory()->create();
    $payload = fakeChatPayload();

    $this->actingAs($userOne)
        ->postJson(route('messages.store'), [
            'recipient_id' => $userTwo->id,
            'payload' => $payload,
        ])
        ->assertCreated();

    $this->actingAs($userTwo)
        ->getJson(route('messages.index', $userOne))
        ->assertSuccessful()
        ->assertJsonCount(1, 'messages')
        ->assertJsonPath('messages.0.payload', $payload)
        ->assertJsonPath('messages.0.sender.public_id', $userOne->public_key);
});
