<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * detail_sewa — composite primary key (id_sewa, id_genset).
 */
class DetailSewa extends Model
{
    protected $connection = 'voltra';
    protected $table = 'detail_sewa';
    protected $primaryKey = null;
    public $incrementing = false;
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
