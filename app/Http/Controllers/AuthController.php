<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        $responseData = $this->authService->register($request->all(['email', 'name', 'password', 'confirm_password']));
        return response($responseData, 201);
    }

    public function login(LoginRequest $request)
    {
        $responseData = $this->authService->login($request->get('email'), $request->get('password'));
        return response($responseData);
    }
}
