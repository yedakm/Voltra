<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriGenset extends Model
{
    protected $connection = 'voltra';
    protected $table = 'kategori_genset';
    protected $primaryKey = 'id_kategori';
    public $timestamps = false;
    protected $guarded = [];

    public function genset()
    {
        return $this->hasMany(Genset::class, 'id_kategori', 'id_kategori');
    }
}
