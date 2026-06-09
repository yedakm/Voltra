<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalPenyusutan extends Model
{
    protected $connection = 'voltra_akuntansi';
    protected $table = 'jadwal_penyusutan';
    protected $primaryKey = 'id_penyusutan';
    public $timestamps = false;
    protected $guarded = [];
}
