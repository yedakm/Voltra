<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengembalian extends Model
{
    protected $connection = 'voltra';
    protected $table = 'pengembalian';
    protected $primaryKey = 'id_pengembalian';
    public $timestamps = false;
    protected $guarded = [];

    public function genset()
    {
        return $this->belongsTo(Genset::class, 'id_genset', 'id_genset');
    }

    public function sewa()
    {
        return $this->belongsTo(TransaksiSewa::class, 'id_sewa', 'id_sewa');
    }
}
