<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodeAkuntansi extends Model
{
    protected $connection = 'voltra_akuntansi';
    protected $table = 'periode_akuntansi';
    protected $primaryKey = 'id_periode';
    public $timestamps = false;
    protected $guarded = [];

    public function jurnal()
    {
        return $this->hasMany(JurnalAkuntansi::class, 'id_periode', 'id_periode');
    }
}
