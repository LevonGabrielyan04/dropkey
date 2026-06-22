<?php

namespace App\Models\Traits;

use Illuminate\Support\Str;

trait HasPublicAndPrivateIds
{
    public function setUniqueIds(): void
    {
        if (empty($this->{$this->getKeyName()})) {
            $this->{$this->getKeyName()} = (string) Str::ulid();
        }

        if (empty($this->public_id)) {
            $this->public_id = (string) Str::uuid();
        }
    }
}
