<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRolesRequest;
use Illuminate\Http\Request;
use App\Services\UserService;

class UserController extends Controller
{
    private UserService $userService;
    public function __construct(UserService $userService)
    {
        $this->middleware('jwt.auth')->only(['show', 'index', 'updateRoles']);
        $this->middleware('role:worker|admin')->only(['show', 'index', 'updateRoles']);
        $this->userService = $userService;
    }
    public function show($id)
    {
        $responseData = $this->userService->show($id);
        return response($responseData);
    }

    public function index()
    {
        $responseData = $this->userService->index();
        return response($responseData);
    }

    public function updateRoles(UpdateRolesRequest $request, $id)
    {
        $this->userService->updateRoles($id, $request->get('roles'));
        return response('', 204);
    }
}
