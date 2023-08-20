<?php

namespace App\Exceptions;

class UnhauthorizationException extends ApiException
{
    protected function getStatus(): int
    {
        return 401;
    }
}
