<?php

namespace App\Services;

use App\Exceptions\UnhauthorizationException;
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
        if(!$token) {
            throw new UnhauthorizationException("");
        }
        return [
            'access_token' => $token,
            'token_type' => 'bearer'
        ];
    }

    public function login($email, $password)
    {
        return $this->authenticate([
            'email' => $email,
            'password' => $password
        ]);
    }
}

?>
