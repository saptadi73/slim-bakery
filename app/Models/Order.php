<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';  // Nama tabel
    protected $primaryKey = 'id';   // Kunci utama
    protected $keyType = 'int';    // Tipe kunci utama
    public $incrementing = true; // Kunci utama auto-increment
    protected $fillable = ['no_order', 'outlet_name', 'pic_name', 'tanggal'];  // Kolom yang bisa diisi
    public $timestamps = true;

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}

?>
