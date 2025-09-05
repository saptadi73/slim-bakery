<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';  // Nama tabel
    protected $fillable = ['name', 'label'];  // Kolom yang bisa diisi
    public $timestamps = true;

    // Relasi Many-to-Many dengan User
    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user', 'role_id', 'user_id')->withTimestamps();
    }
}
