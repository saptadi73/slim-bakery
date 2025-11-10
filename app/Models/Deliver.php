<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Deliver extends Model
{
    protected $table = 'delivers';  // Nama tabel
    protected $primaryKey = 'id';   // Kunci utama
    protected $keyType = 'int';    // Tipe kunci utama
    public $incrementing = true; // Kunci utama auto-increment
    protected $fillable = ['provider_id', 'quantity','tanggal','pic','receiver'];  // Kolom yang bisa diisi
    public $timestamps = true;

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }
}

?>