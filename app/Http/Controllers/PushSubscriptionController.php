<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DeletePushSubscriptionRequest;
use App\Http\Requests\StorePushSubscriptionRequest;
use Illuminate\Http\JsonResponse;

class PushSubscriptionController extends Controller
{
    public function vapidPublicKey(): JsonResponse
    {
        $publicKey = config('webpush.vapid.public_key');

        abort_if(! is_string($publicKey) || $publicKey === '', 503);

        return response()->json([
            'public_key' => $publicKey,
        ]);
    }

    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $request->user()->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth'],
            $validated['content_encoding'] ?? null,
        );

        return response()->json(['status' => 'ok'], 201);
    }

    public function destroy(DeletePushSubscriptionRequest $request): JsonResponse
    {
        $request->user()->deletePushSubscription(
            $request->validated('endpoint'),
        );

        return response()->json(['status' => 'ok']);
    }
}
