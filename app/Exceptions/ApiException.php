<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

abstract class ApiException extends Exception
{
    public function render(Request $request): Response
    {
        return response([
            'message' => $this->message,
        ], $this->getStatus());
    }

    abstract protected function getStatus(): int;
}
