<?php

use App\Models\User;

it('includes identity registration bootstrap data on authenticated app pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-identity-register-url="'.route('api.identity.public-key.store').'"', false)
        ->assertSee('data-identity-mine-url="'.route('api.identity.public-key.mine').'"', false)
        ->assertSee('data-csrf-token="', false);
});

it('does not include identity registration bootstrap data on guest pages', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('data-identity-register-url', false)
        ->assertDontSee('data-identity-mine-url', false);
});
