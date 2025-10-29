<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrder extends Model
{
    protected $table = 'delivery_orders';  // Nama tabel
    protected $primaryKey = 'id';   // Kunci utama
    protected $keyType = 'int';    // Tipe kunci utama
    public $incrementing = true; // Kunci utama auto-increment
    protected $fillable = ['no_do', 'pic', 'tanggal', 'status'];  // Kolom yang bisa diisi
    public $timestamps = true;

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function deliveryOrderItems()
    {
        return $this->hasMany(DeliveryOrderItem::class, 'delivery_order_id');
    }

    public function receives()
    {
        return $this->hasMany(Receive::class, 'delivery_order_id');
    }
}

?>
