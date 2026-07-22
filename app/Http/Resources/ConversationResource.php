<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     public_key: string,
     *     unread_messages_count: int,
     *     partner: array{name: string, url: string},
     *     last_message_at: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $partner = $this->partnerFor($user);
        $latestMessage = $this->relationLoaded('messages')
            ? $this->messages->first()
            : null;

        return [
            'public_key' => $this->public_key,
            'unread_messages_count' => (int) ($this->unread_messages_count ?? 0),
            'partner' => [
                'name' => $partner->name,
                'url' => route('chat.show', $partner),
            ],
            'last_message_at' => $latestMessage?->created_at?->utc()->toIso8601String(),
        ];
    }
}
