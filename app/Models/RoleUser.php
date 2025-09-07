<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\Pivot;

class RoleUser extends Pivot
{
    protected $table = 'role_user';  // Nama tabel pivot
    public $timestamps = true; // Jika tabel pivot memiliki kolom timestamps
    public $incrementing = false; // Jika tabel pivot memiliki kunci utama auto-increment
    protected $fillable = ['user_id', 'role_id']; // Kolom yang bisa diisi
    protected $casts = [
        'user_id' => 'integer',
        'role_id' => 'integer',
    ];

}
?>