<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());

    fakeUncompromisedPasswordChecks();
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response
        ->assertOk()
        ->assertSee(__('Nickname'), false);
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'ValidPassword-15',
        'password_confirmation' => 'ValidPassword-15',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('registration rejects duplicate nicknames', function () {
    User::factory()->create(['name' => 'Taken Nickname']);

    $response = $this->post(route('register.store'), [
        'name' => 'Taken Nickname',
        'email' => 'another@example.com',
        'password' => 'ValidPassword-15',
        'password_confirmation' => 'ValidPassword-15',
    ]);

    $response->assertSessionHasErrors('name');
    $this->assertGuest();
});
