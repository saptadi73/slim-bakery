<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as DB;

class Order extends Model
{
    protected $table = 'orders';  // Nama tabel
    protected $primaryKey = 'id';   // Kunci utama
    protected $keyType = 'int';    // Tipe kunci utama
    public $incrementing = true; // Kunci utama auto-increment
    protected $fillable = ['no_order', 'outlet_id', 'user_id', 'total', 'status', 'tanggal'];  // Kolom yang bisa diisi
    public $timestamps = true;

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Generate next no_order in format ORDER-00001 based on last order
     * @return string
     */
    public static function generateNoOrder()
    {
        // Prefer DB sequence (Postgres) when available for robust concurrency-safe numbering
        try {
            $res = DB::select("SELECT nextval('order_no_seq') as seq");
            if (is_array($res) && count($res) && isset($res[0]->seq)) {
                $num = intval($res[0]->seq);
                return 'ORDER-' . str_pad($num, 5, '0', STR_PAD_LEFT);
            }
        } catch (\Throwable $t) {
            // Sequence not available or not Postgres — fall back to application-level generator below
        }

        // Fallback: derive from last order stored (best-effort, not fully race-proof)
        $last = self::orderBy('id', 'desc')->first();
        if (!$last || !$last->no_order) {
            $num = 1;
        } else {
            // try to extract trailing number from last no_order
            if (preg_match('/(\d+)$/', $last->no_order, $m)) {
                $num = intval($m[1]) + 1;
            } else {
                // fallback to next id
                $num = $last->id + 1;
            }
        }
        return 'ORDER-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }
}

?>
