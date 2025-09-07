<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserOutlet extends Pivot
{
    protected $table = 'user_outlet';  // Nama tabel pivot
    public $timestamps = true; // Jika tabel pivot memiliki kolom timestamps
    public $incrementing = false; // Jika tabel pivot memiliki kunci utama auto-increment
    protected $fillable = ['user_id', 'outlet_id']; // Kolom yang bisa diisi
    protected $casts = [
        'user_id' => 'integer',
        'outlet_id' => 'integer',
    ];

}
?>