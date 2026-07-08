<?php

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Features;

beforeEach(function () {
    fakeUncompromisedPasswordChecks();
});

test('password requires at least 15 characters when two factor is disabled', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $validator = Validator::make(
        ['password' => 'short-pass-8', 'password_confirmation' => 'short-pass-8'],
        ['password' => ['required', 'string', Password::default(), 'confirmed']],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('password'))->toBeTrue();
});

test('password requires at least 8 characters when two factor is enabled', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user);

    $validator = Validator::make(
        ['password' => 'short-7', 'password_confirmation' => 'short-7'],
        ['password' => ['required', 'string', Password::default(), 'confirmed']],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('password'))->toBeTrue();

    $validator = Validator::make(
        ['password' => 'valid-pass', 'password_confirmation' => 'valid-pass'],
        ['password' => ['required', 'string', Password::default(), 'confirmed']],
    );

    expect($validator->fails())->toBeFalse();
});

test('password reset uses two factor status from email when guest', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    $user = User::factory()->withTwoFactor()->create();

    request()->merge(['email' => $user->email]);

    $validator = Validator::make(
        [
            'email' => $user->email,
            'password' => 'valid-8x',
            'password_confirmation' => 'valid-8x',
        ],
        ['password' => ['required', 'string', Password::default(), 'confirmed']],
    );

    expect($validator->fails())->toBeFalse();
});

test('password must not contain the application name case insensitively', function (callable $caseTransform) {
    $appName = (string) config('app.name');
    $password = str_pad($caseTransform($appName).'-extra-chars', 15, 'x');

    $validator = Validator::make(
        ['password' => $password, 'password_confirmation' => $password],
        ['password' => ['required', 'string', Password::default(), 'confirmed']],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('password'))->toBeTrue();
})->with([
    'lowercase' => [fn (string $name): string => mb_strtolower($name)],
    'uppercase' => [fn (string $name): string => mb_strtoupper($name)],
    'mixed case' => [fn (string $name): string => mb_convert_case($name, MB_CASE_TITLE)],
]);

test('password may omit the application name', function () {
    $password = 'secure-password-ok';

    $validator = Validator::make(
        ['password' => $password, 'password_confirmation' => $password],
        ['password' => ['required', 'string', Password::default(), 'confirmed']],
    );

    expect($validator->fails())->toBeFalse();
});
