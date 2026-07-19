<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessagesViewedBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Dedicated queue so chat realtime is not blocked by slow jobs.
     */
    public string $queue = 'broadcasts';

    /**
     * @param  list<string>  $publicIds
     */
    public function __construct(
        public Conversation $conversation,
        public array $publicIds,
    ) {}

    public function broadcastAs(): string
    {
        return 'ChatMessagesViewed';
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->conversation->public_key.'.receipts'),
        ];
    }

    /**
     * @return array{public_ids: list<string>}
     */
    public function broadcastWith(): array
    {
        return [
            'public_ids' => $this->publicIds,
        ];
    }

    public function broadcastWhen(): bool
    {
        return $this->publicIds !== [];
    }
}
