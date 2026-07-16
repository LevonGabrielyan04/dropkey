<?php

use App\Models\User;
use App\Models\UserIdentityKey;

/**
 * Server-side contract for the live-chat key-rotation fix:
 * after a partner refreshes their identity key and sends a new message,
 * the other participant must be able to (1) observe the new fingerprint
 * and (2) poll the new ciphertext without refreshing the page.
 */
it('exposes a rotated partner key and relays post-rotation messages for live polling', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $this->actingAs($alice)
        ->postJson(route('api.identity.public-key.store'), validPublicKeyPayload())
        ->assertSuccessful();

    $this->actingAs($bob)
        ->postJson(route('api.identity.public-key.store'), [
            'public_key_jwk' => [
                'kty' => 'EC',
                'crv' => 'P-256',
                'x' => 'bob-original-x',
                'y' => 'bob-original-y',
            ],
            'fingerprint' => str_repeat('b', 64),
        ])
        ->assertSuccessful();

    $this->actingAs($alice)
        ->getJson(route('api.users.public-key.show', $bob))
        ->assertSuccessful()
        ->assertJsonPath('fingerprint', str_repeat('b', 64))
        ->assertJsonPath('public_key_jwk.x', 'bob-original-x');

    $this->actingAs($alice)
        ->postJson(route('messages.store'), [
            'recipient_id' => $bob->id,
            'payload' => fakeChatPayload(),
        ])
        ->assertCreated();

    $this->actingAs($bob)
        ->postJson(route('messages.store'), [
            'recipient_id' => $alice->id,
            'payload' => fakeChatPayload(40),
        ])
        ->assertCreated();

    $aliceHistory = $this->actingAs($alice)
        ->getJson(route('messages.index', $bob))
        ->assertSuccessful()
        ->assertJsonCount(2, 'messages')
        ->json('messages');

    $lastSeenPublicId = $aliceHistory[array_key_last($aliceHistory)]['public_id'];

    $this->actingAs($bob)
        ->postJson(route('api.identity.public-key.store'), [
            'public_key_jwk' => [
                'kty' => 'EC',
                'crv' => 'P-256',
                'x' => 'bob-rotated-x',
                'y' => 'bob-rotated-y',
            ],
            'fingerprint' => str_repeat('c', 64),
        ])
        ->assertSuccessful();

    expect(UserIdentityKey::query()->where('user_id', $bob->id)->count())->toBe(1);

    $this->actingAs($alice)
        ->getJson(route('api.users.public-key.show', $bob))
        ->assertSuccessful()
        ->assertJsonPath('fingerprint', str_repeat('c', 64))
        ->assertJsonPath('public_key_jwk.x', 'bob-rotated-x')
        ->assertJsonMissingPath('public_key_jwk.d');

    $postRotationPayload = fakeChatPayload(48);

    $this->actingAs($bob)
        ->postJson(route('messages.store'), [
            'recipient_id' => $alice->id,
            'payload' => $postRotationPayload,
        ])
        ->assertCreated();

    $this->actingAs($alice)
        ->getJson(route('messages.index', $bob).'?after_public_id='.$lastSeenPublicId)
        ->assertSuccessful()
        ->assertJsonCount(1, 'messages')
        ->assertJsonPath('messages.0.payload', $postRotationPayload)
        ->assertJsonPath('messages.0.sender.public_id', $bob->public_key);
});
