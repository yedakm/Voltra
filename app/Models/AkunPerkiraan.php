<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * akun_perkiraan - schema akuntansi, composite PK (kode_akun, id_perusahaan).
 */
class AkunPerkiraan extends Model
{
    protected $connection = 'voltra_akuntansi';
    protected $table = 'akun_perkiraan';
    protected $primaryKey = 'kode_akun';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];
}
