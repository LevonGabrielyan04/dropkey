<?php

namespace App\Actions;

use App\Actions\Interfaces\PreparesSendPivotData;
use Symfony\Component\Uid\Ulid;

class PrepareSendPivotDataAction implements PreparesSendPivotData
{
    /**
     * Transform string ULID and viewer IDs into a binary-ready pivot array.
     */
    public function execute(string $sendId, array $viewerIds): array
    {
        if (empty($viewerIds)) {
            return [];
        }

        $binarySendId = (new Ulid($sendId))->toBinary();

        return collect($viewerIds)->map(function ($viewerId) use ($binarySendId) {
            return [
                'send_id' => $binarySendId,
                'user_id' => $viewerId,
            ];
        })->toArray();
    }
}
