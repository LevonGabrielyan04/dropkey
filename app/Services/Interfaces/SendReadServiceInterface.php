<?php

namespace App\Services\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface SendReadServiceInterface
{
    public function findAll(): Collection;
}
