<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';  // Nama tabel
    protected $primaryKey = 'id';   // Kunci utama
    protected $keyType = 'int';    // Tipe kunci utama
    public $incrementing = true; // Kunci utama auto-increment
    // Sesuaikan dengan migration_all.php: include harga_jual, harga_beli, stok, status
    protected $fillable = ['nama', 'kode', 'gambar', 'category_id', 'harga_jual', 'harga_beli', 'stok', 'status'];  // Kolom yang bisa diisi
    public $timestamps = true;

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_id');
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