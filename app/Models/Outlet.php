<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Outlet extends Model
{
    protected $table = 'outlets';  // Nama tabel
    protected $primaryKey = 'id';   // Kunci utama
    protected $keyType = 'int';    // Tipe kunci utama
    public $incrementing = true; // Kunci utama auto-increment
    protected $fillable = ['nama', 'alamat', 'phone','kode','prioritas','gambar'];  // Kolom yang bisa diisi
    protected $casts = [
        'prioritas' => 'integer', // Mengubah prioritas menjadi integer
    ];
    public $timestamps = true;

    public function orders()
    {
        return $this->hasMany(Order::class, 'outlet_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_outlet', 'outlet_id', 'user_id')->withTimestamps();
    }
    
}

?>