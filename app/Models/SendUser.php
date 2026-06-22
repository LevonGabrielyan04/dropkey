<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsBinary;
use Illuminate\Database\Eloquent\Relations\Pivot;

class SendUser extends Pivot
{
    public $incrementing = false;

    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'send_id' => AsBinary::ulid(),
        ];
    }
}
