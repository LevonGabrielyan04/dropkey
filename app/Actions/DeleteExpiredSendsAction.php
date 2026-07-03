<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\Interfaces\SendRepositoryInterface;

class DeleteExpiredSendsAction
{
    public function __construct(private SendRepositoryInterface $sendRepository) {}

    /**
     * Permanently delete all sends whose validity has expired.
     */
    public function execute(): int
    {
        return $this->sendRepository->deleteExpired();
    }
}
