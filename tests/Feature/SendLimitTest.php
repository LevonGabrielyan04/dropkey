<?php

use App\Models\User;
use Tests\Factories\SendFactory;

/**
 * @return array{name: string, message: string, expire_after: string, viewers: array<int, string>}
 */
function sendLimitPayload(User $viewer, string $name): array
{
    return [
        'name' => $name,
        'message' => fakeEncryptedMessage(),
        'expire_after' => '1 day',
        'viewers' => [$viewer->name],
    ];
}

it('allows creating sends while under the per-user limit', function () {
    config(['send.max_per_user' => 2]);

    $author = User::factory()->create();
    $viewer = User::factory()->create();

    SendFactory::create($author);

    $this->actingAs($author)
        ->post(route('sends.store'), sendLimitPayload($viewer, 'Second Send'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors();
});

it('denies the create form when the per-user limit is reached', function () {
    config(['send.max_per_user' => 2]);

    $author = User::factory()->create();

    SendFactory::create($author);
    SendFactory::create($author);

    $this->actingAs($author)
        ->get(route('sends.create'))
        ->assertForbidden();
});

it('denies storing a send when the per-user limit is reached', function () {
    config(['send.max_per_user' => 2]);

    $author = User::factory()->create();
    $viewer = User::factory()->create();

    SendFactory::create($author);
    SendFactory::create($author);

    $this->actingAs($author)
        ->post(route('sends.store'), sendLimitPayload($viewer, 'Over Limit Send'))
        ->assertForbidden();
});

it('does not count expired sends toward the per-user limit', function () {
    config(['send.max_per_user' => 2]);

    $author = User::factory()->create();
    $viewer = User::factory()->create();

    SendFactory::create($author, ['valid_to' => now()->subMinute()]);
    SendFactory::create($author);

    $this->actingAs($author)
        ->post(route('sends.store'), sendLimitPayload($viewer, 'Replacement Send'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors();
});
