<?php

namespace App\Actions;

use App\Actions\Interfaces\PreparesSendPivotData;
use Symfony\Component\Uid\Ulid;

class PrepareSendPivotDataAction implements PreparesSendPivotData
{
    /**
     * Transform string ULID and viewer IDs into a binary-ready pivot array.
     *
     * @param  array<int, int>  $viewerIds
     * @return array<int, array{send_id: string, user_id: int}>
     */
    public function execute(string $sendId, array $viewerIds): array
    {
        if (empty($viewerIds)) {
            return [];
        }

        $binarySendId = (new Ulid($sendId))->toBinary();

        return collect($viewerIds)
            ->map(fn (int $viewerId): array => [
                'send_id' => $binarySendId,
                'user_id' => $viewerId,
            ])
            ->values()
            ->all();
    }
}
