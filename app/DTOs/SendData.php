<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\CarbonImmutable;

readonly class SendData
{
    public function __construct(
        public int $userId,
        public string $message,
        public string $name,
        public CarbonImmutable $validTo,
        public ?string $id = null,
    ) {}

    /**
     * Convert the DTO into a mass-assignable attribute array for the Send model.
     *
     * The `id` key is only included when present (i.e. on creation).
     *
     * @return array{id?: string, user_id: int, message: string, name: string, valid_to: CarbonImmutable}
     */
    public function toArray(): array
    {
        $attributes = [
            'user_id' => $this->userId,
            'message' => $this->message,
            'name' => $this->name,
            'valid_to' => $this->validTo,
        ];

        if ($this->id !== null) {
            $attributes = ['id' => $this->id, ...$attributes];
        }

        return $attributes;
    }
}
