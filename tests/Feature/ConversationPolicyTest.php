<?php

use App\Models\ChatMessage;
use App\Models\User;

it('denies non-participants from viewing a conversation via policy', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $eve = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    expect($eve->can('view', $conversation))->toBeFalse()
        ->and($alice->can('view', $conversation))->toBeTrue()
        ->and($bob->can('view', $conversation))->toBeTrue();
});

it('does not expose another users conversation when polling an unrelated partner', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $eve = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    $this->actingAs($eve)
        ->getJson(route('messages.index', $alice))
        ->assertSuccessful()
        ->assertJson(['messages' => []]);
});

it('denies viewing your own public key endpoint', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('api.users.public-key.show', $user))
        ->assertNotFound();
});

it('denies opening a chat thread with yourself', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('chat.show', $user))
        ->assertNotFound();
});

it('allows participants to open chat pages', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $this->actingAs($alice)
        ->get(route('chat.show', $bob))
        ->assertSuccessful()
        ->assertSee($bob->name);
});
