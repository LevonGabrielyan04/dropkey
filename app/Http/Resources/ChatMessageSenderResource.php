<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class ChatMessageSenderResource extends JsonResource
{
    /**
     * @return array{public_id: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_key,
        ];
    }
}
