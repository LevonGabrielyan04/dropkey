<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ChatMessageBroadcast;
use App\Events\ChatMessageSent;

class BroadcastChatMessage
{
    public function handle(ChatMessageSent $event): void
    {
        broadcast(new ChatMessageBroadcast($event->message));
    }
}
