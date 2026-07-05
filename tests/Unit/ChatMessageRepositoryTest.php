<?php

use App\Models\User;
use App\Repositories\Interfaces\ChatMessageRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates a canonical conversation for two users', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $repository = app(ChatMessageRepositoryInterface::class);

    $conversation = $repository->findOrCreateConversation($alice, $bob);

    expect($conversation->user_one_id)->toBe(min($alice->id, $bob->id))
        ->and($conversation->user_two_id)->toBe(max($alice->id, $bob->id));
});

it('returns the same conversation regardless of user order', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $repository = app(ChatMessageRepositoryInterface::class);

    $first = $repository->findOrCreateConversation($alice, $bob);
    $second = $repository->findOrCreateConversation($bob, $alice);

    expect($first->id)->toBe($second->id);
});

it('finds an existing conversation between two users', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $repository = app(ChatMessageRepositoryInterface::class);
    $conversation = $repository->findOrCreateConversation($alice, $bob);

    expect($repository->findConversationBetweenUsers($bob, $alice)?->is($conversation))->toBeTrue();
});

it('returns null when looking up a self conversation', function () {
    $user = User::factory()->create();
    $repository = app(ChatMessageRepositoryInterface::class);

    expect($repository->findConversationBetweenUsers($user, $user))->toBeNull();
});

it('rejects self conversations', function () {
    $user = User::factory()->create();
    $repository = app(ChatMessageRepositoryInterface::class);

    $repository->findOrCreateConversation($user, $user);
})->throws(InvalidArgumentException::class);
