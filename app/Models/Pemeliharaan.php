<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pemeliharaan extends Model
{
    protected $connection = 'voltra';
    protected $table = 'pemeliharaan';
    protected $primaryKey = 'id_pemeliharaan';
    public $timestamps = false;
    protected $guarded = [];
    protected $appends = ['status'];

    public function genset()
    {
        return $this->belongsTo(Genset::class, 'id_genset', 'id_genset');
    }

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function detail()
    {
        return $this->hasMany(DetailPemeliharaan::class, 'id_pemeliharaan', 'id_pemeliharaan');
    }

    /** Status diturunkan dari tgl_selesai (tidak disimpan sebagai kolom). */
    public function getStatusAttribute(): string
    {
        return $this->tgl_selesai ? 'Selesai' : 'Dalam Proses';
    }
}
