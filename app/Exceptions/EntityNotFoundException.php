<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EntityNotFoundException extends Exception
{
    private int $status = 404;

    public function render(Request $request): Response
    {
        return response([
            'message' => $this->message,
        ], $this->status);
    }
}
