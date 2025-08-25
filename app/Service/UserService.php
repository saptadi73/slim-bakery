<?php

namespace App\Service;

use App\Model\User;
use App\Model\Role;

class UserService
{
    // Mencari pengguna berdasarkan email
    public static function findByEmail($email)
    {
        return User::where('email', $email)->first();
    }

    // Mencari pengguna berdasarkan ID
    public static function findById($id)
    {
        return User::find($id);
    }

    // Membuat pengguna baru dan mengaitkannya dengan role melalui tabel pivot
    public static function create($name, $email, $password, $role_id = null)
    {
        // Jika tidak ada role_id yang diberikan, ambil role default (misalnya 'User')
        if (!$role_id) {
            $role = Role::where('name', 'User')->first();  // Ambil role default
            if (!$role) {
                return ['success' => false, 'message' => 'Default role not found'];
            }
            $role_id = $role->id;  // Jika role ditemukan, gunakan id-nya
        }

        // Membuat pengguna baru tanpa menyertakan role_id
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        // Menyambungkan pengguna dengan role yang diberikan melalui pivot table role_user
        $user->roles()->attach($role_id);  // Menambahkan relasi pada tabel pivot

        return $user;
    }

    // Mengupdate pengguna berdasarkan ID dan mengupdate relasi di pivot table
    public static function update($id, $data)
    {
        $user = self::findById($id);
        if ($user) {
            // Jika ada data role_id, pastikan role yang dimaksud valid
            if (isset($data['role_id'])) {
                $role = Role::find($data['role_id']);
                if (!$role) {
                    return ['success' => false, 'message' => 'Role not found'];
                }

                // Memperbarui relasi di tabel pivot jika role_id diubah
                $user->roles()->sync([$data['role_id']]);  // Mengupdate role pengguna di pivot table
            }

            $user->update($data); // Update data pengguna lainnya
            return $user;
        }
        return null;
    }

    // Menghapus pengguna berdasarkan ID
    public static function delete($id)
    {
        $user = self::findById($id);
        if ($user) {
            // Menghapus pengguna dan semua relasi di tabel pivot role_user
            $user->roles()->detach();  // Menghapus semua relasi role untuk pengguna
            return $user->delete();
        }
        return false;
    }
}
