<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ConversationCollection extends ResourceCollection
{
    /**
     * @var class-string<ConversationResource>
     */
    public $collects = ConversationResource::class;

    public static $wrap = null;

    /**
     * @return array{conversations: list<array<string, mixed>>}
     */
    public function toArray(Request $request): array
    {
        return [
            'conversations' => array_values($this->collection->map->resolve($request)->all()),
        ];
    }
}
