<?php

namespace App\Service;

use App\Model\User;
use App\Model\Role;
use Firebase\JWT\JWT;

class AuthService
{
    // Fungsi login
    public static function login($email, $password)
    {
        // Mencari user berdasarkan email
        $user = User::where('email', $email)->first();

        // Cek apakah user ditemukan dan password cocok
        if ($user && password_verify($password, $user->password)) {
            $key = $_ENV['JWT_SECRET'] ?? null;  // Mengambil secret key untuk JWT dari environment
            if (!$key) {
                throw new \Exception('JWT_SECRET not set in environment');
            }

            // Payload untuk JWT token
            $payload = [
                'sub' => $user->id,  // ID user yang login
                'name' => $user->name,
                'email' => $user->email,
                'iat' => time(),  // Waktu token dibuat
                'exp' => time() + 3600 // Waktu token kadaluarsa (1 jam)
            ];

            // Membuat JWT token
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

    // Fungsi registrasi
    public static function register($name, $email, $password, $role_id = null)
    {
        // Jika tidak ada role_id yang diberikan, set role default (misalnya, 'User')
        if (!$role_id) {
            $role = Role::where('name', 'user')->first();  // Mengambil role default dengan nama 'user'
            if (!$role) {
                return ['success' => false, 'message' => 'Default role not found'];  // Jika role tidak ditemukan
            }
            $role_id = $role->id;  // Assign role_id default
        }

        // Membuat user baru tanpa menyertakan role_id pada tabel 'users'
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),  // Enkripsi password
        ]);

        // Menyambungkan pengguna dengan role yang diberikan melalui pivot table role_user
        $user->roles()->attach($role_id);  // Menambahkan relasi pada tabel pivot 'role_user'

        return $user;  // Mengembalikan data user yang telah dibuat
    }
}
