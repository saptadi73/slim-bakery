<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Receive extends Model
{
    protected $table = 'receives';  // Nama tabel
    protected $primaryKey = 'id';   // Kunci utama
    protected $keyType = 'int';    // Tipe kunci utama
    public $incrementing = true; // Kunci utama auto-increment
    protected $fillable = ['no_rec', 'pic', 'tanggal', 'delivery_order_id'];  // Kolom yang bisa diisi
    public $timestamps = true;

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }

    public function receiveItems()
    {
        return $this->hasMany(ReceiveItem::class, 'receive_id');
    }
}

?>
