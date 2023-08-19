<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UnhauthorizationException extends Exception
{
    private int $status = 401;

    public function render(Request $request): Response
    {
        return response([
            'message' => $this->message,
        ], $this->status);
    }
}
