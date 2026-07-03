<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\Send;

interface SendWriteServiceInterface
{
    /**
     * Create a new secure send from the given request data.
     *
     * @param  array<string, mixed>  $data
     */
    public function createSend(array $data): Send;

    /**
     * Delete a send by its ID.
     */
    public function deleteSend(string $id): bool;
}
