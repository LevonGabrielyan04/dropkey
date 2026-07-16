<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\IndexChatMessageRequest;
use App\Http\Requests\StoreChatMessageRequest;
use App\Http\Resources\ChatMessageCollection;
use App\Http\Resources\StoredChatMessageResource;
use App\Models\User;
use App\Services\Interfaces\ChatMessageServiceInterface;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    public function __construct(protected ChatMessageServiceInterface $chatMessages) {}

    public function index(IndexChatMessageRequest $request, User $user): ChatMessageCollection
    {
        $messages = $this->chatMessages->getMessagesForUsers(
            $request->user(),
            $user,
            $request->validated('after_public_id'),
        );

        return new ChatMessageCollection($messages);
    }

    public function store(StoreChatMessageRequest $request): JsonResponse
    {
        $message = $this->chatMessages->storeMessage(
            $request->user(),
            User::query()->findOrFail($request->integer('recipient_id')),
            $request->validated('payload'),
        );

        return $message
            ->toResource(StoredChatMessageResource::class)
            ->response()
            ->setStatusCode(201);
    }
}
