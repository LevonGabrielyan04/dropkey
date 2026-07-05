<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreIdentityKeyRequest;
use App\Models\User;
use App\Repositories\Interfaces\UserIdentityKeyRepositoryInterface;
use Illuminate\Http\JsonResponse;

class IdentityKeyController extends Controller
{
    public function __construct(protected UserIdentityKeyRepositoryInterface $identityKeys) {}

    public function store(StoreIdentityKeyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->identityKeys->updateOrCreateForUser(
            $request->user()->id,
            $validated['public_key_jwk'],
            $validated['fingerprint'],
        );

        return response()->json(['status' => 'ok']);
    }

    public function show(User $user): JsonResponse
    {
        $identityKey = $this->identityKeys->findForUser($user->id);

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
        $identityKey = $this->identityKeys->findForUser((int) auth()->id());

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
