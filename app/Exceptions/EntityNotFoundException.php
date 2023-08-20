<?php

namespace App\Exceptions;

class EntityNotFoundException extends ApiException
{
    protected function getStatus(): int
    {
        return 404;
    }
}
