<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JurnalAkuntansi extends Model
{
    protected $connection = 'voltra_akuntansi';
    protected $table = 'jurnal_akuntansi';
    protected $primaryKey = 'id_jurnal';
    public $timestamps = false;
    protected $guarded = [];

    public function detail()
    {
        return $this->hasMany(DetailJurnal::class, 'id_jurnal', 'id_jurnal');
    }

    public function periode()
    {
        return $this->belongsTo(PeriodeAkuntansi::class, 'id_periode', 'id_periode');
    }
}
