<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrderItem extends Model
{
    protected $table = 'delivery_order_items';  // Nama tabel
    protected $primaryKey = 'id';   // Kunci utama
    protected $keyType = 'int';    // Tipe kunci utama
    public $incrementing = true; // Kunci utama auto-increment
    protected $fillable = ['delivery_order_id', 'provider_id', 'quantity', 'pic', 'tanggal', 'product_id'];  // Kolom yang bisa diisi
    public $timestamps = true;

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function receiveItems()
    {
        return $this->hasMany(ReceiveItem::class, 'delivery_order_items_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

?>
