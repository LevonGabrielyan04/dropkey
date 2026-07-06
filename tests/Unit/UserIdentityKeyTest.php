<?php

use App\Models\User;
use App\Models\UserIdentityKey;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('assigns a unique browser database id when creating an identity key', function () {
    $user = User::factory()->create();

    $identityKey = UserIdentityKey::query()->create([
        'user_id' => $user->id,
        'public_key_jwk' => validPublicKeyPayload()['public_key_jwk'],
        'fingerprint' => validPublicKeyPayload()['fingerprint'],
    ]);

    expect($identityKey->browser_db_id)
        ->not->toBeEmpty()
        ->and(Str::isUlid($identityKey->browser_db_id))->toBeTrue();
});

it('preserves an existing browser database id on update', function () {
    $user = User::factory()->create();

    $identityKey = UserIdentityKey::query()->create([
        'user_id' => $user->id,
        'public_key_jwk' => validPublicKeyPayload()['public_key_jwk'],
        'fingerprint' => validPublicKeyPayload()['fingerprint'],
    ]);

    $browserDbId = $identityKey->browser_db_id;

    $identityKey->update([
        'fingerprint' => str_repeat('b', 64),
    ]);

    expect($identityKey->fresh()->browser_db_id)->toBe($browserDbId);
});

it('enforces unique browser database ids', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    $firstIdentityKey = UserIdentityKey::query()->create([
        'user_id' => $firstUser->id,
        'public_key_jwk' => validPublicKeyPayload()['public_key_jwk'],
        'fingerprint' => validPublicKeyPayload()['fingerprint'],
    ]);

    expect(fn () => UserIdentityKey::query()->create([
        'user_id' => $secondUser->id,
        'browser_db_id' => $firstIdentityKey->browser_db_id,
        'public_key_jwk' => validPublicKeyPayload()['public_key_jwk'],
        'fingerprint' => str_repeat('b', 64),
    ]))->toThrow(UniqueConstraintViolationException::class);
});
