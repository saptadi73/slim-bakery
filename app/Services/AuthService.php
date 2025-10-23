<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use Firebase\JWT\JWT;
use App\Supports\JsonResponder;

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


            // Ambil semua role user (dari relasi, otomatis join ke tabel role)
            $roles = $user->roles; // Collection of Role

            // Ambil nama role pertama (jika multi-role)
            $roleName = $roles->first() ? $roles->first()->name : null;

            // Payload untuk JWT token
            $payload = [
                'sub' => $user->id,  // ID user yang login
                'name' => $user->name,
                'email' => $user->email,
                'iat' => time(),  // Waktu token dibuat
                'exp' => time() + (12 * 3600) // Waktu token kadaluarsa (12 jam)
            ];

            // Membuat JWT token
            $jwt = JWT::encode($payload, $key, 'HS256');

            return [
                'status' => true,
                'message' => 'Login successful',
                'data' => [
                    'token' => $jwt,
                    'user' => $user,
                    'role_id' => $user->roles->first() ? $user->roles->first()->id : null,
                    'role' => $user->roles->first() ? $user->roles->first()->name : null,
                    'outlet_id' => $user->outlets->first() ? $user->outlets->first()->id : null,
                    'outlet_name' => $user->outlets->first() ? $user->outlets->first()->nama : null,
                ]
            ];
        }

        return [
            'status' => false,
            'message' => 'Invalid credentials',
            'data' => []
        ];
    }

    // Fungsi registrasi
    public static function register($name, $email, $password, $role_id = null, $outlet_id = null)
    {
        // Jika tidak ada role_id yang diberikan, set role default (misalnya, 'User')
        if (!$role_id) {
            $role = Role::where('name', 'user')->first();  // Mengambil role default dengan nama 'user'
            if (!$role) {
                return [
                    'status' => false,
                    'message' => 'Default role not found',
                    'data' => []
                ];  // Jika role tidak ditemukan
            }
            $role_id = $role->id;  // Assign role_id default
        }

        // Membuat user baru tanpa menyertakan role_id pada tabel 'users'
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT)  // Enkripsi password
        ]);



        // Menyambungkan pengguna dengan role yang diberikan melalui pivot table role_user
        $user->roles()->attach($role_id);  // Menambahkan relasi pada tabel pivot 'role_user'

        // Menyambungkan user ke outlet (user_outlet), default ke outlet dengan kode 'DEFAULT' jika null
        if ($outlet_id === null) {
            $defaultOutlet = \App\Models\Outlet::where('kode', 'DEFAULT')->first();
            if ($defaultOutlet) {
                $outlet_id = $defaultOutlet->id;
            } else {
                // Jika tidak ada default outlet, buat atau gunakan ID 1 sebagai fallback
                $outlet_id = 1;
            }
        }
        $user->outlets()->attach($outlet_id);

        return [
            'status' => true,
            'message' => 'User registered successfully',
            'data' => $user
        ];  // Mengembalikan data user yang telah dibuat
    }

    /**
     * Update role user di tabel pivot role_user
     * @param int $userId
     * @param int|array $roleIds
     * @return array
     */
    public static function updateUserRole($userId, $roleIds)
    {
        $user = User::find($userId);
        if (!$user) {
            return [
                'status' => false,
                'message' => 'User tidak ditemukan',
                'data' => []
            ];
        }
        // Sinkronisasi role baru (bisa 1 id atau array id)
        $user->roles()->sync((array)$roleIds);
        return [
            'status' => true,
            'message' => 'Role user berhasil diupdate',
            'data' => []
        ];
    }
}
