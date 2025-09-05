<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';  // Nama tabel
    protected $primaryKey = 'id';   // Kunci utama
    protected $keyType = 'int';    // Tipe kunci utama
    public $incrementing = true; // Kunci utama auto-increment
    protected $fillable = ['order_date', 'outlet_id', 'quantity','tanggal','pic','product_id','no_order'];  // Kolom yang bisa diisi
    public $timestamps = true;

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

?>