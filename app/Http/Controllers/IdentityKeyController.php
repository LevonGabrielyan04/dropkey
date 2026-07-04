<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreIdentityKeyRequest;
use App\Models\User;
use App\Models\UserIdentityKey;
use Illuminate\Http\JsonResponse;

class IdentityKeyController extends Controller
{
    public function store(StoreIdentityKeyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        UserIdentityKey::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'public_key_jwk' => $validated['public_key_jwk'],
                'fingerprint' => $validated['fingerprint'],
            ],
        );

        return response()->json(['status' => 'ok']);
    }

    public function show(User $user): JsonResponse
    {
        abort_if($user->id === auth()->id(), 404);

        $identityKey = UserIdentityKey::query()
            ->where('user_id', $user->id)
            ->first();

        if ($identityKey === null) {
            abort(404);
        }

        return response()->json([
            'user_id' => $user->id,
            'public_key_jwk' => $identityKey->public_key_jwk,
            'fingerprint' => $identityKey->fingerprint,
        ]);
    }

    public function mine(): JsonResponse
    {
        $identityKey = UserIdentityKey::query()
            ->where('user_id', auth()->id())
            ->first();

        if ($identityKey === null) {
            return response()->json(['registered' => false]);
        }

        return response()->json([
            'registered' => true,
            'public_key_jwk' => $identityKey->public_key_jwk,
            'fingerprint' => $identityKey->fingerprint,
        ]);
    }
}
