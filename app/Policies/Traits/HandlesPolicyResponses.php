<?php

declare(strict_types=1);

namespace App\Policies\Traits;

use App\Support\PolicyDenialLogger;
use Illuminate\Auth\Access\Response;

trait HandlesPolicyResponses
{
    /**
     * Standardize policy denial to prevent data leakage.
     */
    protected function sendResponse(bool $allow): Response
    {
        if ($allow) {
            return Response::allow();
        }

        app(PolicyDenialLogger::class)->log();

        return Response::denyAsNotFound();
    }
}
