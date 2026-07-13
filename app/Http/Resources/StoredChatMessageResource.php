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
class StoredChatMessageResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{public_id: string, created_at: Carbon|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'created_at' => $this->created_at,
        ];
    }
}
