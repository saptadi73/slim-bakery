<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';  // Nama tabel
    protected $primaryKey = 'id';   // Kunci utama
    protected $keyType = 'int';    // Tipe kunci utama
    public $incrementing = true; // Kunci utama auto-increment
    protected $fillable = ['nama', 'keterangan'];  // Kolom yang bisa diisi
    public $timestamps = true;

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}

?>