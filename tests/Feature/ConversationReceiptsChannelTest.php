<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Str;

beforeEach(function () {
    config(['broadcasting.default' => 'reverb']);
    Broadcast::forgetDrivers();
    require base_path('routes/channels.php');
});

it('authorizes conversation participants to join the receipts channel', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $this->actingAs($alice)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-conversation.'.$conversation->public_key.'.receipts',
            'socket_id' => '1234.5678',
        ])
        ->assertSuccessful()
        ->assertJsonStructure(['auth']);

    $this->actingAs($bob)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-conversation.'.$conversation->public_key.'.receipts',
            'socket_id' => '1234.5678',
        ])
        ->assertSuccessful()
        ->assertJsonStructure(['auth']);
});

it('denies strangers access to the receipts channel', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $stranger = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $this->actingAs($stranger)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-conversation.'.$conversation->public_key.'.receipts',
            'socket_id' => '1234.5678',
        ])
        ->assertForbidden();
});

it('denies access when the receipts conversation does not exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-conversation.'.(string) Str::uuid().'.receipts',
            'socket_id' => '1234.5678',
        ])
        ->assertForbidden();
});

it('redirects unauthenticated users away from the receipts channel', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $this->postJson('/broadcasting/auth', [
        'channel_name' => 'private-conversation.'.$conversation->public_key.'.receipts',
        'socket_id' => '1234.5678',
    ])
        ->assertRedirect(route('login'));
});
