<?php

declare(strict_types=1);

namespace App\Events;

use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Dedicated queue so chat realtime is not blocked by slow jobs.
     */
    public string $queue = 'broadcasts';

    public function __construct(public ChatMessage $message) {}

    public function broadcastAs(): string
    {
        return 'ChatMessageSent';
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $this->message->loadMissing('conversation');

        return [
            new PrivateChannel('conversation.'.$this->message->conversation->public_key),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->message->loadMissing('sender:id,public_key');

        return ChatMessageResource::make($this->message)->resolve();
    }
}
