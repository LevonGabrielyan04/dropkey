<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @mixin ChatMessage
 */
class ChatMessageResource extends JsonResource
{
    /**
     * @return array{public_id: string, sender: ChatMessageSenderResource, payload: string, is_viewed: bool, created_at: Carbon}
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'sender' => ChatMessageSenderResource::make($this->whenLoaded('sender')),
            'payload' => $this->payload,
            'is_viewed' => $this->is_viewed,
            'created_at' => $this->created_at,
        ];
    }
}
