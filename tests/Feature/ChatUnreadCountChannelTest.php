<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Str;

beforeEach(function () {
    // Channel callbacks are registered on the default broadcaster at boot
    // (null in phpunit.xml). Switch to reverb and re-register so auth is real.
    config(['broadcasting.default' => 'reverb']);
    Broadcast::forgetDrivers();
    require base_path('routes/channels.php');
});

it('authorizes the owning user to join their chat channel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-chat.'.$user->public_key,
            'socket_id' => '1234.5678',
        ])
        ->assertSuccessful()
        ->assertJsonStructure(['auth']);
});

it('denies other users access to a chat channel', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-chat.'.$owner->public_key,
            'socket_id' => '1234.5678',
        ])
        ->assertForbidden();
});

it('denies access when the chat channel user does not exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-chat.'.(string) Str::uuid(),
            'socket_id' => '1234.5678',
        ])
        ->assertForbidden();
});

it('redirects unauthenticated users away from the chat channel', function () {
    $user = User::factory()->create();

    $this->postJson('/broadcasting/auth', [
        'channel_name' => 'private-chat.'.$user->public_key,
        'socket_id' => '1234.5678',
    ])
        ->assertRedirect(route('login'));
});
