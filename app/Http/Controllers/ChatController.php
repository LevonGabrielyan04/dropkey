<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TimePeriod;
use App\Models\User;
use App\Repositories\Interfaces\ChatMessageRepositoryInterface;
use App\Repositories\Interfaces\ConversationRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct(
        protected ConversationRepositoryInterface $conversations,
        protected ChatMessageRepositoryInterface $chatMessages,
    ) {}

    public function index(Request $request): View
    {
        $conversations = $this->conversations->getConversationsForUser($request->user());

        return view('chat.index', compact('conversations'));
    }

    public function show(User $user): View
    {
        abort_unless(auth()->id() !== $user->id, 404);

        $conversation = $this->chatMessages->findConversationBetweenUsers(auth()->user(), $user);

        return view('chat.show', [
            'recipient' => $user,
            'autoDelete' => $conversation->auto_delete ?? TimePeriod::SEVEN_DAYS,
            'timePeriods' => TimePeriod::cases(),
        ]);
    }
}
