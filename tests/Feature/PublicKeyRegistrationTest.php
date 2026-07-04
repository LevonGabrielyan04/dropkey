<?php

use App\Models\User;
use App\Models\UserIdentityKey;

/**
 * @return array<string, mixed>
 */
function validPublicKeyPayload(): array
{
    return [
        'public_key_jwk' => [
            'kty' => 'EC',
            'crv' => 'P-256',
            'x' => 'test-public-x',
            'y' => 'test-public-y',
        ],
        'fingerprint' => str_repeat('a', 64),
    ];
}

it('registers a user public key', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.identity.public-key.store'), validPublicKeyPayload())
        ->assertSuccessful()
        ->assertJson(['status' => 'ok']);

    $identityKey = UserIdentityKey::query()->where('user_id', $user->id)->first();

    expect($identityKey)->not->toBeNull()
        ->and($identityKey->public_key_jwk['x'])->toBe('test-public-x')
        ->and($identityKey->fingerprint)->toBe(str_repeat('a', 64));
});

it('updates an existing public key registration', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.identity.public-key.store'), validPublicKeyPayload())
        ->assertSuccessful();

    $this->actingAs($user)
        ->postJson(route('api.identity.public-key.store'), [
            'public_key_jwk' => [
                'kty' => 'EC',
                'crv' => 'P-256',
                'x' => 'rotated-x',
                'y' => 'rotated-y',
            ],
            'fingerprint' => str_repeat('b', 64),
        ])
        ->assertSuccessful();

    $identityKey = UserIdentityKey::query()->where('user_id', $user->id)->first();

    expect($identityKey)->not->toBeNull()
        ->and($identityKey->public_key_jwk['x'])->toBe('rotated-x')
        ->and($identityKey->fingerprint)->toBe(str_repeat('b', 64));
});

it('exposes a partner public key to authenticated users', function () {
    $owner = User::factory()->create();
    $partner = User::factory()->create();

    UserIdentityKey::query()->create([
        'user_id' => $owner->id,
        'public_key_jwk' => validPublicKeyPayload()['public_key_jwk'],
        'fingerprint' => validPublicKeyPayload()['fingerprint'],
    ]);

    $this->actingAs($partner)
        ->getJson(route('api.users.public-key.show', $owner))
        ->assertSuccessful()
        ->assertJsonPath('user_id', $owner->id)
        ->assertJsonPath('fingerprint', str_repeat('a', 64));
});

it('returns not found when a partner has not registered a public key', function () {
    $owner = User::factory()->create();
    $partner = User::factory()->create();

    $this->actingAs($partner)
        ->getJson(route('api.users.public-key.show', $owner))
        ->assertNotFound();
});

it('rejects invalid public key payloads', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.identity.public-key.store'), [
            'public_key_jwk' => [
                'kty' => 'RSA',
                'crv' => 'P-256',
                'x' => 'bad',
                'y' => 'bad',
            ],
            'fingerprint' => 'not-a-valid-fingerprint',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['public_key_jwk.kty', 'fingerprint']);
});

it('reports whether the current user has registered a key', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('api.identity.public-key.mine'))
        ->assertSuccessful()
        ->assertJson(['registered' => false]);

    UserIdentityKey::query()->create([
        'user_id' => $user->id,
        ...validPublicKeyPayload(),
    ]);

    $this->actingAs($user)
        ->getJson(route('api.identity.public-key.mine'))
        ->assertSuccessful()
        ->assertJsonPath('registered', true)
        ->assertJsonPath('fingerprint', str_repeat('a', 64));
});
