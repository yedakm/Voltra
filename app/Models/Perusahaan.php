<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Perusahaan extends Model
{
    protected $connection = 'voltra';
    protected $table = 'perusahaan';
    protected $primaryKey = 'id_perusahaan';
    public $timestamps = false;
    protected $guarded = [];

    public function pengguna()
    {
        return $this->hasMany(Pengguna::class, 'id_perusahaan', 'id_perusahaan');
    }
}
