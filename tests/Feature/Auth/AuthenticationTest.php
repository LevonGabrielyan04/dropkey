<?php

use App\Models\User;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('chat.index', absolute: false));

    $this->assertAuthenticated();
});

test('users can authenticate using their nickname', function () {
    $user = User::factory()->create(['name' => 'Unique Nickname']);

    $response = $this->post(route('login.store'), [
        'email' => 'Unique Nickname',
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('chat.index', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('users can authenticate using their nickname case insensitively', function () {
    $user = User::factory()->create(['name' => 'CaseSensitiveNick']);

    $response = $this->post(route('login.store'), [
        'email' => 'casesensitivenick',
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('chat.index', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('users without an email can authenticate using their nickname', function () {
    $user = User::factory()->create(['name' => 'No Email Nick', 'email' => null]);

    $response = $this->post(route('login.store'), [
        'email' => 'No Email Nick',
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('chat.index', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('email');

    $this->assertGuest();
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});
