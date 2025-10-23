<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';  // Nama tabel
    protected $primaryKey = 'id';   // Kunci utama
    protected $keyType = 'int';    // Tipe kunci utama
    public $incrementing = true; // Kunci utama auto-increment
    protected $fillable = ['nama', 'kode','gambar'];  // Kolom yang bisa diisi
    public $timestamps = true;

    public function orders()
    {
        return $this->hasMany(Order::class, 'product_id');
    }
    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'product_id');
    }

    public function productMovings()
    {
        return $this->hasMany(ProductMoving::class, 'product_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
?>