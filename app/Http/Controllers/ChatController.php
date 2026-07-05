<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\Interfaces\ConversationRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct(protected ConversationRepositoryInterface $conversations) {}

    public function index(Request $request): View
    {
        $conversations = $this->conversations->getConversationsForUser($request->user());

        return view('chat.index', compact('conversations'));
    }

    public function show(User $user): View
    {
        abort_unless(auth()->id() !== $user->id, 404);

        return view('chat.show', ['recipient' => $user]);
    }
}
