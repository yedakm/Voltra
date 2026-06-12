<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * detail_pemeliharaan - composite primary key (id_pemeliharaan, id_part).
 */
class DetailPemeliharaan extends Model
{
    protected $connection = 'voltra';
    protected $table = 'detail_pemeliharaan';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    public function part()
    {
        return $this->belongsTo(SukuCadang::class, 'id_part', 'id_part');
    }
}
