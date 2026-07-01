<?php

use App\Models\User;

beforeEach(function () {
    config(['send.max_per_user' => 20]);

    $this->travel(61)->seconds();
});

function validSendPayload(User $viewer, string $name): array
{
    return [
        'name' => $name,
        'message' => fakeEncryptedMessage(),
        'expire_after' => '1 day',
        'viewers' => [$viewer->name],
    ];
}

it('throttles store requests after ten per minute', function () {
    $author = User::factory()->create();
    $viewer = User::factory()->create();

    for ($i = 0; $i < 10; $i++) {
        $this->actingAs($author)
            ->post(route('sends.store'), validSendPayload($viewer, "Send {$i}"))
            ->assertRedirect(route('dashboard'));
    }

    $this->actingAs($author)
        ->post(route('sends.store'), validSendPayload($viewer, 'Send 11'))
        ->assertTooManyRequests();
});
