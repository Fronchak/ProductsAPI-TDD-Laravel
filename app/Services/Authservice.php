<?php

namespace App\Services;

use App\Models\User;

class Authservice
{
    public function register($data)
    {
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $password = $data['password'];
        $user->password = bcrypt($password);

        $user->save();

        return $this->authenticate([
            'email' => $user->email,
            'password' => $password
        ]);
    }

    protected function authenticate($credentials) {
        $token = auth()->attempt($credentials);
        return [
            'access_token' => $token,
            'token_type' => 'bearer'
        ];
    }
}

?>
