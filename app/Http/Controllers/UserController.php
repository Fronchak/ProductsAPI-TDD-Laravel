<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;

class UserController extends Controller
{
    private UserService $userService;
    public function __construct(UserService $userService)
    {
        $this->middleware('jwt.auth')->only(['show']);
        $this->middleware('role:worker|admin')->only(['show']);
        $this->userService = $userService;
    }
    public function show($id)
    {
        $responseData = $this->userService->show($id);
        return response($responseData);
    }
}
