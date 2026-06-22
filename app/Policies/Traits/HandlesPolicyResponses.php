<?php

namespace App\Policies\Traits;

use Illuminate\Auth\Access\Response;

trait HandlesPolicyResponses
{
    /**
     * Standardize policy denial to prevent data leakage.
     */
    protected function sendResponse(bool $allow): Response
    {
        return $allow ? Response::allow() : Response::denyAsNotFound();
    }
}
