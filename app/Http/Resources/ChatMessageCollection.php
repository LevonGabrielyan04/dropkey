<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ChatMessageCollection extends ResourceCollection
{
    /**
     * @var class-string<ChatMessageResource>
     */
    public $collects = ChatMessageResource::class;

    public static $wrap = null;

    /**
     * @return array{messages: list<array<string, mixed>>}
     */
    public function toArray(Request $request): array
    {
        return [
            'messages' => array_values($this->collection->map->resolve($request)->all()),
        ];
    }
}
