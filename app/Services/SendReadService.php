<?php

namespace App\Services;

use App\Repositories\Interfaces\SendRepositoryInterface;
use App\Services\Interfaces\SendReadServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class SendReadService implements SendReadServiceInterface
{
    /**
     * @var array<int, string>
     */
    private const INDEX_COLUMNS = ['id', 'name', 'valid_to', 'public_id'];

    public function __construct(protected SendRepositoryInterface $sendRepository) {}

    public function findAll(): Collection
    {
        $userId ??= auth()->id();

        return $this->sendRepository->findAll((string) $userId, self::INDEX_COLUMNS);
    }
}
