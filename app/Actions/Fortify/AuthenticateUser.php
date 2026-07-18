<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class AuthenticateUser
{
    /**
     * Attempt to authenticate the user by email or nickname.
     */
    public function __invoke(Request $request): ?User
    {
        $login = $request->string(Fortify::username())->toString();
        $user = $this->findUser($login);

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return null;
        }

        return $user;
    }

    /**
     * Find a user by email address or nickname.
     */
    private function findUser(string $login): ?User
    {
        $normalizedLogin = Str::lower($login);

        return User::query()
            ->where(function ($query) use ($normalizedLogin): void {
                $query->where('email', $normalizedLogin)
                    ->orWhereRaw('LOWER(name) = ?', [$normalizedLogin]);
            })
            ->first();
    }
}
