<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalKetersediaan extends Model
{
    protected $connection = 'voltra';
    protected $table = 'jadwal_ketersediaan';
    protected $primaryKey = 'id_jadwal';
    public $timestamps = false;
    protected $guarded = [];

    public function genset()
    {
        return $this->belongsTo(Genset::class, 'id_genset', 'id_genset');
    }
}
