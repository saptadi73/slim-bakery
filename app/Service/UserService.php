<?php
namespace App\Service;

use App\Model\User;

class UserService {
    public static function findByEmail($email) {
        return User::where('email', $email)->first();
    }

    public static function findById($id) {
        return User::find($id);
    }

    public static function create($name, $email, $password) {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ]);
    }

    public static function update($id, $data) {
        $user = self::findById($id);
        if ($user) {
            $user->update($data);
            return $user;
        }
        return null;
    }

    public static function delete($id) {
        $user = self::findById($id);
        if ($user) {
            return $user->delete();
        }
        return false;
    }

}
