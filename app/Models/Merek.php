<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Merek extends Model
{
    protected $connection = 'voltra';
    protected $table = 'merek';
    protected $primaryKey = 'id_merek';
    public $timestamps = false;
    protected $guarded = [];

    public function genset()
    {
        return $this->hasMany(Genset::class, 'id_merek', 'id_merek');
    }
}
