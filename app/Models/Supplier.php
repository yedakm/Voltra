<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $connection = 'voltra';
    protected $table = 'supplier';
    protected $primaryKey = 'id_supplier';
    public $timestamps = false;
    protected $guarded = [];
}
