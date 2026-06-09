<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Genset extends Model
{
    protected $connection = 'voltra';
    protected $table = 'genset';
    protected $primaryKey = 'id_genset';
    public $timestamps = false;
    protected $guarded = [];

    public function kategori()
    {
        return $this->belongsTo(KategoriGenset::class, 'id_kategori', 'id_kategori');
    }

    public function merek()
    {
        return $this->belongsTo(Merek::class, 'id_merek', 'id_merek');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
    }

    public function pemeliharaan()
    {
        return $this->hasMany(Pemeliharaan::class, 'id_genset', 'id_genset');
    }
}
