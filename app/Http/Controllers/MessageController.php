<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\IndexChatMessageRequest;
use App\Http\Requests\StoreChatMessageRequest;
use App\Models\User;
use App\Services\Interfaces\ChatMessageServiceInterface;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    public function __construct(protected ChatMessageServiceInterface $chatMessages) {}

    public function index(IndexChatMessageRequest $request, User $user): JsonResponse
    {
        $messages = $this->chatMessages->getMessagesForUsers(
            $request->user(),
            $user,
            $request->validated('after_public_id'),
        );

        return response()->json([
            'messages' => $messages,
        ]);
    }

    public function store(StoreChatMessageRequest $request): JsonResponse
    {
        $message = $this->chatMessages->storeMessage(
            $request->user(),
            User::query()->findOrFail($request->integer('recipient_id')),
            $request->validated('payload'),
        );

        return response()->json([
            'public_id' => $message->public_id,
            'created_at' => $message->created_at,
        ], 201);
    }
}
