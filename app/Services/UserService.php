<?php

namespace App\Services;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnprocessableException;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class UserService
{
    public function show($id)
    {
        $user = User::find($id);
        if($user === null) {
            throw new EntityNotFoundException();
        }
        return UserService::mapToDTO($user);
    }

    public static function mapToDTO(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'roles' => $user->roles->map(function($role) {
                return $role->name;
            })
        ];
    }

    public function index()
    {
        $users = User::all();
        return UserService::mapToDTOs($users);
    }

    public static function mapToDTOs(Collection $users)
    {
        return $users->map(function($user) {
            return [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ];
        });
    }

    public function updateRoles($id, array $roleIds)
    {
        $user = User::find($id);
        if($user === null) {
            throw new EntityNotFoundException();
        }
        $authenticatedUser = auth()->user();
        if($user->id === $authenticatedUser->id) {
            throw new ForbiddenException();
        }
        $authenticatedUserIdAdmin = $authenticatedUser->hasRole('admin');
        $userBeenUpdatedIsAdmin = $user->hasRole('admin');
        if($userBeenUpdatedIsAdmin && !$authenticatedUserIdAdmin) {
            throw new ForbiddenException();
        }
        $roles = Role::whereIn('id', $roleIds)->get();
        $adminRole = $roles->first(function(Role $role) {
            if($role->id === 1) {
                return $role;
            }
        });
        if($adminRole !== null && !$authenticatedUserIdAdmin) {
            throw new ForbiddenException();
        }

        $user->syncRoles($roles);
    }
}

?>
