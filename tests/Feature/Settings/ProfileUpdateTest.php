<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('profile.edit'))
        ->assertOk()
        ->assertSee(__('Nickname'), false)
        ->assertSee(__('Update your nickname and email address'), false);
});

test('profile information can be updated', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('adding an email to a user registered without one sends a verification notification', function () {
    Notification::fake();

    $user = User::factory()->create([
        'name' => 'No Email User',
        'email' => null,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('name', 'No Email User')
        ->set('email', 'new@example.com')
        ->call('updateProfileInformation')
        ->assertHasNoErrors()
        ->assertSee(__('A new verification link has been sent to your email address.'), false);

    $user->refresh();

    expect($user->email)->toEqual('new@example.com');
    expect($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('profile update rejects duplicate nicknames', function () {
    User::factory()->create(['name' => 'Taken Nickname']);

    $user = User::factory()->create(['name' => 'Original Nickname']);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('name', 'Taken Nickname')
        ->set('email', $user->email)
        ->call('updateProfileInformation')
        ->assertHasErrors(['name']);

    expect($user->refresh()->name)->toEqual('Original Nickname');
});

test('profile update allows keeping the same nickname', function () {
    $user = User::factory()->create(['name' => 'My Nickname']);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('name', 'My Nickname')
        ->set('email', 'updated@example.com')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('My Nickname');
    expect($user->email)->toEqual('updated@example.com');
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});
