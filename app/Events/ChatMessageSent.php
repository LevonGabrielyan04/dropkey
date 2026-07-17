<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ChatMessage $message,
        public User $sender,
        public User $recipient,
    ) {}
}
