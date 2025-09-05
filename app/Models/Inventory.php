<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventories';  // Nama tabel
    protected $primaryKey = 'id';   // Kunci utama
    protected $keyType = 'int';    // Tipe kunci utama
    public $incrementing = true; // Kunci utama auto-increment
    protected $fillable = ['product_id', 'quantity','tanggal','pic'];  // Kolom yang bisa diisi
    public $timestamps = true;

    public function products()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}