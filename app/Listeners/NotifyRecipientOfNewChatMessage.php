<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ChatMessageSent;
use App\Notifications\NewChatMessageNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyRecipientOfNewChatMessage implements ShouldQueue
{
    public function handle(ChatMessageSent $event): void
    {
        if (! $event->recipient->pushSubscriptions()->exists()) {
            return;
        }

        $event->recipient->notify(new NewChatMessageNotification($event->sender));
    }
}
