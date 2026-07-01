<?php

namespace App\Actions\Interfaces;

interface PreparesSendPivotData
{
    /**
     * @param  array<int, int>  $viewerIds
     * @return array<int, array{send_id: string, user_id: int}>
     */
    public function execute(string $sendId, array $viewerIds): array;
}
