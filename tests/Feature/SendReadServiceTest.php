<?php

use App\Models\Send;
use App\Models\User;
use App\Services\Interfaces\SendReadServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(SendReadServiceInterface::class);
});

it('returns sends for the authenticated user with index columns', function () {
    $author = User::factory()->create();
    $otherUser = User::factory()->create();

    Send::forceCreate([
        'user_id' => $author->id,
        'message' => 'secret one',
        'name' => 'First Send',
        'valid_to' => now()->addDay(),
    ]);
    Send::forceCreate([
        'user_id' => $author->id,
        'message' => 'secret two',
        'name' => 'Second Send',
        'valid_to' => now()->addDay(),
    ]);
    Send::forceCreate([
        'user_id' => $otherUser->id,
        'message' => 'not mine',
        'name' => 'Other User Send',
        'valid_to' => now()->addDay(),
    ]);

    $this->actingAs($author);

    $sends = $this->service->findAll();

    expect($sends)->toHaveCount(2)
        ->and($sends->pluck('name')->all())->toBe(['First Send', 'Second Send'])
        ->and($sends->every(fn (Send $send) => array_keys($send->getAttributes()) === ['id', 'name', 'valid_to', 'public_id']))->toBeTrue();
});

it('does not include the message column in findAll results', function () {
    $user = User::factory()->create();

    Send::forceCreate([
        'user_id' => $user->id,
        'message' => 'secret message',
        'name' => 'Named Send',
        'valid_to' => now()->addDay(),
    ]);

    $this->actingAs($user);

    $sends = $this->service->findAll();

    expect($sends)->toHaveCount(1)
        ->and($sends->first()->getAttributes())->not->toHaveKey('message')
        ->and($sends->every(fn (Send $send) => ! array_key_exists('message', $send->getAttributes())))->toBeTrue();
});

it('returns sends for the authenticated user', function () {
    $author = User::factory()->create();

    Send::forceCreate([
        'user_id' => $author->id,
        'message' => 'secret one',
        'name' => 'First Send',
        'valid_to' => now()->addDay(),
    ]);

    $this->actingAs($author);

    $sends = $this->service->findAll();

    expect($sends)->toHaveCount(1)
        ->and($sends->first()->name)->toBe('First Send');
});

it('returns an empty collection when the user has no sends', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $sends = $this->service->findAll();

    expect($sends)->toBeEmpty();
});
