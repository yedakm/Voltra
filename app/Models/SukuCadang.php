<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SukuCadang extends Model
{
    protected $connection = 'voltra';
    protected $table = 'suku_cadang';
    protected $primaryKey = 'id_part';
    public $timestamps = false;
    protected $guarded = [];
}
