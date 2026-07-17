<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ChatMessageSent;
use App\Notifications\NewChatMessageNotification;

class NotifyRecipientOfNewChatMessage
{
    public function handle(ChatMessageSent $event): void
    {
        if (! $event->recipient->pushSubscriptions()->exists()) {
            return;
        }

        $event->recipient->notify(new NewChatMessageNotification($event->sender));
    }
}
