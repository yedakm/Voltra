<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    protected $connection = 'voltra';
    protected $table = 'pelanggan';
    protected $primaryKey = 'id_pelanggan';
    public $timestamps = false;
    protected $guarded = [];

    public function transaksiSewa()
    {
        return $this->hasMany(TransaksiSewa::class, 'id_pelanggan', 'id_pelanggan');
    }
}
