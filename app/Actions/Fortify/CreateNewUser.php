<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->registrationPasswordRules(),
        ])->validate();

        $email = filled($input['email'] ?? null) ? Str::lower($input['email']) : null;

        $user = User::create([
            'name' => $input['name'],
            'email' => $email,
            'password' => $input['password'],
        ]);

        if ($email === null) {
            $user->markEmailAsVerified();
        }

        return $user;
    }
}
