<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ProductMoving extends Model
{
    protected $table = 'product_movings';  // Nama tabel
    protected $primaryKey = 'id';   // Kunci utama
    protected $keyType = 'int';    // Tipe kunci utama
    public $incrementing = true; // Kunci utama auto-increment
    protected $fillable = ['product_id', 'type','outlet_id', 'quantity','tanggal','pic','keterangan'];  // Kolom yang bisa diisi
    public $timestamps = true;

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }
}
