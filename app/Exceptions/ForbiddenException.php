<?php

namespace App\Exceptions;

class ForbiddenException extends ApiException
{
    protected function getStatus(): int
    {
        return 403;
    }
}
