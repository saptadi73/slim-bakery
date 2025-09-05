<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';  // Nama tabel
    protected $fillable = ['name', 'email', 'password'];  // Kolom yang bisa diisi
    protected $hidden = ['password'];  // Menyembunyikan password dalam respons API
    public $timestamps = true;

    // Relasi Many-to-Many dengan Role
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')->withTimestamps();
    }

    // Memeriksa apakah pengguna memiliki role tertentu
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }
}
