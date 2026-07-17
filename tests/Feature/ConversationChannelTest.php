<?php

use App\Models\User;
use Illuminate\Support\Str;

it('authorizes conversation participants to join the private channel', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $this->actingAs($alice)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-conversation.'.$conversation->public_key,
            'socket_id' => '1234.5678',
        ])
        ->assertSuccessful();

    $this->actingAs($bob)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-conversation.'.$conversation->public_key,
            'socket_id' => '1234.5678',
        ])
        ->assertSuccessful();
});

it('denies strangers access to the conversation channel', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $stranger = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $this->actingAs($stranger)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-conversation.'.$conversation->public_key,
            'socket_id' => '1234.5678',
        ])
        ->assertForbidden();
});

it('denies access when the conversation does not exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-conversation.'.(string) Str::uuid(),
            'socket_id' => '1234.5678',
        ])
        ->assertForbidden();
});

it('denies unauthenticated access to the conversation channel', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $this->postJson('/broadcasting/auth', [
        'channel_name' => 'private-conversation.'.$conversation->public_key,
        'socket_id' => '1234.5678',
    ])
        ->assertForbidden();
});
