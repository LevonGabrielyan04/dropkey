<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Services\Interfaces\ChatMessageServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MarkChatMessageAsViewedController extends Controller
{
    public function __construct(protected ChatMessageServiceInterface $chatMessages) {}

    public function __invoke(Request $request, ChatMessage $message): Response
    {
        $this->chatMessages->markMessageAsViewed($request->user(), $message);

        return response()->noContent();
    }
}
