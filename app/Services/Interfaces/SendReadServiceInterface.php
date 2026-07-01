<?php

namespace App\Services\Interfaces;

use App\Models\Send;
use Illuminate\Database\Eloquent\Collection;

interface SendReadServiceInterface
{
    /**
     * @return Collection<int, Send>
     */
    public function findAll(): Collection;

    public function findOne(Send $send): Send;
}
