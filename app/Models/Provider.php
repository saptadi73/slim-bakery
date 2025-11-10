<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Provider extends Model
{
    protected $table = 'providers';  // Nama tabel
    protected $primaryKey = 'id';   // Kunci utama
    protected $keyType = 'int';    // Tipe kunci utama
    public $incrementing = true; // Kunci utama auto-increment
    // tambahkan 'nopro' agar mass-assignment konsisten dengan migration
    protected $fillable = ['order_items_id', 'quantity', 'tanggal', 'pic', 'nopro'];  // Kolom yang bisa diisi
    public $timestamps = true;

    public function delivers()
    {
        return $this->hasMany(Deliver::class, 'provider_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_items_id');
    }

    public function deliveryOrderItems()
    {
        return $this->hasMany(DeliveryOrderItem::class, 'provider_id');
    }

    /**
     * Ambil receive items yang terkait melalui delivery_order_items.
     * receive_items.delivery_order_items_id -> delivery_order_items.id
     * delivery_order_items.provider_id -> providers.id
     */
    public function receiveItems()
    {
        return $this->hasManyThrough(
            ReceiveItem::class,
            DeliveryOrderItem::class,
            'provider_id', // Foreign key on delivery_order_items table...
            'delivery_order_items_id', // Foreign key on receive_items table...
            'id', // Local key on providers
            'id'  // Local key on delivery_order_items
        );
    }
}

?>