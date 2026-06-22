<?php

namespace App\Actions\Interfaces;

interface PreparesSendPivotData
{
    public function execute(string $sendId, array $viewerIds): array;
}
