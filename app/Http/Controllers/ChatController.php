<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->where(function ($query) use ($user): void {
                $query->where('user_one_id', $user->id)
                    ->orWhere('user_two_id', $user->id);
            })
            ->with([
                'userOne',
                'userTwo',
                'messages' => fn ($query) => $query->latest('id')->limit(1),
            ])
            ->latest('id')
            ->get();

        return view('chat.index', compact('conversations'));
    }

    public function show(User $user): View
    {
        abort_unless(auth()->id() !== $user->id, 404);

        return view('chat.show', ['recipient' => $user]);
    }
}
