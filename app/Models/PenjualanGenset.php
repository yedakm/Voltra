<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanGenset extends Model
{
    protected $connection = 'voltra';
    protected $table = 'penjualan_genset';
    protected $primaryKey = 'id_penjualan';
    public $timestamps = false;
    protected $guarded = [];

    public function genset()
    {
        return $this->belongsTo(Genset::class, 'id_genset', 'id_genset');
    }
}
