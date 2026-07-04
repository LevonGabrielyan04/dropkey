<?php

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use App\Models\UserIdentityKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates a canonical conversation for two users', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $conversation = Conversation::findOrCreateForUsers($alice, $bob);

    expect($conversation->user_one_id)->toBe(min($alice->id, $bob->id))
        ->and($conversation->user_two_id)->toBe(max($alice->id, $bob->id));
});

it('returns the same conversation regardless of user order', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $first = Conversation::findOrCreateForUsers($alice, $bob);
    $second = Conversation::findOrCreateForUsers($bob, $alice);

    expect($first->id)->toBe($second->id);
});

it('rejects self conversations', function () {
    $user = User::factory()->create();

    Conversation::findOrCreateForUsers($user, $user);
})->throws(InvalidArgumentException::class);

it('resolves the conversation partner for a participant', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $conversation = Conversation::findOrCreateForUsers($alice, $bob);

    expect($conversation->partnerFor($alice)->is($bob))->toBeTrue()
        ->and($conversation->partnerFor($bob)->is($alice))->toBeTrue()
        ->and($conversation->includesUser($alice))->toBeTrue()
        ->and($conversation->includesUser($bob))->toBeTrue();
});

it('relates users to identity keys and chat messages', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = Conversation::findOrCreateForUsers($alice, $bob);

    UserIdentityKey::query()->create([
        'user_id' => $alice->id,
        'public_key_jwk' => [
            'kty' => 'EC',
            'crv' => 'P-256',
            'x' => 'test-x',
            'y' => 'test-y',
        ],
        'fingerprint' => str_repeat('a', 64),
    ]);

    ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    $alice->refresh();

    expect($alice->identityKey)->not->toBeNull()
        ->and($alice->sentChatMessages)->toHaveCount(1)
        ->and($conversation->messages)->toHaveCount(1)
        ->and($alice->conversationsAsUserOne()->count() + $alice->conversationsAsUserTwo()->count())->toBe(1);
});
