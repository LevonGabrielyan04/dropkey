<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ChatMessageBroadcast;
use App\Events\ChatMessageSent;
use Illuminate\Contracts\Queue\ShouldQueue;

class BroadcastChatMessage implements ShouldQueue
{
    /**
     * Dedicated queue so chat realtime is not blocked by slow jobs.
     */
    public string $queue = 'broadcasts';

    public function handle(ChatMessageSent $event): void
    {
        broadcast(new ChatMessageBroadcast($event->message));
    }
}
