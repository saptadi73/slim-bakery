<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ReceiveItem extends Model
{
    protected $table = 'receive_items';  // Nama tabel
    protected $primaryKey = 'id';   // Kunci utama
    protected $keyType = 'int';    // Tipe kunci utama
    public $incrementing = true; // Kunci utama auto-increment
    protected $fillable = ['receive_id', 'delivery_order_items_id', 'quantity', 'pic', 'tanggal'];  // Kolom yang bisa diisi
    public $timestamps = true;

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function receive()
    {
        return $this->belongsTo(Receive::class, 'receive_id');
    }

    public function deliveryOrderItem()
    {
        return $this->belongsTo(DeliveryOrderItem::class, 'delivery_order_items_id');
    }
}

?>
