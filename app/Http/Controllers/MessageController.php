<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreChatMessageRequest;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, User $user): JsonResponse
    {
        abort_if($user->id === $request->user()->id, 404);

        $conversation = Conversation::query()
            ->where('user_one_id', min($request->user()->id, $user->id))
            ->where('user_two_id', max($request->user()->id, $user->id))
            ->first();

        if ($conversation === null) {
            return response()->json(['messages' => []]);
        }

        $this->authorize('view', $conversation);

        $afterId = max(0, (int) $request->query('after_id', 0));

        $messages = ChatMessage::query()
            ->where('conversation_id', $conversation->id)
            ->when($afterId > 0, fn ($query) => $query->where('id', '>', $afterId))
            ->orderBy('id')
            ->limit(100)
            ->get(['id', 'sender_id', 'payload', 'created_at']);

        return response()->json([
            'messages' => $messages,
        ]);
    }

    public function store(StoreChatMessageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $recipient = User::query()->findOrFail($request->integer('recipient_id'));
        $conversation = Conversation::findOrCreateForUsers($request->user(), $recipient);

        $this->authorize('view', $conversation);

        $message = ChatMessage::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'payload' => $validated['payload'],
        ]);

        return response()->json([
            'id' => $message->id,
            'created_at' => $message->created_at,
        ], 201);
    }
}
