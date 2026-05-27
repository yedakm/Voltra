<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiSewa extends Model
{
    protected $connection = 'voltra';
    protected $table = 'transaksi_sewa';
    protected $primaryKey = 'id_sewa';
    public $timestamps = false;
    protected $guarded = [];

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function detail()
    {
        return $this->hasMany(DetailSewa::class, 'id_sewa', 'id_sewa');
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'id_sewa', 'id_sewa');
    }
}
