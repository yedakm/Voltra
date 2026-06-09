<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailJurnal extends Model
{
    protected $connection = 'voltra_akuntansi';
    protected $table = 'detail_jurnal';
    protected $primaryKey = 'id_detail_jurnal';
    public $timestamps = false;
    protected $guarded = [];

    public function jurnal()
    {
        return $this->belongsTo(JurnalAkuntansi::class, 'id_jurnal', 'id_jurnal');
    }

    public function akun()
    {
        return $this->belongsTo(AkunPerkiraan::class, 'kode_akun', 'kode_akun');
    }
}
