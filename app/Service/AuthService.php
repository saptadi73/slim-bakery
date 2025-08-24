<?php
namespace App\Service;

use App\Model\User;
use Firebase\JWT\JWT;

class AuthService {
    public static function login($email, $password) {
        $user = User::where('email', $email)->first();
        if ($user && password_verify($password, $user->password)) {
            $key = 'minierp2025';
            $payload = [
                'sub' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'iat' => time(),
                'exp' => time() + 3600
            ];
            $jwt = JWT::encode($payload, $key, 'HS256');
            return [
                'success' => true,
                'token' => $jwt,
                'user' => $user
            ];
        }
        return [
            'success' => false,
            'message' => 'Invalid credentials'
        ];
    }

    public static function register($name, $email, $password) {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ]);
        return $user;
    }
}
