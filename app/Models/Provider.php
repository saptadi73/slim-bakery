<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Provider extends Model
{
    protected $table = 'providers';  // Nama tabel
    protected $primaryKey = 'id';   // Kunci utama
    protected $keyType = 'int';    // Tipe kunci utama
    public $incrementing = true; // Kunci utama auto-increment
    protected $fillable = ['order_id', 'quantity','tanggal','pic','nopro'];  // Kolom yang bisa diisi
    public $timestamps = true;

    public function delivers()
    {
        return $this->hasMany(Deliver::class, 'provider_id');
    }
    
    public function orders()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}

?>