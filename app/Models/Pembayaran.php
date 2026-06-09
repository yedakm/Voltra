<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $connection = 'voltra';
    protected $table = 'pembayaran';
    protected $primaryKey = 'id_pembayaran';
    public $timestamps = false;
    protected $guarded = [];

    public function sewa()
    {
        return $this->belongsTo(TransaksiSewa::class, 'id_sewa', 'id_sewa');
    }
}
