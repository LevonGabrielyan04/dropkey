<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TimePeriod;
use App\Http\Requests\UpdateConversationAutoDeleteRequest;
use App\Http\Resources\ConversationCollection;
use App\Models\User;
use App\Repositories\Interfaces\ChatMessageRepositoryInterface;
use App\Repositories\Interfaces\ConversationRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ConversationController extends Controller
{
    public function __construct(
        protected ChatMessageRepositoryInterface $chatMessages,
        protected ConversationRepositoryInterface $conversations,
    ) {}

    public function index(Request $request): ConversationCollection
    {
        return new ConversationCollection(
            $this->conversations->getConversationsForUser($request->user()),
        );
    }

    public function updateAutoDelete(UpdateConversationAutoDeleteRequest $request, User $user): JsonResponse
    {
        $conversation = $this->chatMessages->findOrCreateConversation($request->user(), $user);

        Gate::authorize('update', $conversation);

        $conversation = $this->conversations->updateAutoDelete(
            $conversation,
            $request->enum('auto_delete', TimePeriod::class),
        );

        return response()->json([
            'auto_delete' => $conversation->auto_delete->value,
        ]);
    }
}
